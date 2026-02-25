import { Component, OnInit, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { AuthService } from '../../core/services/auth.service';
import { Barber } from '../../models/barber.model';
import { Hairstyle } from '../../models/hairstyle.model';
import { environment } from '../../../environments/environment';

interface SlotEntry {
  datetime: string; // ISO string
  available: boolean;
}

interface DayGroup {
  date: string;       // 'YYYY-MM-DD'
  label: string;      // 'Hétfő, febr. 24.'
  slots: SlotEntry[];
}

@Component({
  selector: 'app-booking',
  standalone: true,
  imports: [FormsModule, SlicePipe],
  templateUrl: './booking.component.html',
  styleUrl: './booking.component.css'
})
export class BookingComponent implements OnInit {
  private http    = inject(HttpClient);
  private auth    = inject(AuthService);
  private router  = inject(Router);
  private readonly API = environment.apiUrl;

  // Step 1 — barber
  barbers: Barber[]   = [];
  selectedBarber: Barber | null = null;

  // Step 2 — hairstyle
  hairstyles: Hairstyle[] = [];
  selectedHairstyle: Hairstyle | null = null;

  // Step 3 — calendar
  days: DayGroup[]  = [];
  selectedSlot: string | null = null;
  calendarWeekOffset = 0; // 0 = current week

  // UI state
  step = 1;
  loadingBarbers   = true;
  loadingHairstyles = false;
  loadingSlots     = false;
  submitting       = false;
  error: string | null = null;

  ngOnInit(): void {
    this.http.get<Barber[]>(`${this.API}/barbers`).subscribe({
      next: b => { this.barbers = b; this.loadingBarbers = false; },
      error: () => { this.error = 'Nem sikerült betölteni a borbélyokat.'; this.loadingBarbers = false; }
    });
    this.http.get<Hairstyle[]>(`${this.API}/hairstyles`).subscribe({
      next: h => this.hairstyles = h
    });
  }

  selectBarber(b: Barber): void {
    this.selectedBarber = b;
    this.selectedHairstyle = null;
    this.step = 2;
  }

  selectHairstyle(h: Hairstyle): void {
    this.selectedHairstyle = h;
    this.step = 3;
    this.calendarWeekOffset = 0;
    this.loadSlots();
  }

  // ── calendar navigation ───────────────────────────────────────────
  prevWeek(): void { this.calendarWeekOffset--; this.loadSlots(); }
  nextWeek(): void { this.calendarWeekOffset++; this.loadSlots(); }

  get canGoPrev(): boolean { return this.calendarWeekOffset > 0; }

  private loadSlots(): void {
    if (!this.selectedBarber) return;
    this.loadingSlots = true;
    this.selectedSlot = null;
    this.days = [];

    const monday = this.getMondayOfWeek(this.calendarWeekOffset);
    const sunday = new Date(monday);
    sunday.setDate(sunday.getDate() + 6);

    const dateFrom = this.toDateStr(monday);
    const dateTo   = this.toDateStr(sunday);

    // 1) Szabad slotok lekérése
    this.http.get<{ slots: string[] }>(
      `${this.API}/availability?barberId=${this.selectedBarber.id}&dateFrom=${dateFrom}&dateTo=${dateTo}`
    ).subscribe({
      next: res => {
        // Backend toIso8601String() timezone offsettel adja vissza (pl. +01:00) → normalizálni kell
        const freeSet = new Set(res.slots.map(s => this.normalizeISO(s)));

        // 2) Foglalt slotok lekérése ugyanarra az időszakra
        this.http.get<{ booked: { start_at: string; duration_min: number }[] }>(
          `${this.API}/barbers/${this.selectedBarber!.id}/schedule?dateFrom=${dateFrom}&dateTo=${dateTo}`
        ).subscribe({
          next: schedRes => {
            // Minden foglalt slot ISO stringjét adjuk a bookedSet-be
            const bookedSet = new Set<string>();
            for (const bk of schedRes.booked) {
              // A backend a start_at-ot adja vissza; normalizáljuk másodperc nélkül
              const iso = this.normalizeISO(bk.start_at);
              bookedSet.add(iso);
            }

            this.days = this.buildDays(monday, freeSet, bookedSet);
            this.loadingSlots = false;
          },
          error: () => {
            this.days = this.buildDays(monday, freeSet, new Set());
            this.loadingSlots = false;
          }
        });
      },
      error: () => { this.error = 'Nem sikerült betölteni az időpontokat.'; this.loadingSlots = false; }
    });
  }

  private buildDays(monday: Date, freeSet: Set<string>, bookedSet: Set<string>): DayGroup[] {
    const groups: DayGroup[] = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(d.getDate() + i);
      const dateStr = this.toDateStr(d);

      // Minden 9:00–18:00 közötti 30 perces slot
      const slots: SlotEntry[] = [];
      for (let hour = 9; hour < 18; hour++) {
        for (const min of [0, 30]) {
          const iso = `${dateStr}T${String(hour).padStart(2,'0')}:${String(min).padStart(2,'0')}:00`;

          // Csak a jövőbeli slotokat mutassuk
          if (new Date(iso) <= new Date()) continue;

          const isFree   = freeSet.has(iso);
          const isBooked = bookedSet.has(iso);

          slots.push({ datetime: iso, available: isFree && !isBooked });
        }
      }

      groups.push({
        date: dateStr,
        label: d.toLocaleDateString('hu-HU', { weekday: 'long', month: 'short', day: 'numeric' }),
        slots
      });
    }
    return groups;
  }

  selectSlot(iso: string): void {
    this.selectedSlot = iso;
  }

  confirmBooking(): void {
    if (!this.selectedSlot || !this.selectedBarber) return;
    const user = this.auth.getCurrentUser();
    if (!user) { this.router.navigate(['/login']); return; }

    this.submitting = true;
    this.error = null;

    const payload = {
      barber_id:      this.selectedBarber.id,
      customer_name:  user.name,
      customer_email: user.email,
      customer_phone: user.phone || '06000000000',
      start_at:       this.selectedSlot,
      duration_min:   this.selectedHairstyle?.duration ?? 30,
      note:           this.selectedHairstyle ? `Nyírás: ${this.selectedHairstyle.name}` : ''
    };

    this.http.post(`${this.API}/bookings`, payload).subscribe({
      next: () => this.router.navigate(['/booking-success']),
      error: err => {
        this.submitting = false;
        this.error = err?.error?.message || 'Sikertelen foglalás. Próbáld újra.';
      }
    });
  }

  // ── helpers ──────────────────────────────────────────────────────
  private getMondayOfWeek(offset: number): Date {
    const now = new Date();
    const day = now.getDay(); // 0=Sun
    const diff = (day === 0 ? -6 : 1 - day);
    const monday = new Date(now);
    monday.setDate(now.getDate() + diff + offset * 7);
    monday.setHours(0, 0, 0, 0);
    return monday;
  }

  private toDateStr(d: Date): string {
    // Lokális dátumot adunk vissza, nem UTC-t (toISOString UTC-t ad!)
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  private normalizeISO(iso: string): string {
    // '2026-02-25 09:00:00'      → '2026-02-25T09:00:00'
    // '2026-02-25T09:00:00+01:00' → '2026-02-25T09:00:00'
    return iso.replace(' ', 'T').slice(0, 19);
  }

  formatTime(iso: string): string {
    return iso.slice(11, 16); // 'HH:MM'
  }

  goBack(): void {
    if (this.step > 1) this.step--;
  }
}


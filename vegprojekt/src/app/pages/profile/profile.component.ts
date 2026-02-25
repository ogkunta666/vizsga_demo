import { Component, OnInit, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { User } from '../../models/user.model';
import { Booking } from '../../models/booking.model';
import { environment } from '../../../environments/environment';

interface BarberProfile {
  id: number;
  name: string;
  specialization?: string;
}

interface EditForm {
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  start_at: string;
  duration_min: number;
  note: string;
  status: string;
}

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [RouterLink, FormsModule],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.css'
})
export class ProfileComponent implements OnInit {
  private http = inject(HttpClient);
  private readonly API = environment.apiUrl;

  user: User | null = null;
  bookings: Booking[] = [];
  loading = true;
  loadingBookings = true;
  error: string | null = null;

  // Borbély adatok (ha role === 'barber')
  barberProfile: BarberProfile | null = null;
  barberBookings: Booking[] = [];
  loadingBarberBookings = false;

  // Szerkesztő modal
  editingBooking: Booking | null = null;
  editForm: EditForm = this.emptyForm();
  saving = false;
  saveError: string | null = null;
  saveSuccess = false;

  // Törlés megerősítés
  deletingId: number | null = null;

  ngOnInit(): void {
    this.http.get<User>(`${this.API}/auth/me`).subscribe({
      next: (data) => {
        this.user = data;
        this.loading = false;
        this.loadBookings();
        if (data.role === 'barber' || data.role === 'admin') {
          this.loadBarberData();
        }
      },
      error: () => {
        this.error = 'Nem sikerült betölteni a profil adatokat.';
        this.loading = false;
        this.loadingBookings = false;
      }
    });
  }

  // ── Saját foglalások (vendégként) ────────────────────────────────
  private loadBookings(): void {
    this.http.get<Booking[]>(`${this.API}/bookings`).subscribe({
      next: (data) => {
        this.bookings = data.sort((a, b) =>
          new Date(a.start_at).getTime() - new Date(b.start_at).getTime()
        );
        this.loadingBookings = false;
      },
      error: () => { this.loadingBookings = false; }
    });
  }

  // ── Borbély profil + foglalások ──────────────────────────────────
  private loadBarberData(): void {
    this.loadingBarberBookings = true;
    this.http.get<BarberProfile>(`${this.API}/barber/me`).subscribe({
      next: (b) => {
        this.barberProfile = b;
        this.loadBarberBookings();
      },
      error: () => { this.loadingBarberBookings = false; }
    });
  }

  private loadBarberBookings(): void {
    this.http.get<Booking[]>(`${this.API}/barber/bookings`).subscribe({
      next: (data) => {
        this.barberBookings = data.sort((a, b) =>
          new Date(a.start_at).getTime() - new Date(b.start_at).getTime()
        );
        this.loadingBarberBookings = false;
      },
      error: () => { this.loadingBarberBookings = false; }
    });
  }

  // ── Szerkesztés ──────────────────────────────────────────────────
  openEdit(b: Booking): void {
    this.editingBooking = b;
    this.saveError = null;
    this.saveSuccess = false;
    this.editForm = {
      customer_name:  b.customer_name,
      customer_email: b.customer_email,
      customer_phone: b.customer_phone,
      start_at:       b.start_at.slice(0, 19).replace(' ', 'T'),
      duration_min:   b.duration_min,
      note:           b.note ?? '',
      status:         b.status,
    };
  }

  closeEdit(): void {
    this.editingBooking = null;
    this.saveError = null;
    this.saveSuccess = false;
  }

  saveEdit(): void {
    if (!this.editingBooking) return;
    this.saving = true;
    this.saveError = null;
    this.saveSuccess = false;

    this.http.put<{ booking: Booking }>(
      `${this.API}/barber/bookings/${this.editingBooking.id}`,
      this.editForm
    ).subscribe({
      next: (res) => {
        const idx = this.barberBookings.findIndex(b => b.id === res.booking.id);
        if (idx !== -1) this.barberBookings[idx] = res.booking;
        this.saving = false;
        this.saveSuccess = true;
        setTimeout(() => this.closeEdit(), 1200);
      },
      error: (err) => {
        this.saving = false;
        this.saveError = err?.error?.message || 'Sikertelen mentés.';
      }
    });
  }

  // ── Törlés (lemondás) ────────────────────────────────────────────
  confirmDelete(id: number): void {
    this.deletingId = id;
  }

  cancelDelete(): void {
    this.deletingId = null;
  }

  doDelete(id: number): void {
    this.http.delete(`${this.API}/barber/bookings/${id}`).subscribe({
      next: () => {
        this.barberBookings = this.barberBookings.map(b =>
          b.id === id ? { ...b, status: 'cancelled' } : b
        );
        this.deletingId = null;
      },
      error: () => { this.deletingId = null; }
    });
  }

  // ── Helpers ──────────────────────────────────────────────────────
  formatDateTime(iso: string): { date: string; time: string } {
    const d = new Date(iso);
    return {
      date: d.toLocaleDateString('hu-HU', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' }),
      time: d.toLocaleTimeString('hu-HU', { hour: '2-digit', minute: '2-digit' })
    };
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      confirmed: 'Visszaigazolt', pending: 'Függőben',
      cancelled: 'Lemondva',      completed: 'Teljesített'
    };
    return map[status] ?? status;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      confirmed: 'status--confirmed', pending:   'status--pending',
      cancelled: 'status--cancelled', completed: 'status--completed'
    };
    return map[status] ?? '';
  }

  private emptyForm(): EditForm {
    return { customer_name: '', customer_email: '', customer_phone: '',
             start_at: '', duration_min: 30, note: '', status: 'confirmed' };
  }

  get upcomingBookings(): Booking[] {
    const now = new Date();
    return this.bookings.filter(b => new Date(b.start_at) >= now && b.status !== 'cancelled');
  }

  get pastBookings(): Booking[] {
    const now = new Date();
    return this.bookings.filter(b => new Date(b.start_at) < now || b.status === 'cancelled');
  }

  get barberUpcoming(): Booking[] {
    const now = new Date();
    return this.barberBookings.filter(b => new Date(b.start_at) >= now && b.status !== 'cancelled');
  }

  get barberPast(): Booking[] {
    const now = new Date();
    return this.barberBookings.filter(b => new Date(b.start_at) < now || b.status === 'cancelled');
  }
}

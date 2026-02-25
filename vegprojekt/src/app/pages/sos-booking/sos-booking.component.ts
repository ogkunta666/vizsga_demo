import { Component, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../core/services/auth.service';
import { Barber } from '../../models/barber.model';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-sos-booking',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './sos-booking.component.html',
  styleUrl: './sos-booking.component.css'
})
export class SosBookingComponent {
  private http   = inject(HttpClient);
  private auth   = inject(AuthService);
  private router = inject(Router);
  private readonly API = environment.apiUrl;

  // Dátum opciók: ma / holnap / holnapután
  readonly dateOptions = this.buildDateOptions();

  selectedDate: string = this.dateOptions[0].value;
  selectedPeriod: 'morning' | 'afternoon' = 'morning';

  submitting = false;
  error: string | null = null;

  // ── Dátum opciók generálása ─────────────────────────────────────
  private buildDateOptions(): { label: string; value: string }[] {
    const opts = [];
    const labels = ['Ma', 'Holnap', 'Holnapután'];
    for (let i = 0; i < 3; i++) {
      const d = new Date();
      d.setDate(d.getDate() + i);
      opts.push({ label: labels[i], value: this.toDateStr(d) });
    }
    return opts;
  }

  // ── Foglalás küldése ────────────────────────────────────────────
  bookNow(): void {
    const user = this.auth.getCurrentUser();
    if (!user) { this.router.navigate(['/login']); return; }

    this.submitting = true;
    this.error = null;

    // 1) Barbers lekérése
    this.http.get<Barber[]>(`${this.API}/barbers`).subscribe({
      next: barbers => {
        if (!barbers.length) {
          this.error = 'Nem található elérhető borbély.';
          this.submitting = false;
          return;
        }

        // 2) Szabad slotok lekérése az összes borbélyhoz az adott napra
        const dateFrom = this.selectedDate;
        const dateTo   = this.selectedDate;

        // Időhatárok a kiválasztott időszak szerint
        const hourMin = this.selectedPeriod === 'morning' ? 9  : 12;
        const hourMax = this.selectedPeriod === 'morning' ? 12 : 18;

        // Minden borbély elérhetőségét párhuzamosan kérjük le
        let completed = 0;
        const allFree: { barberId: number | string; slot: string }[] = [];

        for (const barber of barbers) {
          this.http.get<{ slots: string[] }>(
            `${this.API}/availability?barberId=${barber.id}&dateFrom=${dateFrom}&dateTo=${dateTo}`
          ).subscribe({
            next: res => {
              const freeSlots = res.slots
                .map(s => this.normalizeISO(s))
                .filter(s => {
                  const hour = parseInt(s.slice(11, 13), 10);
                  return hour >= hourMin && hour < hourMax;
                });

              for (const slot of freeSlots) {
                allFree.push({ barberId: barber.id, slot });
              }

              completed++;
              if (completed === barbers.length) {
                this.pickAndBook(allFree, user);
              }
            },
            error: () => {
              completed++;
              if (completed === barbers.length) {
                this.pickAndBook(allFree, user);
              }
            }
          });
        }
      },
      error: () => {
        this.error = 'Nem sikerült betölteni a borbélyokat.';
        this.submitting = false;
      }
    });
  }

  private pickAndBook(
    allFree: { barberId: number | string; slot: string }[],
    user: any
  ): void {
    if (!allFree.length) {
      this.error = 'Nincs szabad időpont a kiválasztott időszakban. Próbálj másik napot vagy időszakot!';
      this.submitting = false;
      return;
    }

    // Véletlenszerű szabad slot kiválasztása
    const pick = allFree[Math.floor(Math.random() * allFree.length)];

    const payload = {
      barber_id:      pick.barberId,
      customer_name:  user.name,
      customer_email: user.email,
      customer_phone: user.phone || '06000000000',
      start_at:       pick.slot,
      duration_min:   30,
      note:           'SOS foglalás'
    };

    this.http.post(`${this.API}/bookings`, payload).subscribe({
      next: () => this.router.navigate(['/booking-success']),
      error: err => {
        this.submitting = false;
        this.error = err?.error?.message || 'Sikertelen foglalás. Próbáld újra.';
      }
    });
  }

  // ── Helpers ─────────────────────────────────────────────────────
  private toDateStr(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  private normalizeISO(iso: string): string {
    return iso.replace(' ', 'T').slice(0, 19);
  }

  goBack(): void {
    this.router.navigate(['/']);
  }
}

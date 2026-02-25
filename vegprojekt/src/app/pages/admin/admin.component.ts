import { Component, OnInit, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { TitleCasePipe } from '@angular/common';
import { environment } from '../../../environments/environment';

type Section = 'bookings' | 'barbers' | 'hairstyles' | 'gallery' | 'users';

interface Booking {
  id: number; barber_id: number; customer_name: string;
  customer_email: string; customer_phone: string;
  start_at: string; duration_min: number; status: string; note?: string;
}
interface Barber {
  id: number; name: string; specialization?: string; bio?: string; photo_url?: string;
}
interface Hairstyle {
  id: number; name: string; description?: string; price_from?: number;
}
interface GalleryImage {
  id: number; title?: string; image_url: string; source?: string;
}
interface User {
  id: number; name: string; email: string; role: string;
}

@Component({
  selector: 'app-admin',
  standalone: true,
  imports: [FormsModule, TitleCasePipe],
  templateUrl: './admin.component.html',
  styleUrl: './admin.component.css'
})
export class AdminComponent implements OnInit {
  private http = inject(HttpClient);
  private readonly API = environment.apiUrl;

  activeSection: Section = 'bookings';

  // Data
  bookings: Booking[] = [];
  barbers: Barber[] = [];
  hairstyles: Hairstyle[] = [];
  gallery: GalleryImage[] = [];
  users: User[] = [];

  loading = false;
  error: string | null = null;
  successMsg: string | null = null;

  // Modal
  modalOpen = false;
  modalMode: 'create' | 'edit' = 'create';
  editingId: number | null = null;
  formData: Record<string, any> = {};

  ngOnInit(): void {
    this.load('bookings');
  }

  setSection(s: Section): void {
    this.activeSection = s;
    this.closeModal();
    this.error = null;
    this.successMsg = null;
    this.load(s);
  }

  private load(s: Section): void {
    this.loading = true;
    const map: Record<Section, string> = {
      bookings: '/bookings', barbers: '/barbers',
      hairstyles: '/hairstyles', gallery: '/gallery', users: '/users'
    };
    this.http.get<any[]>(`${this.API}${map[s]}`).subscribe({
      next: data => {
        (this as any)[s] = data;
        this.loading = false;
      },
      error: () => { this.error = 'Betöltési hiba.'; this.loading = false; }
    });
  }

  openCreate(): void {
    this.modalMode = 'create';
    this.editingId = null;
    this.formData = this.defaultForm();
    this.modalOpen = true;
  }

  openEdit(item: any): void {
    this.modalMode = 'edit';
    this.editingId = item.id;
    this.formData = { ...item };
    this.modalOpen = true;
  }

  closeModal(): void {
    this.modalOpen = false;
    this.formData = {};
    this.editingId = null;
  }

  save(): void {
    const map: Record<Section, string> = {
      bookings: '/bookings', barbers: '/barbers',
      hairstyles: '/hairstyles', gallery: '/gallery', users: '/users'
    };
    const base = `${this.API}${map[this.activeSection]}`;
    const req = this.modalMode === 'create'
      ? this.http.post(base, this.formData)
      : this.http.put(`${base}/${this.editingId}`, this.formData);

    req.subscribe({
      next: () => {
        this.successMsg = this.modalMode === 'create' ? 'Sikeresen létrehozva.' : 'Sikeresen frissítve.';
        this.closeModal();
        this.load(this.activeSection);
      },
      error: err => {
        this.error = err?.error?.message || 'Hiba történt.';
      }
    });
  }

  delete(id: number): void {
    if (!confirm('Biztosan törölni szeretnéd?')) return;
    const map: Record<Section, string> = {
      bookings: '/bookings', barbers: '/barbers',
      hairstyles: '/hairstyles', gallery: '/gallery', users: '/users'
    };
    this.http.delete(`${this.API}${map[this.activeSection]}/${id}`).subscribe({
      next: () => {
        this.successMsg = 'Sikeresen törölve.';
        this.load(this.activeSection);
      },
      error: err => { this.error = err?.error?.message || 'Törlési hiba.'; }
    });
  }

  private defaultForm(): Record<string, any> {
    switch (this.activeSection) {
      case 'barbers':    return { name: '', specialization: '', bio: '', photo_url: '' };
      case 'hairstyles': return { name: '', description: '', price_from: 0 };
      case 'gallery':    return { title: '', image_url: '', source: '' };
      case 'users':      return { name: '', email: '', role: 'user', password: '' };
      default:           return {};
    }
  }

  get fields(): { key: string; label: string; type: string }[] {
    switch (this.activeSection) {
      case 'barbers':    return [
        { key: 'name', label: 'Név', type: 'text' },
        { key: 'specialization', label: 'Szakterület', type: 'text' },
        { key: 'bio', label: 'Bemutatkozás', type: 'textarea' },
        { key: 'photo_url', label: 'Fotó URL', type: 'text' },
      ];
      case 'hairstyles': return [
        { key: 'name', label: 'Megnevezés', type: 'text' },
        { key: 'description', label: 'Leírás', type: 'textarea' },
        { key: 'price_from', label: 'Ár (Ft-tól)', type: 'number' },
      ];
      case 'gallery': return [
        { key: 'title', label: 'Cím', type: 'text' },
        { key: 'image_url', label: 'Kép URL', type: 'text' },
        { key: 'source', label: 'Forrás', type: 'text' },
      ];
      case 'users': return [
        { key: 'name', label: 'Név', type: 'text' },
        { key: 'email', label: 'E-mail', type: 'text' },
        { key: 'role', label: 'Szerep (user/admin/barber)', type: 'text' },
        { key: 'password', label: 'Jelszó (üresen hagyva nem változik)', type: 'password' },
      ];
      default: return [];
    }
  }

  columns(): string[] {
    switch (this.activeSection) {
      case 'bookings':   return ['ID', 'Ügyfél', 'E-mail', 'Időpont', 'Időtartam', 'Státusz'];
      case 'barbers':    return ['ID', 'Név', 'Szakterület', 'Bio'];
      case 'hairstyles': return ['ID', 'Megnevezés', 'Leírás', 'Ár'];
      case 'gallery':    return ['ID', 'Cím', 'URL', 'Forrás'];
      case 'users':      return ['ID', 'Név', 'E-mail', 'Szerep'];
    }
  }

  rowValues(item: any): string[] {
    switch (this.activeSection) {
      case 'bookings':   return [item.id, item.customer_name, item.customer_email, item.start_at?.slice(0,16).replace('T',' '), item.duration_min + ' perc', item.status];
      case 'barbers':    return [item.id, item.name, item.specialization ?? '—', (item.bio ?? '').slice(0, 50)];
      case 'hairstyles': return [item.id, item.name, (item.description ?? '').slice(0, 50), item.price_from != null ? item.price_from + ' Ft' : '—'];
      case 'gallery':    return [item.id, item.title ?? '—', item.image_url?.slice(0, 40), item.source ?? '—'];
      case 'users':      return [item.id, item.name, item.email, item.role];
    }
  }

  get currentList(): any[] {
    return (this as any)[this.activeSection] ?? [];
  }

  canCreate(): boolean {
    return this.activeSection !== 'bookings';
  }
}


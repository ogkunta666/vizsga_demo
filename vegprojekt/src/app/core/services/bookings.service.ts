import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
import { Booking } from '../../models/booking.model';

@Injectable({
  providedIn: 'root'
})
export class BookingsService {
  constructor(private api: ApiService) {}

  getAll() {
    return this.api.get<Booking[]>('bookings');
  }

  getById(id: string) {
    return this.api.get<Booking>(`bookings/${id}`);
  }

  create(booking: Partial<Booking>) {
    return this.api.post<Booking>('bookings', booking);
  }

  cancel(id: string) {
    return this.api.delete(`bookings/${id}`);
  }
}

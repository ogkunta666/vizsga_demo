import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
import { Barber } from '../../models/barber.model';

@Injectable({
  providedIn: 'root'
})
export class BarbersService {
  constructor(private api: ApiService) {}

  getAll() {
    return this.api.get<Barber[]>('barbers');
  }

  getById(id: string) {
    return this.api.get<Barber>(`barbers/${id}`);
  }
}

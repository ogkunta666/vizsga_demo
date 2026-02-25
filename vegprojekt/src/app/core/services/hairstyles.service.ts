import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
import { Hairstyle } from '../../models/hairstyle.model';

@Injectable({
  providedIn: 'root'
})
export class HairstylesService {
  constructor(private api: ApiService) {}

  getAll() {
    return this.api.get<Hairstyle[]>('hairstyles');
  }

  getById(id: string) {
    return this.api.get<Hairstyle>(`hairstyles/${id}`);
  }
}

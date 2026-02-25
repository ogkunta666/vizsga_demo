import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { GalleryImage } from '../../models/gallery.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class GalleryService {
  private readonly API = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getAll() {
    return this.http.get<GalleryImage[]>(`${this.API}/gallery`);
  }
}

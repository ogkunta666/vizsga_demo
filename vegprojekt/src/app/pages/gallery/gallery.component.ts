import { Component, inject, OnInit } from '@angular/core';
import { GalleryService } from '../../core/services/gallery.service';
import { GalleryImage } from '../../models/gallery.model';

@Component({
  selector: 'app-gallery',
  standalone: true,
  templateUrl: './gallery.component.html',
  styleUrl: './gallery.component.css'
})
export class GalleryComponent implements OnInit {
  private galleryService = inject(GalleryService);

  images: GalleryImage[] = [];
  loading = true;
  error = '';

  ngOnInit() {
    this.galleryService.getAll().subscribe({
      next: (data) => {
        this.images = data;
        this.loading = false;
      },
      error: () => {
        this.error = 'Nem sikerült betölteni a képeket.';
        this.loading = false;
      }
    });
  }
}


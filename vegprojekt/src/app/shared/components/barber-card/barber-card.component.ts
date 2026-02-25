import { Component, Input } from '@angular/core';
import { Barber } from '../../../models/barber.model';

@Component({
  selector: 'app-barber-card',
  standalone: true,
  templateUrl: './barber-card.component.html',
  styleUrl: './barber-card.component.css'
})
export class BarberCardComponent {
  @Input() barber!: Barber;
}

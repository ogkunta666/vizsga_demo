import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './register.component.html',
  styleUrl: './register.component.css'
})
export class RegisterComponent {
  private auth = inject(AuthService);
  private router = inject(Router);

  name = '';
  email = '';
  password = '';
  passwordConfirm = '';
  loading = false;
  error = '';

  onSubmit() {
    if (!this.name || !this.email || !this.password || !this.passwordConfirm) {
      this.error = 'Kérlek töltsd ki az összes mezőt.';
      return;
    }
    if (this.password !== this.passwordConfirm) {
      this.error = 'A két jelszó nem egyezik.';
      return;
    }

    this.loading = true;
    this.error = '';

    this.auth.register({
      name: this.name,
      email: this.email,
      password: this.password,
      password_confirmation: this.passwordConfirm
    }).subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: (err) => {
        this.loading = false;
        const msg = err?.error?.message || err?.error?.error;
        const errors = err?.error?.errors;
        if (errors) {
          this.error = Object.values(errors).flat().join(' ');
        } else {
          this.error = msg || 'Sikertelen regisztráció. Próbáld újra.';
        }
      }
    });
  }
}


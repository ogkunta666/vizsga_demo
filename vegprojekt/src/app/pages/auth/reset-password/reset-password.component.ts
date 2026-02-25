import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.css'
})
export class ResetPasswordComponent {
  password = '';
  passwordConfirm = '';
  loading = false;
  error = '';
  success = false;

  onSubmit() {
    if (!this.password || !this.passwordConfirm) {
      this.error = 'Kérlek töltsd ki az összes mezőt.';
      return;
    }
    if (this.password !== this.passwordConfirm) {
      this.error = 'A két jelszó nem egyezik.';
      return;
    }
    this.loading = true;
    this.error = '';
    // TODO: AuthService.resetPassword()
    this.success = true;
    this.loading = false;
  }
}

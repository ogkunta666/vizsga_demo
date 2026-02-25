import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [FormsModule, RouterLink],
  templateUrl: './forgot-password.component.html',
  styleUrl: './forgot-password.component.css'
})
export class ForgotPasswordComponent {
  email = '';
  loading = false;
  error = '';
  success = false;

  onSubmit() {
    if (!this.email) {
      this.error = 'Kérlek add meg az e-mail címed.';
      return;
    }
    this.loading = true;
    this.error = '';
    // TODO: AuthService.forgotPassword()
    this.success = true;
    this.loading = false;
  }
}

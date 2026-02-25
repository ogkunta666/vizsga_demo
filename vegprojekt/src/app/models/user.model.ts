export interface User {
  id: string;
  name: string;
  email: string;
  phone: string;
  role: 'user' | 'admin' | 'barber';
  profileImage?: string;
  createdAt: string;
}

export interface Booking {
  id: number;
  barber_id: number;
  user_id: number | null;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  start_at: string;
  duration_min: number;
  note?: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  created_at: string;
  updated_at: string;
  barber?: {
    id: number;
    name: string;
    specialization?: string;
    photo_url?: string;
  };
}

<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\GalleryImage;
use App\Models\Hairstyle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;



    private function actingAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        return $admin;
    }

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        return $user;
    }

    private function createBarber(array $attrs = []): Barber
    {
        return Barber::create(array_merge([
            'name' => 'Teszt Borbély',
            'specialization' => 'Fade',
            'bio' => 'Bio szöveg',
            'photo_url' => 'https://example.com/photo.jpg',
        ], $attrs));
    }

    private function futureDateTime(int $daysAhead = 1, int $hour = 10): string
    {
        return Carbon::now()->addDays($daysAhead)->setTime($hour, 0)->format('Y-m-d\TH:i:s');
    }



    public function test_ping_visszaad_pongot(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'pong']);
    }



    public function test_sikeres_regisztracio(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Teszt Elek',
            'email' => 'teszt@pelda.hu',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'message']);

        $this->assertDatabaseHas('users', ['email' => 'teszt@pelda.hu']);
    }

    public function test_regisztracio_duplikat_email_megtagadva(): void
    {
        User::factory()->create(['email' => 'dupla@pelda.hu']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Dupla User',
            'email' => 'dupla@pelda.hu',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_regisztracio_rovid_jelszo_megtagadva(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Teszt',
            'email' => 'rovid@pelda.hu',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_regisztracio_nem_egyezo_jelszo_megtagadva(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Teszt',
            'email' => 'nem@egyezo.hu',
            'password' => 'Secret123!',
            'password_confirmation' => 'Masik123!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }


    public function test_sikeres_bejelentkezes_tokent_ad_vissza(): void
    {
        User::factory()->create([
            'email' => 'login@pelda.hu',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@pelda.hu',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user'])
                 ->assertJson(['message' => 'Bejelentkezve']);
    }

    public function test_hibas_jelszovel_bejelentkezes_megtagadva(): void
    {
        User::factory()->create(['email' => 'valaki@pelda.hu']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'valaki@pelda.hu',
            'password' => 'RosszJelszo!',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_endpoint_visszaadja_a_felhasznalot(): void
    {
        $user = $this->actingAsUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_me_endpoint_auth_nelkul_megtagadva(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_kijelentkezes_torolli_a_tokent(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Kijelentkezve']);
    }



    public function test_borbely_lista_publikusan_elerheto(): void
    {
        $this->createBarber(['name' => 'Kiss Bence']);
        $this->createBarber(['name' => 'Nagy Ádám']);

        $response = $this->getJson('/api/barbers');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_borbely_reszlet_publikusan_elerheto(): void
    {
        $barber = $this->createBarber(['name' => 'Kiss Bence']);

        $response = $this->getJson("/api/barbers/{$barber->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Kiss Bence']);
    }

    public function test_borbely_letrehozas_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/barbers', [
                             'name' => 'Új Borbély',
                             'specialization' => 'Pompadour',
                             'bio' => 'Bio szöveg',
                             'photo_url' => 'https://example.com/p.jpg',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Új Borbély']);

        $this->assertDatabaseHas('barbers', ['name' => 'Új Borbély']);
    }

    public function test_borbely_letrehozas_usernek_megtagadva(): void
    {
        $user = $this->actingAsUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/barbers', [
                             'name' => 'Tiltott Borbély',
                             'specialization' => 'Fade',
                         ]);

        $response->assertStatus(403);
    }

    public function test_borbely_letrehozas_auth_nelkul_megtagadva(): void
    {
        $response = $this->postJson('/api/barbers', [
            'name' => 'Tiltott',
            'specialization' => 'Fade',
        ]);

        $response->assertStatus(401);
    }

    public function test_borbely_modositas_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();
        $barber = $this->createBarber();

        $response = $this->actingAs($admin, 'sanctum')
                         ->putJson("/api/barbers/{$barber->id}", [
                             'name' => 'Módosított Név',
                             'specialization' => 'Szakáll',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Módosított Név']);

        $this->assertDatabaseHas('barbers', ['id' => $barber->id, 'name' => 'Módosított Név']);
    }

    public function test_borbely_torlese_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();
        $barber = $this->createBarber();

        $response = $this->actingAs($admin, 'sanctum')
                         ->deleteJson("/api/barbers/{$barber->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('barbers', ['id' => $barber->id]);
    }

    public function test_borbely_next_slot_visszaad_idopontot(): void
    {
        $barber = $this->createBarber();

        $response = $this->getJson("/api/barbers/{$barber->id}/next-slot");

        $response->assertStatus(200)
                 ->assertJsonStructure(['barber_id', 'next_slot']);
    }

    public function test_borbely_schedule_visszaadja_a_foglalasokat(): void
    {
        $barber = $this->createBarber();

        $response = $this->getJson("/api/barbers/{$barber->id}/schedule?dateFrom=2026-03-01&dateTo=2026-03-07");

        $response->assertStatus(200)
                 ->assertJsonStructure(['barber_id', 'range' => ['from', 'to'], 'booked']);
    }

    public function test_borbely_schedule_ervenytelen_datum_validacios_hiba(): void
    {
        $barber = $this->createBarber();

        $response = $this->getJson("/api/barbers/{$barber->id}/schedule?dateFrom=nem-datum&dateTo=2026-03-07");

        $response->assertStatus(422);
    }



    public function test_sikeres_foglalas_letrehozas(): void
    {
        $barber = $this->createBarber();
        $startAt = $this->futureDateTime(3, 10);

        $response = $this->postJson('/api/bookings', [
            'barber_id' => $barber->id,
            'customer_name' => 'Teszt Elek',
            'customer_email' => 'teszt@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => $startAt,
            'duration_min' => 30,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['booking' => ['id', 'barber_id', 'status']])
                 ->assertJsonFragment(['status' => 'confirmed']);

        $this->assertDatabaseHas('bookings', [
            'customer_email' => 'teszt@pelda.hu',
            'status' => 'confirmed',
        ]);
    }

    public function test_utkozo_foglalas_409_hibat_ad(): void
    {
        $barber = $this->createBarber();
        $startAt = $this->futureDateTime(3, 10);

     
        $this->postJson('/api/bookings', [
            'barber_id' => $barber->id,
            'customer_name' => 'Első Vendég',
            'customer_email' => 'elso@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => $startAt,
            'duration_min' => 30,
        ]);

        
        $response = $this->postJson('/api/bookings', [
            'barber_id' => $barber->id,
            'customer_name' => 'Második Vendég',
            'customer_email' => 'masodik@pelda.hu',
            'customer_phone' => '+36309876543',
            'start_at' => $startAt,
            'duration_min' => 30,
        ]);

        $response->assertStatus(409)
                 ->assertJsonFragment(['message' => 'Ez az időpont már foglalt.']);
    }

    public function test_foglalas_multra_422_hibat_ad(): void
    {
        $barber = $this->createBarber();

        $response = $this->postJson('/api/bookings', [
            'barber_id' => $barber->id,
            'customer_name' => 'Teszt',
            'customer_email' => 'teszt@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => '2020-01-01T10:00:00',
            'duration_min' => 30,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['start_at']);
    }

    public function test_foglalas_nem_letezo_borbely_422_hibat_ad(): void
    {
        $response = $this->postJson('/api/bookings', [
            'barber_id' => 9999,
            'customer_name' => 'Teszt',
            'customer_email' => 'teszt@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => $this->futureDateTime(3, 10),
            'duration_min' => 30,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['barber_id']);
    }

    public function test_foglalasok_listaja_adminnak_mindent_mutat(): void
    {
        $admin = $this->actingAsAdmin();
        $barber = $this->createBarber();

        Booking::create([
            'barber_id' => $barber->id,
            'customer_name' => 'Valaki',
            'customer_email' => 'valaki@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => $this->futureDateTime(5, 10),
            'duration_min' => 30,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson('/api/bookings');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    public function test_foglalas_lemondas_statuszt_cancelled_re_allitja(): void
    {
        $user = $this->actingAsUser();
        $barber = $this->createBarber();

        $booking = Booking::create([
            'barber_id' => $barber->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '+36301234567',
            'start_at' => $this->futureDateTime(5, 10),
            'duration_min' => 30,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($user, 'sanctum')
                         ->deleteJson("/api/bookings/{$booking->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Foglalás lemondva.']);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_availability_visszaad_szabad_idopontokat(): void
    {
        $barber = $this->createBarber();

        $response = $this->getJson("/api/availability?dateFrom=2026-03-01&dateTo=2026-03-01&barberId={$barber->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['range' => ['from', 'to'], 'barber_id', 'slots']);
    }

    public function test_availability_foglalas_utan_nem_szerepel_a_sloton(): void
    {
        $barber = $this->createBarber();
        $date = Carbon::now()->addDays(5)->format('Y-m-d');
        $startAt = Carbon::now()->addDays(5)->setTime(10, 0)->format('Y-m-d\TH:i:s');

      
        Booking::create([
            'barber_id' => $barber->id,
            'customer_name' => 'Valaki',
            'customer_email' => 'valaki@pelda.hu',
            'customer_phone' => '+36301234567',
            'start_at' => $startAt,
            'duration_min' => 30,
            'status' => 'confirmed',
        ]);

        $response = $this->getJson("/api/availability?dateFrom={$date}&dateTo={$date}&barberId={$barber->id}");

        $slots = $response->json('slots');
        $this->assertNotContains(
            Carbon::now()->addDays(5)->setTime(10, 0)->toIso8601String(),
            $slots
        );
    }

  

    public function test_hajstilus_lista_publikusan_elerheto(): void
    {
        Hairstyle::create(['name' => 'Fade', 'description' => 'Leírás', 'price_from' => 3000]);
        Hairstyle::create(['name' => 'Pompadour', 'description' => 'Leírás', 'price_from' => 3500]);

        $response = $this->getJson('/api/hairstyles');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_hajstilus_reszlet_publikusan_elerheto(): void
    {
        $hairstyle = Hairstyle::create(['name' => 'Undercut', 'price_from' => 3000]);

        $response = $this->getJson("/api/hairstyles/{$hairstyle->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Undercut']);
    }

    public function test_hajstilus_letrehozas_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/hairstyles', [
                             'name' => 'Crew Cut',
                             'description' => 'Rövid katonai vágás',
                             'price_from' => 2500,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Crew Cut']);

        $this->assertDatabaseHas('hairstyles', ['name' => 'Crew Cut']);
    }

    public function test_hajstilus_letrehozas_usernek_megtagadva(): void
    {
        $user = $this->actingAsUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/hairstyles', [
                             'name' => 'Tiltott stílus',
                         ]);

        $response->assertStatus(403);
    }

    public function test_hajstilus_modositas_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();
        $hairstyle = Hairstyle::create(['name' => 'Régi Név', 'price_from' => 2000]);

        $response = $this->actingAs($admin, 'sanctum')
                         ->putJson("/api/hairstyles/{$hairstyle->id}", [
                             'name' => 'Új Név',
                             'price_from' => 2500,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Új Név']);
    }

    public function test_hajstilus_torlese_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();
        $hairstyle = Hairstyle::create(['name' => 'Törölni való', 'price_from' => 2000]);

        $response = $this->actingAs($admin, 'sanctum')
                         ->deleteJson("/api/hairstyles/{$hairstyle->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('hairstyles', ['id' => $hairstyle->id]);
    }


    public function test_galeria_lista_publikusan_elerheto(): void
    {
        GalleryImage::create(['title' => 'Fade', 'image_url' => 'https://example.com/img.jpg']);

        $response = $this->getJson('/api/gallery');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    public function test_galeria_kep_hozzaadasa_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin, 'sanctum')
                         ->postJson('/api/gallery', [
                             'title' => 'Új kép',
                             'image_url' => 'https://example.com/new.jpg',
                             'source' => 'Kiss Bence',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Új kép']);

        $this->assertDatabaseHas('gallery_images', ['title' => 'Új kép']);
    }

    public function test_galeria_kep_hozzaadasa_usernek_megtagadva(): void
    {
        $user = $this->actingAsUser();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/gallery', [
                             'title' => 'Tiltott kép',
                             'image_url' => 'https://example.com/img.jpg',
                         ]);

        $response->assertStatus(403);
    }

    public function test_galeria_kep_torlese_adminnak_mukodik(): void
    {
        $admin = $this->actingAsAdmin();
        $image = GalleryImage::create(['title' => 'Törlendő', 'image_url' => 'https://example.com/d.jpg']);

        $response = $this->actingAs($admin, 'sanctum')
                         ->deleteJson("/api/gallery/{$image->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
    }

    public function test_galeria_kep_torlese_auth_nelkul_megtagadva(): void
    {
        $image = GalleryImage::create(['title' => 'Kép', 'image_url' => 'https://example.com/x.jpg']);

        $response = $this->deleteJson("/api/gallery/{$image->id}");

        $response->assertStatus(401);
    }
}

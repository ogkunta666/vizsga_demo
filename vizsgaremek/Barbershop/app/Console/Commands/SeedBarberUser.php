<?php

namespace App\Console\Commands;

use App\Models\Barber;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedBarberUser extends Command
{
    protected $signature = 'app:seed-barber-user';
    protected $description = 'Barber role-ú userek létrehozása és hozzárendelése a Barber rekordokhoz';

    public function handle(): void
    {
        $barbers = Barber::all();

        foreach ($barbers as $barber) {
            if ($barber->user_id) {
                $this->info("Barber [{$barber->name}] már rendelkezik user_id-vel: {$barber->user_id}");
                continue;
            }

            $email = strtolower(str_replace(' ', '.', $barber->name)) . '@barbershop.hu';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $barber->name,
                    'password' => Hash::make('Barber1234!'),
                    'role'     => 'barber',
                ]
            );

            $barber->update(['user_id' => $user->id]);
            $this->info("Barber [{$barber->name}] → User [{$user->email}] (id:{$user->id})");
        }

        $this->info('Kész.');
    }
}


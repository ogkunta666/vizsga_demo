<?php
$u = \App\Models\User::create([
    'name' => 'Kiss Bence',
    'email' => 'bence@barbershop.hu',
    'password' => bcrypt('Barber1234!'),
    'role' => 'barber'
]);
\App\Models\Barber::where('id', 1)->update(['user_id' => $u->id]);
echo 'OK user_id=' . $u->id . PHP_EOL;

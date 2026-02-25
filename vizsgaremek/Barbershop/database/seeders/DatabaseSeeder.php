<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\GalleryImage;
use App\Models\Hairstyle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin és tesztfelhasználó ───────────────────────────────────
        User::create([
            'name' => 'Admin',
            'email' => 'admin@barbershop.hu',
            'password' => Hash::make('Admin1234!'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Teszt Felhasználó',
            'email' => 'user@barbershop.hu',
            'password' => Hash::make('User1234!'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // ─── Borbélyok ────────────────────────────────────────────────────
        $barbers = [
            [
                'name' => 'Kiss Bence',
                'specialization' => 'Fade',
                'bio' => '8 éves tapasztalattal rendelkező borbély, aki a modern fade vágásokra specializálódott. Versenydíjas fodrász.',
                'photo_url' => 'https://via.placeholder.com/400x400?text=Kiss+Bence',
            ],
            [
                'name' => 'Nagy Ádám',
                'specialization' => 'Szakáll formázás',
                'bio' => 'Szakáll és bajusz formázás mestere. 5+ éves szakmai tapasztalat, prémium szolgáltatások.',
                'photo_url' => 'https://via.placeholder.com/400x400?text=Nagy+Adam',
            ],
            [
                'name' => 'Csukodi Zoltán',
                'specialization' => 'Klasszikus vágás',
                'bio' => 'Hagyományos borbély módszerek és modern technikák ötvözése. Specialitás: barrel és pompadour.',
                'photo_url' => 'https://via.placeholder.com/400x400?text=Csukodi+Zoltan',
            ],
        ];

        foreach ($barbers as $barberData) {
            Barber::create($barberData);
        }

        // ─── Hajstílusok ─────────────────────────────────────────────────
        $hairstyles = [
            ['name' => 'Crew Cut', 'description' => 'Rövid, ápolt katonai stílusú vágás.', 'price_from' => 2500],
            ['name' => 'Skin Fade', 'description' => 'Kopaszra borotváltól fokozatosan hosszabbba átmenő stílus.', 'price_from' => 3500],
            ['name' => 'Pompadour', 'description' => 'Elöl magasan feltöltött, íves retró stílus.', 'price_from' => 3000],
            ['name' => 'Undercut', 'description' => 'Oldalt rövid, felül hosszú, határozott átmenettel.', 'price_from' => 3000],
            ['name' => 'Caesar Cut', 'description' => 'Horizontális frufruval jellemzett rövid vágás.', 'price_from' => 2500],
            ['name' => 'Szakáll formázás', 'description' => 'Teljes szakáll kontúrozás és ápolás.', 'price_from' => 1500],
            ['name' => 'Hajmosás + szárítás', 'description' => 'Prémium hajmosás professzionális termékekkel.', 'price_from' => 1000],
        ];

        foreach ($hairstyles as $hairstyleData) {
            Hairstyle::create($hairstyleData);
        }

        // ─── Galéria képek ───────────────────────────────────────────────
        $gallery = [
            ['title' => 'Skin Fade', 'image_url' => 'https://images.pexels.com/photos/12464841/pexels-photo-12464841.jpeg?_gl=1*394q1*_ga*MTI2MDM0MzM0MS4xNzcxOTIzNDU1*_ga_8JE65Q40S6*czE3NzE5MjM0NTQkbzEkZzEkdDE3NzE5MjM0NTckajU3JGwwJGgw', 'source' => 'Kiss Bence'],
            ['title' => 'Haj és Szakáll', 'image_url' => 'https://images.pexels.com/photos/7781848/pexels-photo-7781848.jpeg?_gl=1*yr8yo5*_ga*MTI2MDM0MzM0MS4xNzcxOTIzNDU1*_ga_8JE65Q40S6*czE3NzE5MjM0NTQkbzEkZzEkdDE3NzE5MjUwNzIkajU1JGwwJGgw', 'source' => 'Nagy Ádám'],
            ['title' => 'Undercut', 'image_url' => 'https://images.pexels.com/photos/4085475/pexels-photo-4085475.jpeg?_gl=1*gm21k2*_ga*MTI2MDM0MzM0MS4xNzcxOTIzNDU1*_ga_8JE65Q40S6*czE3NzE5MjM0NTQkbzEkZzEkdDE3NzE5MjUxMjgkajU5JGwwJGgw', 'source' => 'Csukodi Zoltán'],
        ];

        foreach ($gallery as $imageData) {
            GalleryImage::create($imageData);
        }
    }
}

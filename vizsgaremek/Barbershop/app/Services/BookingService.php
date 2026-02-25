<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class BookingService
{
    /**
     * Ellenőrzi, hogy az adott időpont szabad-e az adott borbélyhoz
     */
    public function isSlotAvailable(int $barberId, Carbon $startTime, int $durationMin = 30): bool
    {
        $endTime = $startTime->clone()->addMinutes($durationMin);

        // Keressen ütköző foglalásokat (db-független: PHP-ban számítjuk a végidőt)
        // Overlap: meglévő start_at < newEnd  ÉS  meglévő end > newStart
        // Meglévő end = start_at + duration_min perc  → PHP-ban előre lekérjük és szűrjük
        $conflictingBooking = Booking::where('barber_id', $barberId)
            ->where('status', 'confirmed')
            ->where('start_at', '<', $endTime)
            ->get()
            ->first(function ($booking) use ($startTime) {
                $existingEnd = Carbon::parse($booking->start_at)->addMinutes($booking->duration_min);
                return $existingEnd > $startTime;
            });

        return $conflictingBooking === null;
    }

    /**
     * Szabad idősávok listája az adott napra
     */
    public function getAvailableSlots(int $barberId, Carbon $date, int $slotDuration = 30): array
    {
        $workStart = 9;  // 9:00
        $workEnd = 18;   // 18:00
        $slots = [];

        $current = $date->clone()->setTime($workStart, 0);
        $endOfDay = $date->clone()->setTime($workEnd, 0);

        while ($current < $endOfDay) {
            if ($this->isSlotAvailable($barberId, $current, $slotDuration)) {
                $slots[] = $current->toIso8601String();
            }
            $current->addMinutes(15); // 15 perces intervallum
        }

        return $slots;
    }

    /**
     * Legkorábbi szabad idősáv
     */
    public function getNextAvailableSlot(int $barberId, int $slotDuration = 30): ?Carbon
    {
        $workStart = 9;
        $workEnd = 18;

        $startTime = now();
        $minutes = (int) $startTime->format('i');
        $rounded = (int) (ceil($minutes / 15) * 15);
        if ($rounded === 60) {
            $startTime->addHour()->setMinute(0)->setSecond(0);
        } else {
            $startTime->setMinute($rounded)->setSecond(0);
        }

        // Munkaidőn kívüli korrekció
        if ($startTime->hour >= $workEnd) {
            $startTime = $startTime->clone()->addDay()->setTime($workStart, 0);
        } elseif ($startTime->hour < $workStart) {
            $startTime->setTime($workStart, 0);
        }

        // Keressünk 100 iteráción belül (kb. 50 nap)
        for ($i = 0; $i < 100; $i++) {
            if ($this->isSlotAvailable($barberId, $startTime, $slotDuration)) {
                return $startTime;
            }

            $startTime = $startTime->addMinutes(15);

            // Ha túlment a munkaidőn, ugorjunk a következő napra
            if ($startTime->hour >= $workEnd) {
                $startTime = $startTime->clone()->addDay()->setTime($workStart, 0);
            }
        }

        return null;
    }
}

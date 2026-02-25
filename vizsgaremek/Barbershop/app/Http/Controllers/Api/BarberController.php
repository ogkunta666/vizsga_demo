<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BarberRequest;
use App\Models\Barber;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    public function index(): JsonResponse
    {
        $barbers = Barber::all();

        return response()->json($barbers);
    }

    public function show(Barber $barber): JsonResponse
    {
        return response()->json($barber);
    }

    public function store(BarberRequest $request): JsonResponse
    {
        $barber = Barber::create($request->validated());

        return response()->json([
            'message' => 'Borbély sikeresen létrehozva.',
            'barber' => $barber,
        ], 201);
    }

    public function update(BarberRequest $request, Barber $barber): JsonResponse
    {
        $barber->update($request->validated());

        return response()->json([
            'message' => 'Borbély frissítve.',
            'barber' => $barber,
        ]);
    }

    public function destroy(Barber $barber): JsonResponse
    {
        $barber->delete();

        return response()->json([
            'message' => 'Borbély törölve.',
        ]);
    }

    public function nextSlot(Barber $barber): JsonResponse
    {
        // Keressük az első szabad 30 perces sávot
        $slotDuration = 30;
        $workStart = 9; // 9:00
        $workEnd = 18;  // 18:00

        // Kerekítés felfelé a legközelebbi 15 perces intervallumra
        $startTime = now();
        $minutes = (int) $startTime->format('i');
        $rounded = (int) (ceil($minutes / 15) * 15);
        if ($rounded === 60) {
            $startTime->addHour()->setMinute(0)->setSecond(0);
        } else {
            $startTime->setMinute($rounded)->setSecond(0);
        }

        // Ha már munkaidőn túl vagyunk, kezdjük a holnap reggel
        if ($startTime->hour >= $workEnd) {
            $startTime = $startTime->clone()->addDay()->setTime($workStart, 0);
        }

        // Ha még munkaidő előtt vagyunk, kezdjük a munkaidő kezdetén
        if ($startTime->hour < $workStart) {
            $startTime->setTime($workStart, 0);
        }

        // Keressünk szabad sávot
        for ($i = 0; $i < 100; $i++) { // Max 100 iteráció (kb. 50 nap)
            $slotEnd = $startTime->clone()->addMinutes($slotDuration);

            // Ha az idősáv munkaidőn túlmegy, ugorjunk a következő napra
            if ($slotEnd->hour > $workEnd || ($slotEnd->hour == $workEnd && $slotEnd->minute > 0)) {
                $startTime = $startTime->clone()->addDay()->setTime($workStart, 0);
                continue;
            }

            // Ellenőrizzük, hogy az idősáv szabad-e
            $conflictingBooking = Booking::where('barber_id', $barber->id)
                ->where('status', 'confirmed')
                ->where('start_at', '<', $slotEnd)
                ->where('start_at', '>=', $startTime->copy()->subMinutes(60))
                ->first();

            if (!$conflictingBooking) {
                return response()->json([
                    'barber_id' => $barber->id,
                    'next_slot' => $startTime->toIso8601String(),
                ]);
            }

            $startTime = $startTime->addMinutes(15);
        }

        return response()->json([
            'message' => 'Nincs szabad idősáv.',
        ], 404);
    }

    public function schedule(Request $request, Barber $barber): JsonResponse
    {
        $request->validate([
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateTo' => 'required|date_format:Y-m-d|after_or_equal:dateFrom',
        ]);

        $dateFrom = Carbon::parse($request->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($request->dateTo)->endOfDay();

        $bookings = Booking::where('barber_id', $barber->id)
            ->where('status', 'confirmed')
            ->whereBetween('start_at', [$dateFrom, $dateTo])
            ->select('start_at', 'duration_min')
            ->get();

        return response()->json([
            'barber_id' => $barber->id,
            'range' => [
                'from' => $request->dateFrom,
                'to' => $request->dateTo,
            ],
            'booked' => $bookings,
        ]);
    }
}

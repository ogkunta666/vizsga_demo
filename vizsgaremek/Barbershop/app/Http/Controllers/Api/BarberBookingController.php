<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarberBookingController extends Controller
{
    /**
     * A bejelentkezett borbélyhoz tartozó Barber rekord megkeresése.
     */
    private function getBarberForUser(Request $request): ?Barber
    {
        return Barber::where('user_id', $request->user()->id)->first();
    }

    /**
     * GET /api/barber/me — A borbély saját Barber rekordját adja vissza.
     */
    public function me(Request $request): JsonResponse
    {
        $barber = $this->getBarberForUser($request);

        if (!$barber) {
            return response()->json(['message' => 'Nincs hozzárendelt borbély profil.'], 404);
        }

        return response()->json($barber);
    }

    /**
     * GET /api/barber/bookings — A borbélyhoz tartozó foglalások listája.
     */
    public function index(Request $request): JsonResponse
    {
        $barber = $this->getBarberForUser($request);

        if (!$barber) {
            return response()->json(['message' => 'Nincs hozzárendelt borbély profil.'], 404);
        }

        $bookings = Booking::where('barber_id', $barber->id)
            ->orderBy('start_at')
            ->get();

        return response()->json($bookings);
    }

    /**
     * PUT /api/barber/bookings/{booking} — Foglalás szerkesztése (csak a saját borbélyhoz tartozó).
     */
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $barber = $this->getBarberForUser($request);

        if (!$barber || $booking->barber_id !== $barber->id) {
            return response()->json(['message' => 'Hozzáférés megtagadva.'], 403);
        }

        $data = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|max:20',
            'start_at'       => 'required|date_format:Y-m-d\TH:i:s',
            'duration_min'   => 'integer|min:15|max:180',
            'note'           => 'nullable|string|max:1000',
            'status'         => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $booking->update($data);

        return response()->json([
            'message' => 'Foglalás frissítve.',
            'booking' => $booking->fresh(),
        ]);
    }

    /**
     * DELETE /api/barber/bookings/{booking} — Foglalás lemondása (csak a saját borbélyhoz tartozó).
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $barber = $this->getBarberForUser($request);

        if (!$barber || $booking->barber_id !== $barber->id) {
            return response()->json(['message' => 'Hozzáférés megtagadva.'], 403);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Foglalás lemondva.']);
    }
}

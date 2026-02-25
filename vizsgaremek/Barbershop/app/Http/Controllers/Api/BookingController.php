<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingCreateRequest;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Barber;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user() && $request->user()->role === 'admin') {
            // Admin látja az összes foglalást
            $bookings = Booking::with('barber')->orderBy('start_at')->get();
        } else {
            // Felhasználó csak a sajátját
            $bookings = Booking::where('customer_email', $request->user()->email ?? null)
                ->orWhere('user_id', $request->user()->id ?? null)
                ->with('barber')
                ->orderBy('start_at')
                ->get();
        }

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load('barber'));
    }

    public function store(BookingCreateRequest $request): JsonResponse
    {
        $startTime = Carbon::parse($request->start_at);
        $durationMin = $request->duration_min ?? 30;

        return DB::transaction(function () use ($request, $startTime, $durationMin) {
            $endTime = $startTime->clone()->addMinutes($durationMin);

            // Pesszimista zárolás párhuzamos kérések ellen (race condition)
            // Overlap check: PHP-ban számítjuk a végidőt, db-független
            $conflict = Booking::where('barber_id', $request->barber_id)
                ->where('status', 'confirmed')
                ->where('start_at', '<', $endTime)
                ->lockForUpdate()
                ->get()
                ->first(function ($booking) use ($startTime) {
                    $existingEnd = \Carbon\Carbon::parse($booking->start_at)->addMinutes($booking->duration_min);
                    return $existingEnd > $startTime;
                });

            if ($conflict) {
                return response()->json([
                    'message' => 'Ez az időpont már foglalt.',
                    'errors' => ['start_at' => ['Ez az időpont már foglalt.']],
                ], 409);
            }

            $booking = Booking::create([
                'barber_id' => $request->barber_id,
                'user_id' => $request->user()?->id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'start_at' => $startTime,
                'duration_min' => $durationMin,
                'note' => $request->note,
                'status' => 'confirmed',
            ]);

            // Visszaigazolási e-mail küldése queue-n
            try {
                Mail::to($booking->customer_email)->queue(new BookingConfirmedMail($booking->load('barber')));
            } catch (\Exception $e) {
                Log::error('E-mail küldési hiba: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Foglalás sikeresen létrehozva.',
                'booking' => $booking->load('barber'),
            ], 201);
        });
    }

    public function update(BookingCreateRequest $request, Booking $booking): JsonResponse
    {
        $startTime = Carbon::parse($request->start_at);
        $durationMin = $request->duration_min ?? 30;

        // Ha az időpont változik, ellenőrzünk ütközéseket
        if ($booking->start_at != $startTime) {
            if (!$this->bookingService->isSlotAvailable($request->barber_id, $startTime, $durationMin)) {
                return response()->json([
                    'message' => 'Ez az időpont már foglalt.',
                ], 409);
            }
        }

        $booking->update([
            'barber_id' => $request->barber_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'start_at' => $startTime,
            'duration_min' => $durationMin,
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'Foglalás frissítve.',
            'booking' => $booking,
        ]);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Foglalás lemondva.',
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateTo' => 'required|date_format:Y-m-d|after_or_equal:dateFrom',
            'barberId' => 'required|exists:barbers,id',
        ]);

        $dateFrom = Carbon::parse($request->dateFrom);
        $dateTo = Carbon::parse($request->dateTo);
        $barberId = $request->barberId;

        $slots = [];
        $current = $dateFrom->clone();

        while ($current <= $dateTo) {
            $daySlots = $this->bookingService->getAvailableSlots($barberId, $current, 30);
            $slots = array_merge($slots, $daySlots);
            $current->addDay();
        }

        return response()->json([
            'range' => [
                'from' => $request->dateFrom,
                'to' => $request->dateTo,
            ],
            'barber_id' => $barberId,
            'slots' => $slots,
        ]);
    }
}

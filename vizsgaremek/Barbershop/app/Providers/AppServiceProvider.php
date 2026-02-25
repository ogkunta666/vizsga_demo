<?php

namespace App\Providers;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 24 órás emlékeztető scheduler – óránként fut
        Schedule::call(function () {
            $reminderStart = Carbon::now()->addHours(23)->addMinutes(30);
            $reminderEnd = Carbon::now()->addHours(24)->addMinutes(30);

            Booking::where('status', 'confirmed')
                ->whereBetween('start_at', [$reminderStart, $reminderEnd])
                ->with('barber')
                ->each(function (Booking $booking) {
                    SendBookingReminderJob::dispatch($booking);
                });
        })->hourly()->name('send-booking-reminders')->withoutOverlapping();
    }
}

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foglalás visszaigazolása</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .header { background: #1a1a1a; color: #c9a96e; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; letter-spacing: 2px; }
        .header p { margin: 5px 0 0; color: #aaa; }
        .body { padding: 30px; }
        .body h2 { color: #1a1a1a; }
        .detail { background: #f9f9f9; border-left: 4px solid #c9a96e; padding: 15px 20px; margin: 20px 0; border-radius: 4px; }
        .detail p { margin: 6px 0; }
        .detail strong { color: #1a1a1a; }
        .btn { display: inline-block; background: #c9a96e; color: #fff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; padding: 20px; background: #f4f4f4; font-size: 13px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✂ BARBER SHOP</h1>
            <p>Online időpontfoglaló rendszer</p>
        </div>
        <div class="body">
            <h2>✅ Foglalása visszaigazolva!</h2>
            <p>Kedves <strong>{{ $booking->customer_name }}</strong>!</p>
            <p>Örömmel értesítjük, hogy időpontfoglalása sikeresen rögzítve lett. Az alábbiakban találja a foglalás részleteit:</p>

            <div class="detail">
                <p><strong>Borbély:</strong> {{ $booking->barber->name }}</p>
                <p><strong>Időpont:</strong> {{ \Carbon\Carbon::parse($booking->start_at)->format('Y. F j. (l) H:i') }}</p>
                <p><strong>Időtartam:</strong> {{ $booking->duration_min }} perc</p>
                <p><strong>Foglalás azonosítója:</strong> #{{ $booking->id }}</p>
                @if($booking->note)
                    <p><strong>Megjegyzés:</strong> {{ $booking->note }}</p>
                @endif
            </div>

            <p>Ha módosítani vagy lemondani szeretné foglalását, kérjük lépjen kapcsolatba velünk.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Barber Shop – Minden jog fenntartva</p>
            <p>Ez egy automatikusan generált üzenet, kérjük ne válaszoljon rá.</p>
        </div>
    </div>
</body>
</html>

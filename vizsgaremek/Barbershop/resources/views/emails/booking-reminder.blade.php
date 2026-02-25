<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Időpont emlékeztető</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
        .header { background: #1a1a1a; color: #c9a96e; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; letter-spacing: 2px; }
        .header p { margin: 5px 0 0; color: #aaa; }
        .body { padding: 30px; }
        .body h2 { color: #1a1a1a; }
        .detail { background: #fff8ed; border-left: 4px solid #f5a623; padding: 15px 20px; margin: 20px 0; border-radius: 4px; }
        .detail p { margin: 6px 0; }
        .detail strong { color: #1a1a1a; }
        .highlight { font-size: 22px; font-weight: bold; color: #c9a96e; text-align: center; padding: 20px; background: #fafafa; border-radius: 8px; margin: 20px 0; }
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
            <h2>⏰ Emlékeztető: Holnap időpontja van!</h2>
            <p>Kedves <strong>{{ $booking->customer_name }}</strong>!</p>
            <p>Ez egy baráti emlékeztető, hogy <strong>holnap</strong> időpontja van nálunk. Ne felejtse el!</p>

            <div class="highlight">
                🗓 {{ \Carbon\Carbon::parse($booking->start_at)->format('Y. F j. H:i') }}
            </div>

            <div class="detail">
                <p><strong>Borbély:</strong> {{ $booking->barber->name }}</p>
                <p><strong>Időpont:</strong> {{ \Carbon\Carbon::parse($booking->start_at)->format('Y. F j. (l) H:i') }}</p>
                <p><strong>Időtartam:</strong> {{ $booking->duration_min }} perc</p>
                <p><strong>Foglalás azonosítója:</strong> #{{ $booking->id }}</p>
            </div>

            <p>Ha nem tud megjelenni, kérjük értesítsen minket időben. Köszönjük!</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Barber Shop – Minden jog fenntartva</p>
            <p>Ez egy automatikusan generált üzenet, kérjük ne válaszoljon rá.</p>
        </div>
    </div>
</body>
</html>

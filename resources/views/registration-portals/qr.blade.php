<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Registration QR — {{ $portal['event_name'] }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ink: #0f172a; --gold: #b8922e; --muted: #64748b; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, sans-serif;
            background: #f1f5f9;
            color: var(--ink);
            padding: 24px 16px;
        }
        .qr-toolbar {
            max-width: 640px;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        .qr-toolbar a, .qr-toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: var(--ink);
            font-size: .95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .qr-toolbar .qr-btn--primary { background: linear-gradient(135deg, #d4af37, #b8922e); border-color: transparent; color: #111; }
        .qr-poster {
            max-width: 640px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            padding: clamp(24px, 6vw, 56px) clamp(20px, 5vw, 48px);
            text-align: center;
            box-shadow: 0 20px 60px rgba(15,23,42,.12);
        }
        .qr-poster__eyebrow { text-transform: uppercase; letter-spacing: .16em; color: var(--gold); font-size: .78rem; margin: 18px 0 6px; }
        .qr-poster__title { font-size: clamp(1.5rem, 5vw, 2.2rem); margin: 0 0 6px; }
        .qr-poster__subtitle { color: var(--muted); margin: 0 0 24px; }
        .qr-poster__code { display: inline-block; padding: 18px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; }
        .qr-poster__code img { display: block; width: clamp(220px, 60vw, 320px); height: auto; }
        .qr-poster__cta { margin-top: 22px; font-size: 1.15rem; font-weight: 700; }
        .qr-poster__url { margin-top: 10px; color: var(--muted); font-size: .9rem; word-break: break-all; }
        .qr-poster__footer { margin-top: 28px; color: var(--muted); font-size: .82rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .qr-toolbar { display: none; }
            .qr-poster { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="qr-toolbar">
        <a href="{{ route('registration-portals.show', $portal['id']) }}"><i class="fas fa-arrow-left"></i> Back to portal</a>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="qr-btn--primary" href="#" onclick="window.print();return false;"><i class="fas fa-print"></i> Print</a>
            <a href="{{ $qrDownload }}"><i class="fas fa-download"></i> Download QR</a>
        </div>
    </div>

    <div class="qr-poster">
        <p class="qr-poster__eyebrow">{{ $portal['category'] ?: 'Event Registration' }}</p>
        <h1 class="qr-poster__title">{{ $portal['event_name'] }}</h1>
        @if (!empty($portal['event_subtitle']))
            <p class="qr-poster__subtitle">{{ $portal['event_subtitle'] }}</p>
        @endif

        <div class="qr-poster__code">
            <img src="{{ $qrImg }}" alt="Scan to register QR code">
        </div>

        <p class="qr-poster__cta"><i class="fas fa-mobile-screen-button"></i> Scan to Register</p>
        <p class="qr-poster__url">{{ $registrationUrl }}</p>
        <p class="qr-poster__footer">Point your phone camera at the code to open the registration form.</p>
    </div>
</body>
</html>

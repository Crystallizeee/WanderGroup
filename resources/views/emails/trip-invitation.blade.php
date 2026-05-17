<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; line-height: 1.6; color: #171c20; margin: 0; padding: 0; background-color: #f5faff; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #00658d 0%, #296195 100%); padding: 40px 20px; text-align: center; color: #ffffff; }
        .content { padding: 40px; }
        .trip-box { background: #eff4fa; border-radius: 12px; padding: 20px; margin: 24px 0; border: 1px solid #dee3e8; }
        .btn { display: inline-block; padding: 14px 32px; background-color: #00658d; color: #ffffff !important; text-decoration: none; border-radius: 30px; font-weight: 600; margin-top: 20px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #6e7881; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0; font-size: 24px;">WanderGroup</h1>
        </div>
        <div class="content">
            <h2 style="margin:0; color: #00658d;">Halo! 👋</h2>
            <p style="font-size: 16px; margin-top: 16px;">
                <strong>{{ $invitation->inviter->name }}</strong> mengundang kamu untuk bergabung dalam rencana perjalanan seru!
            </p>
            
            <div class="trip-box">
                <h3 style="margin:0; color: #171c20;">{{ $invitation->trip->title }}</h3>
                <p style="margin: 8px 0 0 0; color: #3e4850; font-size: 14px;">
                    📍 {{ $invitation->trip->destination }}<br>
                    📅 {{ $invitation->trip->start_date->format('d M') }} - {{ $invitation->trip->end_date->format('d M Y') }}
                </p>
            </div>

            <p>Mari rencanakan itinerary, bagi pengeluaran, dan simpan dokumen perjalanan bersama-sama di satu tempat.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('invitation.accept', $invitation->token) }}" class="btn">Terima Undangan</a>
            </div>
            
            <p style="font-size: 13px; color: #6e7881; margin-top: 32px;">
                Link ini akan kadaluarsa dalam 7 hari. Jika kamu tidak merasa diundang, abaikan saja email ini.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} WanderGroup Platform. Membantu grup merencanakan perjalanan lebih baik.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Password — EV Smart Energy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: #060d1a;
            color: #f1f5f9;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* ── Animated Background ── */
        #bg-canvas {
            position: fixed; inset: 0; z-index: 0;
        }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image:
                linear-gradient(rgba(6,182,212,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6,182,212,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        /* Ambient glow blobs */
        .glow-blob {
            position: fixed; border-radius: 50%;
            filter: blur(90px); pointer-events: none; z-index: 1;
        }
        .glow-1 {
            width: 500px; height: 500px;
            top: -180px; left: -120px;
            background: radial-gradient(circle, rgba(29,78,216,0.18), transparent 70%);
        }
        .glow-2 {
            width: 400px; height: 400px;
            bottom: -150px; right: -100px;
            background: radial-gradient(circle, rgba(6,182,212,0.14), transparent 70%);
        }

        .card {
            position: relative; z-index: 10;
            background: rgba(15,28,48,0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(6,182,212,0.18);
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 0 1px rgba(6,182,212,0.05);
            width: 100%;
            max-width: 420px;
            padding: 32px 28px 24px;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand-row {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; margin-bottom: 18px;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, #1d4ed8, #06b6d4);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px;
        }
        .brand-name {
            font-size: 14px; font-weight: 700;
            color: #e2e8f0; letter-spacing: -0.2px;
        }

        .divider {
            height: 1px;
            background: rgba(255,255,255,0.07);
            margin-bottom: 20px;
        }

        /* Email Icon — amplop dengan animasi ring + orbit dots */
        .app-icon-wrap { display: flex; justify-content: center; margin-bottom: 20px; }
        .email-icon-outer {
            position: relative;
            width: 90px; height: 90px;
            display: flex; align-items: center; justify-content: center;
        }
        .email-ring {
            position: absolute; inset: 0; border-radius: 50%;
            border: 2px solid rgba(6,182,212,0.35);
            animation: emailRingPulse 2.5s ease-in-out infinite;
        }
        .email-ring-2 {
            position: absolute; inset: -12px; border-radius: 50%;
            border: 1.5px solid rgba(6,182,212,0.15);
            animation: emailRingPulse 2.5s ease-in-out infinite 0.6s;
        }
        @keyframes emailRingPulse {
            0%,100% { transform: scale(1); opacity: 0.7; }
            50%      { transform: scale(1.1); opacity: 0.15; }
        }
        .email-icon-inner {
            width: 72px; height: 72px; border-radius: 20px;
            background: linear-gradient(135deg, #1d4ed8, #06b6d4);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 1px rgba(6,182,212,0.3), 0 12px 35px rgba(6,182,212,0.3);
            position: relative; z-index: 2;
        }
        .email-icon-inner i {
            font-size: 30px; color: #fff;
            animation: emailFloat 3s ease-in-out infinite;
        }
        @keyframes emailFloat {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-5px); }
        }
        .email-dot {
            position: absolute; width: 8px; height: 8px; border-radius: 50%;
            background: #22d3ee; box-shadow: 0 0 10px rgba(6,182,212,0.9);
            animation: orbitDot 3s linear infinite; z-index: 3;
        }
        .email-dot-2 {
            width: 6px; height: 6px; background: #60a5fa;
            box-shadow: 0 0 8px rgba(96,165,250,0.9);
            animation: orbitDot 3s linear infinite reverse;
            animation-delay: -1.5s;
        }
        @keyframes orbitDot {
            0%   { transform: rotate(0deg) translateX(48px); }
            100% { transform: rotate(360deg) translateX(48px); }
        }

        .card-header { text-align: center; margin-bottom: 22px; }
        .card-header h1 {
            font-size: 19px; font-weight: 700;
            color: #f1f5f9; margin-bottom: 6px;
        }
        .card-header p {
            font-size: 13px; color: #94a3b8; line-height: 1.6;
        }

        /* Alerts */
        .alert {
            padding: 10px 13px; border-radius: 8px;
            font-size: 13px; margin-bottom: 16px;
            display: flex; align-items: flex-start;
            gap: 9px; line-height: 1.5;
        }
        .alert i { margin-top: 1px; flex-shrink: 0; font-size: 13px; }
        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #fca5a5;
        }
        .alert-info {
            background: rgba(6,182,212,0.08);
            border: 1px solid rgba(6,182,212,0.2);
            color: #22d3ee;
        }

        /* Form */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-size: 12px; font-weight: 600;
            color: #94a3b8; margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #475569; font-size: 13px; pointer-events: none;
        }
        .form-input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            font-size: 14px; font-family: inherit;
            color: #f1f5f9;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6,182,212,0.1);
            background: rgba(255,255,255,0.06);
        }
        .form-input::placeholder { color: #475569; }

        /* Button */
        .btn-submit {
            width: 100%; padding: 11px;
            background: #1d4ed8; color: #fff;
            border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; font-family: inherit;
            transition: background 0.15s;
            display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #1e40af; }
        .btn-submit:active { transform: scale(0.99); }

        /* Security note */
        .security-note {
            margin-top: 14px;
            padding: 10px 12px;
            background: rgba(6,182,212,0.05);
            border: 1px solid rgba(6,182,212,0.12);
            border-radius: 8px;
            font-size: 12px; color: #64748b;
            line-height: 1.5;
            display: flex; gap: 8px;
        }
        .security-note i { color: #06b6d4; margin-top: 1px; flex-shrink: 0; }
        .security-note strong { color: #94a3b8; }

        /* Footer */
        .card-footer {
            margin-top: 18px; text-align: center;
            font-size: 13px; color: #64748b;
        }
        .card-footer a {
            color: #06b6d4; font-weight: 500;
            text-decoration: none;
        }
        .card-footer a:hover { color: #22d3ee; }
    </style>
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <div class="grid-overlay"></div>
    <div class="glow-blob glow-1"></div>
    <div class="glow-blob glow-2"></div>

    <div class="card">

        <div class="brand-row">
            <div class="brand-icon"><i class="fas fa-charging-station"></i></div>
            <span class="brand-name">EV Smart Energy</span>
        </div>
        <div class="divider"></div>

        <div class="app-icon-wrap">
            <div class="email-icon-outer">
                <div class="email-ring"></div>
                <div class="email-ring-2"></div>
                <div class="email-icon-inner">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="email-dot"></div>
                <div class="email-dot email-dot-2"></div>
            </div>
        </div>

        <div class="card-header">
            <h1>Reset Password</h1>
            <p>Masukkan email akun Anda. Kami akan mengirimkan kode verifikasi untuk mereset password.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.send-otp') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email"
                           class="form-input"
                           placeholder="contoh@email.com"
                           value="{{ old('email') }}"
                           required autofocus>
                </div>
            </div>

            {{-- Channel email saja — hidden --}}
            <input type="hidden" name="channel" value="email">

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                Kirim Kode Verifikasi ke Email
            </button>
        </form>

        <div class="security-note">
            <i class="fas fa-shield-halved"></i>
            <span>Kode verifikasi berlaku <strong>10 menit</strong> dan hanya bisa digunakan sekali. Jangan bagikan kode kepada siapa pun.</span>
        </div>

        <div class="card-footer">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-left" style="font-size:11px;margin-right:4px"></i>Kembali ke halaman login
            </a>
        </div>

    </div>

    <script>
        // ── Animated particle background ──
        (function () {
            const canvas = document.getElementById('bg-canvas');
            const ctx = canvas.getContext('2d');
            let W, H, nodes = [];
            function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
            window.addEventListener('resize', resize); resize();
            for (let i = 0; i < 120; i++) nodes.push({
                x: Math.random() * W, y: Math.random() * H,
                vx: (Math.random() - 0.5) * 0.5, vy: (Math.random() - 0.5) * 0.5,
                r: Math.random() * 2.5 + 0.8,
                a: Math.random() * 0.5 + 0.4,
                col: Math.random() > 0.6 ? '6,182,212' : Math.random() > 0.5 ? '59,130,246' : '16,185,129'
            });
            function tick() {
                ctx.clearRect(0, 0, W, H);
                nodes.forEach(n => {
                    n.x += n.vx; n.y += n.vy;
                    if (n.x < 0 || n.x > W) n.vx *= -1;
                    if (n.y < 0 || n.y > H) n.vy *= -1;
                    ctx.beginPath(); ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(${n.col},${n.a})`; ctx.fill();
                });
                for (let i = 0; i < nodes.length; i++)
                    for (let j = i + 1; j < nodes.length; j++) {
                        const dx = nodes[i].x - nodes[j].x, dy = nodes[i].y - nodes[j].y;
                        const d = Math.sqrt(dx * dx + dy * dy);
                        if (d < 150) {
                            ctx.strokeStyle = `rgba(6,182,212,${0.28 * (1 - d / 150)})`;
                            ctx.lineWidth = 1; ctx.beginPath();
                            ctx.moveTo(nodes[i].x, nodes[i].y);
                            ctx.lineTo(nodes[j].x, nodes[j].y); ctx.stroke();
                        }
                    }
                requestAnimationFrame(tick);
            }
            tick();
        })();
    </script>
</body>
</html>

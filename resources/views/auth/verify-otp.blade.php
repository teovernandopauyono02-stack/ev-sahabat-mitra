<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Kode — EV Smart Energy</title>
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
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 20px;
            position: relative; overflow: hidden;
        }

        #bg-canvas { position: fixed; inset: 0; z-index: 0; }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image:
                linear-gradient(rgba(6,182,212,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6,182,212,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .glow-blob { position: fixed; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 1; }
        .glow-1 { width: 520px; height: 520px; top: -180px; left: -120px; background: radial-gradient(circle, rgba(29,78,216,0.22), transparent 70%); }
        .glow-2 { width: 450px; height: 450px; bottom: -150px; right: -100px; background: radial-gradient(circle, rgba(6,182,212,0.18), transparent 70%); }
        .glow-3 { width: 300px; height: 300px; top: 40%; left: 50%; transform: translate(-50%,-50%); background: radial-gradient(circle, rgba(16,185,129,0.12), transparent 70%); }

        .card {
            background: rgba(15,28,48,0.88);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(6,182,212,0.18);
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            width: 100%; max-width: 420px;
            padding: 32px 28px 24px;
            position: relative; z-index: 10;
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
        .brand-name { font-size: 14px; font-weight: 700; color: #e2e8f0; }
        .divider { height: 1px; background: rgba(255,255,255,0.07); margin-bottom: 20px; }
        /* App icon */
        .app-icon-wrap { display:flex; justify-content:center; margin-bottom:20px; }
        .app-icon {
            width:72px; height:72px;
            background: linear-gradient(135deg, #1d4ed8 0%, #06b6d4 100%);
            border-radius:20px;
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:32px;
            box-shadow: 0 0 0 1px rgba(6,182,212,0.25), 0 16px 40px rgba(6,182,212,0.25);
            position:relative;
            animation: iconPulse 3s ease-in-out infinite;
        }
        @keyframes iconPulse {
            0%,100% { box-shadow: 0 0 0 1px rgba(6,182,212,0.25), 0 16px 40px rgba(6,182,212,0.2); }
            50%      { box-shadow: 0 0 0 1px rgba(6,182,212,0.45), 0 16px 50px rgba(6,182,212,0.4); }
        }
        .app-icon::after {
            content:''; position:absolute; inset:-8px;
            border-radius:28px; border:1px solid rgba(6,182,212,0.2);
            animation: ringExpand 3s ease-in-out infinite;
        }
        @keyframes ringExpand {
            0%,100% { transform:scale(1); opacity:0.6; }
            50%      { transform:scale(1.06); opacity:0; }
        }

        .card-header { text-align: center; margin-bottom: 20px; }
        .card-header h1 { font-size: 19px; font-weight: 700; color: #f1f5f9; margin-bottom: 6px; }
        .card-header p { font-size: 13px; color: #94a3b8; line-height: 1.6; }
        .card-header strong { color: #e2e8f0; }

        .alert {
            padding: 10px 13px; border-radius: 8px;
            font-size: 13px; margin-bottom: 16px;
            display: flex; align-items: flex-start; gap: 9px; line-height: 1.5;
        }
        .alert i { margin-top: 1px; flex-shrink: 0; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
        .alert-success { background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #34d399; }

        /* Demo OTP banner */
        .demo-banner {
            background: rgba(255,215,0,0.07);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .demo-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #94a3b8; margin-bottom: 6px;
        }
        .demo-otp-code {
            font-family: 'Rajdhani', sans-serif;
            font-size: 30px; font-weight: 700;
            letter-spacing: 10px; color: #FFD700;
            font-variant-numeric: tabular-nums;
            margin: 2px 0 4px;
        }
        .demo-banner small { font-size: 11px; color: #64748b; }

        /* Timer */
        .timer-row {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 12px; color: #64748b;
        }
        .timer-badge {
            display: flex; align-items: center; gap: 6px;
            padding: 4px 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            font-size: 12px; font-weight: 600;
            color: #94a3b8; transition: all 0.3s;
        }
        .timer-badge.warning {
            background: rgba(245,158,11,0.08);
            border-color: rgba(245,158,11,0.2);
            color: #fbbf24;
        }
        .timer-badge.expired {
            background: rgba(239,68,68,0.08);
            border-color: rgba(239,68,68,0.2);
            color: #f87171;
        }
        .timer-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #10b981;
            animation: blink 1.2s ease-in-out infinite;
        }
        .timer-badge.warning .timer-dot { background: #f59e0b; }
        .timer-badge.expired .timer-dot { background: #ef4444; animation: none; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        /* OTP inputs */
        .otp-row {
            display: flex; gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .otp-box {
            width: 48px; height: 52px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            text-align: center;
            font-size: 20px; font-weight: 700;
            color: #f1f5f9;
            outline: none;
            transition: all 0.15s;
            font-family: 'Rajdhani', sans-serif;
            font-variant-numeric: tabular-nums;
        }
        .otp-box:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6,182,212,0.1);
            background: rgba(6,182,212,0.05);
        }
        .otp-box.filled {
            border-color: #06b6d4;
            background: rgba(6,182,212,0.08);
            color: #22d3ee;
        }

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
        .btn-submit:disabled { background: #1e3a5f; color: #475569; cursor: not-allowed; }

        .card-footer {
            margin-top: 18px;
            display: flex; justify-content: space-between;
            font-size: 13px; color: #64748b;
        }
        .card-footer a { color: #06b6d4; font-weight: 500; text-decoration: none; }
        .card-footer a:hover { color: #22d3ee; }
    </style>
</head>
<body>
    <canvas id="bg-canvas"></canvas>
    <div class="grid-overlay"></div>
    <div class="glow-blob glow-1"></div>
    <div class="glow-blob glow-2"></div>
    <div class="glow-blob glow-3"></div>

    <div class="card">

        <div class="brand-row">
            <div class="brand-icon"><i class="fas fa-charging-station"></i></div>
            <span class="brand-name">EV Smart Energy</span>
        </div>
        <div class="divider"></div>

        <div class="app-icon-wrap">
            <div class="app-icon"><i class="fas fa-charging-station"></i></div>
        </div>

        <div class="card-header">
            <h1>Verifikasi Kode</h1>
            <p>Kode 6 digit telah dikirim ke<br><strong>{{ $email }}</strong></p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if(session('demo_otp'))
            <div class="demo-banner">
                <div class="demo-label">Mode Demo — Kode OTP Anda</div>
                @php
                    preg_match('/OTP:\s*(\d{6})/', session('demo_otp'), $m);
                    $displayOtp = $m[1] ?? session('demo_otp');
                @endphp
                <div class="demo-otp-code">{{ $displayOtp }}</div>
                <small>Di production, kode ini dikirim ke email/WhatsApp Anda</small>
            </div>
        @endif

        <div class="timer-row">
            <span>Kode berlaku selama:</span>
            <div class="timer-badge" id="timerBadge">
                <div class="timer-dot" id="timerDot"></div>
                <span id="timerText">10:00</span>
            </div>
        </div>

        <form method="POST" action="{{ route('password.verify-otp.post') }}" id="otpForm">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="otp" id="otpFinal">

            <div class="otp-row">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]" autofocus>
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" pattern="[0-9]">
            </div>

            <button type="submit" class="btn-submit" id="verifyBtn">
                <i class="fas fa-check"></i>
                Verifikasi Kode
            </button>
        </form>

        <div class="card-footer">
            <a href="{{ route('password.forgot') }}">
                <i class="fas fa-redo" style="font-size:11px;margin-right:4px"></i>Kirim ulang
            </a>
            <a href="{{ route('login') }}">Batal</a>
        </div>

    </div>

    <script>
        const boxes = document.querySelectorAll('.otp-box');
        const otpFinal = document.getElementById('otpFinal');
        const verifyBtn = document.getElementById('verifyBtn');

        boxes.forEach((box, i) => {
            box.addEventListener('input', e => {
                const v = e.target.value.replace(/\D/g, '');
                e.target.value = v;
                v ? box.classList.add('filled') : box.classList.remove('filled');
                if (v && i < boxes.length - 1) boxes[i + 1].focus();
                updateFinal();
            });
            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && i > 0) {
                    boxes[i - 1].focus();
                    boxes[i - 1].classList.remove('filled');
                }
            });
            box.addEventListener('paste', e => {
                e.preventDefault();
                const txt = (e.clipboardData || window.clipboardData)
                    .getData('text').replace(/\D/g, '').slice(0, 6);
                txt.split('').forEach((c, j) => {
                    if (boxes[j]) { boxes[j].value = c; boxes[j].classList.add('filled'); }
                });
                if (txt.length === 6) boxes[5].focus();
                updateFinal();
            });
        });

        function updateFinal() {
            otpFinal.value = Array.from(boxes).map(b => b.value).join('');
        }

        document.getElementById('otpForm').addEventListener('submit', e => {
            if (otpFinal.value.length !== 6) { e.preventDefault(); boxes[0].focus(); }
        });

        // Countdown 10 menit
        let secs = 600;
        const timerText  = document.getElementById('timerText');
        const timerBadge = document.getElementById('timerBadge');

        const cd = setInterval(() => {
            secs--;
            const m = String(Math.floor(secs / 60)).padStart(2, '0');
            const s = String(secs % 60).padStart(2, '0');
            timerText.textContent = `${m}:${s}`;
            if (secs <= 60)  timerBadge.className = 'timer-badge warning';
            if (secs <= 0) {
                clearInterval(cd);
                timerBadge.className = 'timer-badge expired';
                timerText.textContent = 'Kedaluwarsa';
                verifyBtn.disabled = true;
            }
        }, 1000);

        // ── Particle background ──
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

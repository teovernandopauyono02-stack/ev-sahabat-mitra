<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buat Password Baru — EV Smart Energy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Inter', sans-serif; background: #060d1a; color: #f1f5f9; }
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; position: relative; overflow: hidden; }
        #bg-canvas { position: fixed; inset: 0; z-index: 0; }
        .grid-overlay { position: fixed; inset: 0; z-index: 1; pointer-events: none; background-image: linear-gradient(rgba(6,182,212,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(6,182,212,0.04) 1px, transparent 1px); background-size: 48px 48px; }
        .glow-blob { position: fixed; border-radius: 50%; filter: blur(90px); pointer-events: none; z-index: 1; }
        .glow-1 { width: 520px; height: 520px; top: -180px; left: -120px; background: radial-gradient(circle, rgba(16,185,129,0.2), transparent 70%); }
        .glow-2 { width: 450px; height: 450px; bottom: -150px; right: -100px; background: radial-gradient(circle, rgba(29,78,216,0.18), transparent 70%); }
        .glow-3 { width: 300px; height: 300px; top: 40%; left: 50%; transform: translate(-50%,-50%); background: radial-gradient(circle, rgba(6,182,212,0.12), transparent 70%); }
        .card {
            background: rgba(15,28,48,0.88); backdrop-filter: blur(16px);
            border: 1px solid rgba(6,182,212,0.18);
            border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            width: 100%; max-width: 420px; padding: 32px 28px 24px;
            position: relative; z-index: 10;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .brand-row { display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:18px; }
        .brand-icon { width:34px; height:34px; background:linear-gradient(135deg,#1d4ed8,#06b6d4); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px; }
        .brand-name { font-size:14px; font-weight:700; color:#e2e8f0; }
        .divider { height:1px; background:rgba(255,255,255,0.07); margin-bottom:20px; }
        /* App icon */
        .app-icon-wrap { display:flex; justify-content:center; margin-bottom:20px; }
        .app-icon { width:72px; height:72px; background:linear-gradient(135deg,#1d4ed8 0%,#06b6d4 100%); border-radius:20px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:32px; box-shadow:0 0 0 1px rgba(6,182,212,0.25),0 16px 40px rgba(6,182,212,0.25); position:relative; animation:iconPulse 3s ease-in-out infinite; }
        @keyframes iconPulse { 0%,100%{box-shadow:0 0 0 1px rgba(6,182,212,0.25),0 16px 40px rgba(6,182,212,0.2)} 50%{box-shadow:0 0 0 1px rgba(6,182,212,0.45),0 16px 50px rgba(6,182,212,0.4)} }
        .app-icon::after { content:''; position:absolute; inset:-8px; border-radius:28px; border:1px solid rgba(6,182,212,0.2); animation:ringExpand 3s ease-in-out infinite; }
        @keyframes ringExpand { 0%,100%{transform:scale(1);opacity:0.6} 50%{transform:scale(1.06);opacity:0} }
        .card-header { text-align:center; margin-bottom:20px; }
        .card-header h1 { font-size:19px; font-weight:700; color:#f1f5f9; margin-bottom:6px; }
        .card-header p { font-size:13px; color:#94a3b8; line-height:1.6; }
        .card-header strong { color:#e2e8f0; }
        .alert { padding:10px 13px; border-radius:8px; font-size:13px; margin-bottom:16px; display:flex; align-items:flex-start; gap:9px; line-height:1.5; }
        .alert i { margin-top:1px; flex-shrink:0; }
        .alert-error { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25); color:#fca5a5; }
        .req-list { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:8px; padding:10px 12px; margin-bottom:16px; }
        .req-item { display:flex; align-items:center; gap:7px; font-size:12px; color:#64748b; padding:2px 0; }
        .req-item i { font-size:10px; color:rgba(255,255,255,0.15); }
        .req-item.ok { color:#34d399; }
        .req-item.ok i { color:#10b981; }
        .form-group { margin-bottom:14px; }
        .form-label { display:block; font-size:12px; font-weight:600; color:#94a3b8; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
        .input-wrap { position:relative; }
        .input-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#475569; font-size:13px; pointer-events:none; }
        .form-input { width:100%; padding:10px 40px 10px 36px; background:rgba(255,255,255,0.04); border:1.5px solid rgba(255,255,255,0.1); border-radius:8px; font-size:14px; font-family:inherit; color:#f1f5f9; outline:none; transition:border-color 0.15s,box-shadow 0.15s; }
        .form-input:focus { border-color:#06b6d4; box-shadow:0 0 0 3px rgba(6,182,212,0.1); background:rgba(255,255,255,0.06); }
        .form-input::placeholder { color:#475569; }
        .toggle-pass { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#475569; cursor:pointer; font-size:13px; padding:4px; }
        .toggle-pass:hover { color:#94a3b8; }
        .strength-wrap { margin-top:7px; }
        .strength-bars { display:flex; gap:4px; margin-bottom:4px; }
        .strength-bar { flex:1; height:3px; border-radius:2px; background:rgba(255,255,255,0.08); transition:background 0.3s; }
        .strength-bar.weak   { background:#ef4444; }
        .strength-bar.medium { background:#f59e0b; }
        .strength-bar.strong { background:#10b981; }
        .strength-text { font-size:11px; color:#64748b; }
        .strength-text.weak   { color:#f87171; }
        .strength-text.medium { color:#fbbf24; }
        .strength-text.strong { color:#34d399; }
        .btn-submit { width:100%; padding:11px; background:#1d4ed8; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit; transition:background 0.15s; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:4px; }
        .btn-submit:hover { background:#1e40af; }
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
            <h1>Buat Password Baru</h1>
            <p>Identitas terverifikasi untuk <strong>{{ $email }}</strong>.<br>Buat password baru yang aman.</p>
        </div>
        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
        <div class="req-list">
            <div class="req-item" id="req-len"><i class="fas fa-circle"></i> Minimal 8 karakter</div>
            <div class="req-item" id="req-upper"><i class="fas fa-circle"></i> Mengandung huruf besar</div>
            <div class="req-item" id="req-num"><i class="fas fa-circle"></i> Mengandung angka</div>
            <div class="req-item" id="req-match"><i class="fas fa-circle"></i> Konfirmasi password cocok</div>
        </div>
        <form method="POST" action="{{ route('password.reset') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="password">Password Baru</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-input" placeholder="Minimal 8 karakter" required autofocus>
                    <button type="button" class="toggle-pass" data-target="password"><i class="fas fa-eye"></i></button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-bars">
                        <div class="strength-bar" id="sb1"></div>
                        <div class="strength-bar" id="sb2"></div>
                        <div class="strength-bar" id="sb3"></div>
                        <div class="strength-bar" id="sb4"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" placeholder="Ulangi password baru" required>
                    <button type="button" class="toggle-pass" data-target="password_confirmation"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-check"></i> Simpan Password Baru
            </button>
        </form>
    </div>
    <script>
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const inp = document.getElementById(btn.dataset.target);
                const ico = btn.querySelector('i');
                inp.type = inp.type === 'password' ? 'text' : 'password';
                ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
        });
        const pw = document.getElementById('password');
        const cf = document.getElementById('password_confirmation');
        const bars = ['sb1','sb2','sb3','sb4'].map(id => document.getElementById(id));
        const stText = document.getElementById('strengthText');
        function setReq(id, ok) {
            const el = document.getElementById(id);
            el.className = 'req-item' + (ok ? ' ok' : '');
            el.querySelector('i').className = ok ? 'fas fa-check-circle' : 'fas fa-circle';
        }
        function checkStrength() {
            const v = pw.value, cv = cf.value;
            setReq('req-len',   v.length >= 8);
            setReq('req-upper', /[A-Z]/.test(v));
            setReq('req-num',   /\d/.test(v));
            setReq('req-match', v.length > 0 && v === cv);
            let score = 0;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            bars.forEach((b, i) => {
                b.className = 'strength-bar';
                if (i < score) b.classList.add(score <= 1 ? 'weak' : score <= 3 ? 'medium' : 'strong');
            });
            stText.className = 'strength-text';
            if (!v) { stText.textContent = ''; return; }
            if (score <= 1) { stText.textContent = 'Lemah'; stText.classList.add('weak'); }
            else if (score <= 3) { stText.textContent = 'Sedang'; stText.classList.add('medium'); }
            else { stText.textContent = 'Kuat'; stText.classList.add('strong'); }
        }
        pw.addEventListener('input', checkStrength);
        cf.addEventListener('input', checkStrength);

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
                col: Math.random() > 0.6 ? '6,182,212' : Math.random() > 0.5 ? '16,185,129' : '59,130,246'
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

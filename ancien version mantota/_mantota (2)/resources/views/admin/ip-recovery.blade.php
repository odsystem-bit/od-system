<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperation d'acces — MANTOTA Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px; max-width: 420px; width: 100%; box-shadow: 0 25px 50px rgba(0,0,0,.3); }
        .logo { text-align: center; margin-bottom: 24px; font-size: 24px; font-weight: 700; color: #a78bfa; }
        h1 { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #f1f5f9; }
        .desc { font-size: 14px; color: #94a3b8; margin-bottom: 24px; line-height: 1.5; }
        .ip-box { background: #0f172a; border: 1px solid #475569; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .ip-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        .ip-value { font-size: 16px; font-weight: 600; color: #38bdf8; font-family: monospace; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #cbd5e1; }
        input[type="text"] { width: 100%; padding: 12px 16px; border: 1px solid #475569; border-radius: 8px; background: #0f172a; color: #f1f5f9; font-size: 16px; outline: none; transition: border .2s; }
        input[type="text"]:focus { border-color: #a78bfa; }
        .error { color: #f87171; font-size: 13px; margin-top: 6px; }
        .success { background: #064e3b; border: 1px solid #10b981; color: #a7f3d0; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        button { width: 100%; margin-top: 20px; padding: 12px; background: #7c3aed; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .2s; }
        button:hover { background: #6d28d9; }
        .back-link { display: block; text-align: center; margin-top: 16px; color: #64748b; font-size: 13px; text-decoration: none; }
        .back-link:hover { color: #a78bfa; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">MANTOTA</div>
        <h1>Recuperation d'acces admin</h1>
        <p class="desc">Votre IP publique a change ? Entrez votre code secret de recuperation pour autoriser votre nouvelle adresse IP.</p>

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <div class="ip-box">
            <span class="ip-label">Votre IP actuelle</span>
            <span class="ip-value">{{ $currentIp }}</span>
        </div>

        <form method="POST" action="{{ route('admin.ip-recovery.recover') }}">
            @csrf
            <div>
                <label for="recovery_code">Code secret de recuperation</label>
                <input type="text" id="recovery_code" name="recovery_code" placeholder="Entrez votre code secret..." autocomplete="off" required>
                @error('recovery_code')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit">Restaurer mon acces</button>
        </form>

        <a href="{{ route('admin.login') }}" class="back-link">&larr; Retour au login admin</a>
    </div>
</body>
</html>

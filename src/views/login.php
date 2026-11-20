
<?php
require_once __DIR__ . '/logo.php';
if ($page === 'login') { ?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar • Visual Pro</title>
    <link rel="icon" href="<?= e(logo_src()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #3e4095;
            --brand-600: #34367f;
            --brand-700: #2a2c68;
            --accent: #00afef;
            --red: #e11d2e;
            --ink: #151834;
            --text: #2a2f4a;
            --muted: #6c7390;
            --line: #d6d9e5;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--text);
            background: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .split {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
            min-height: 100%;
        }

        /* ---- painel da marca ---- */
        .showcase {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            color: #fff;
            overflow: hidden;
            background:
                radial-gradient(60% 50% at 100% 0%, rgba(0, 175, 239, .35), transparent 60%),
                radial-gradient(50% 40% at 0% 100%, rgba(225, 29, 46, .28), transparent 60%),
                linear-gradient(160deg, #3e4095 0%, #2a2c68 55%, #1c1e4a 100%);
        }

        .showcase::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .14) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: linear-gradient(135deg, transparent 20%, #000 80%);
            -webkit-mask-image: linear-gradient(135deg, transparent 20%, #000 80%);
            pointer-events: none;
        }

        .showcase > * { position: relative; }

        .showcase .logo { width: 190px; height: auto; display: block; }

        .showcase h1 {
            margin: 0 0 14px;
            max-width: 440px;
            font-size: clamp(1.9rem, 3vw, 2.6rem);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -.035em;
        }
        .showcase h1 em { font-style: normal; color: var(--accent); }

        .showcase p.lead { margin: 0 0 32px; max-width: 420px; color: rgba(255, 255, 255, .78); font-size: 1rem; line-height: 1.6; }

        .features { display: grid; gap: 14px; margin: 0; padding: 0; list-style: none; }
        .features li { display: flex; align-items: center; gap: 12px; font-size: .92rem; color: rgba(255, 255, 255, .9); }
        .features .ico {
            flex: none;
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
        }
        .features svg { width: 17px; height: 17px; }

        .showcase .copy { font-size: .78rem; color: rgba(255, 255, 255, .55); }

        /* ---- formulário ---- */
        .pane {
            display: grid;
            place-items: center;
            padding: 32px 24px;
            background: #fafbfd;
        }

        .login { width: min(400px, 100%); }

        .login .mobile-logo { display: none; width: 150px; margin: 0 auto 28px; }

        .login h2 { margin: 0 0 6px; font-size: 1.65rem; font-weight: 750; letter-spacing: -.03em; color: var(--ink); }
        .login .sub { margin: 0 0 28px; color: var(--muted); font-size: .94rem; }

        .field { margin-bottom: 18px; }
        .field label { display: block; margin-bottom: 7px; font-size: .82rem; font-weight: 650; color: #3b4162; }

        .control { position: relative; }
        .control .lead-ico {
            position: absolute;
            left: 14px;
            top: 50%;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #9aa0b8;
            pointer-events: none;
        }
        .control input {
            width: 100%;
            height: 50px;
            padding: 0 44px 0 44px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .control input::placeholder { color: #a3a8bf; }
        .control input:hover { border-color: #b9bed3; }
        .control input:focus { border-color: var(--brand); box-shadow: 0 0 0 4px rgba(62, 64, 149, .14); }

        .toggle-pass {
            position: absolute;
            right: 6px;
            top: 50%;
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #9aa0b8;
            cursor: pointer;
        }
        .toggle-pass:hover { background: #f0f1fa; color: var(--brand); }
        .toggle-pass svg { width: 18px; height: 18px; }

        .btn-login {
            width: 100%;
            height: 52px;
            margin-top: 6px;
            border: 0;
            border-radius: 12px;
            background: var(--brand);
            color: #fff;
            font: inherit;
            font-weight: 650;
            font-size: .96rem;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(42, 44, 104, .3), 0 10px 24px rgba(62, 64, 149, .28);
            transition: background .15s, transform .1s, box-shadow .15s;
        }
        .btn-login:hover { background: var(--brand-600); box-shadow: 0 1px 2px rgba(42, 44, 104, .3), 0 14px 28px rgba(62, 64, 149, .34); }
        .btn-login:active { transform: translateY(1px); }
        .btn-login:focus-visible, .toggle-pass:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .login-footer { margin-top: 28px; text-align: center; font-size: .78rem; color: var(--muted); }
        .login-footer::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            margin-right: 7px;
            vertical-align: middle;
            border-radius: 50%;
            background: var(--red);
        }

        /* alertas */
        .alert {
            position: relative;
            margin: 0 0 20px;
            padding: 12px 40px 12px 14px;
            border: 1px solid transparent;
            border-radius: 12px;
            font-size: .88rem;
        }
        .alert-danger { background: #fdecee; color: #b3202f; border-color: #f4c2c8; }
        .alert-success { background: #e8f8ef; color: #0b7a3b; border-color: #bfe9cf; }
        .alert-warning { background: #fff6e0; color: #9a6204; border-color: #f5dba4; }
        .alert-info { background: #e6f7fe; color: #0376a3; border-color: #b4e3f8; }
        .btn-close {
            position: absolute;
            right: 10px;
            top: 8px;
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 20px;
            line-height: 1;
            opacity: .7;
            cursor: pointer;
        }
        .btn-close::after { content: '×'; }
        .btn-close:hover { opacity: 1; }

        @media (max-width: 900px) {
            .split { grid-template-columns: 1fr; }
            .showcase { display: none; }
            .login .mobile-logo { display: block; }
            .pane { align-items: start; padding-top: 56px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>

<body>
    <div class="split">
        <aside class="showcase" aria-hidden="true">
            <img class="logo" src="<?= e(logo_src()) ?>" alt="">

            <div>
                <h1>Do orçamento à entrega, <em>tudo num só lugar.</em></h1>
                <p class="lead">Gestão completa para comunicação visual: orçamentos com arte, produção, financeiro e caixa.</p>
                <ul class="features">
                    <li>
                        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg></span>
                        Orçamentos com cálculo por m², metro linear e unidade
                    </li>
                    <li>
                        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/></svg></span>
                        Fila de produção e ordens de serviço automáticas
                    </li>
                    <li>
                        <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v12H3z"/><path d="M3 7l2-3h14l2 3M16 13h2"/></svg></span>
                        Contas a pagar, a receber e fluxo de caixa
                    </li>
                </ul>
            </div>

            <div class="copy">© <?= date('Y') ?> Visual Digital</div>
        </aside>

        <main class="pane">
            <div class="login">
                <img class="mobile-logo" src="<?= e(logo_src()) ?>" alt="Visual Digital">
                <h2>Bem-vindo de volta</h2>
                <p class="sub">Entre com seu e-mail e senha para acessar o painel.</p>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                        <?= e($flash['msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="action" value="login">

                    <div class="field">
                        <label for="email">E-mail</label>
                        <div class="control">
                            <svg class="lead-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            <input id="email" type="email" name="email" placeholder="seu@email.com" autocomplete="username" autofocus required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="senha">Senha</label>
                        <div class="control">
                            <svg class="lead-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            <input id="senha" type="password" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
                            <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar senha" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Entrar no sistema</button>
                </form>

                <div class="login-footer">Visual Pro · Gestão de comunicação visual</div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-bs-dismiss="alert"]');
            if (b) { var a = b.closest('.alert'); if (a) a.remove(); }
        });
        (function () {
            var btn = document.getElementById('togglePass'), input = document.getElementById('senha');
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
            });
        })();
    </script>
</body>

</html>
<?php
    exit;
}
?>

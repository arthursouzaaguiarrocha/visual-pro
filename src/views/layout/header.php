<?php require_once __DIR__ . '/../logo.php'; ?><!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> • Visual Pro</title>
    <link rel="icon" href="<?= e(logo_src()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        <?php
        // CSS do tema embutido no HTML: não depende de onde a pasta assets/ ficou no servidor.
        $__css = __DIR__ . '/../theme.css';
        if (is_file($__css)) {
            readfile($__css);
        }
        ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        if (window.Chart) {
            Chart.defaults.font.family = "Inter, system-ui, sans-serif";
            Chart.defaults.color = "#6c7390";
            Chart.defaults.borderColor = "#e7e9f1";
        }
    </script>
</head>

<body>
    <header class="mobile-bar">
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false"><i class="bi bi-list"></i></button>
        <img src="<?= e(logo_src()) ?>" alt="Visual Pro">
        <strong class="ms-auto" style="color:var(--ink)"><?= e($title) ?></strong>
    </header>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar">
        <div class="brand"><img class="brand-logo" src="<?= e(logo_src()) ?>" alt="Visual Digital" title="Visual Pro"></div>
        <?php
        // [página, ícone, rótulo, perfis com acesso]
        $navGroups = [
            'Geral' => [
                ['dashboard', 'bi-grid-1x2-fill', 'Dashboard', ['admin', 'vendedor', 'producao']],
            ],
            'Comercial' => [
                ['clientes', 'bi-people-fill', 'Clientes', ['admin', 'vendedor']],
                ['orcamentos', 'bi-file-earmark-text-fill', 'Orçamentos', ['admin', 'vendedor']],
                ['vendas', 'bi-graph-up-arrow', 'Vendas', ['admin', 'vendedor']],
            ],
            'Catálogo' => [
                ['produtos', 'bi-box-seam-fill', 'Produtos', ['admin']],
                ['materias', 'bi-layers-fill', 'Matérias', ['admin']],
                ['servicos', 'bi-tools', 'Serviços', ['admin']],
            ],
            'Operação' => [
                ['producao', 'bi-printer-fill', 'Produção / OS', ['admin', 'producao']],
            ],
            'Financeiro' => [
                ['contas_receber', 'bi-arrow-down-circle-fill', 'A receber', ['admin', 'vendedor']],
                ['contas_pagar', 'bi-arrow-up-circle-fill', 'A pagar', ['admin']],
                ['caixa', 'bi-wallet2', 'Caixa', ['admin']],
            ],
            'Administração' => [
                ['usuarios', 'bi-person-gear', 'Usuários', ['admin']],
            ],
        ];
        foreach ($navGroups as $groupName => $navItems):
            $visible = array_filter($navItems, function ($n) {
                return can($n[3]);
            });
            if (!$visible) continue; ?>
            <nav class="nav-group" aria-label="<?= e($groupName) ?>">
                <div class="nav-label"><?= e($groupName) ?></div>
                <?php foreach ($visible as $n): ?>
                    <a class="nav-link <?= $page === $n[0] ? 'active' : '' ?>" href="?page=<?= $n[0] ?>" <?= $page === $n[0] ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= $n[1] ?>" aria-hidden="true"></i>
                        <span class="nav-text"><?= e($n[2]) ?></span>
                    </a>
                <?php endforeach ?>
            </nav>
        <?php endforeach;

        $userNome = (string) ($_SESSION['user']['nome'] ?? '');
        $partes = preg_split('/\s+/', trim($userNome)) ?: [];
        $__sub = function ($t) {
            return function_exists('mb_substr') ? mb_substr($t, 0, 1) : substr($t, 0, 1);
        };
        $iniciais = ($partes[0] ?? '?') !== '' ? ($__sub($partes[0] ?? '?') . (count($partes) > 1 ? $__sub(end($partes)) : '')) : '?';
        $iniciais = function_exists('mb_strtoupper') ? mb_strtoupper($iniciais) : strtoupper($iniciais);
        ?>
        <div class="sidebar-user">
            <div class="user-chip">
                <span class="avatar" aria-hidden="true"><?= e($iniciais) ?></span>
                <div style="min-width:0">
                    <div class="user-name"><?= e($userNome) ?></div>
                    <div class="user-role"><?= e($_SESSION['user']['perfil']) ?></div>
                </div>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-sm btn-logout" title="Sair"><i class="bi bi-box-arrow-right"></i> Sair</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="top">
            <div>
                <div class="crumb"><b>Visual Pro</b> / <?= e($title) ?></div>
                <h2 class="mt-1"><?= e($title) ?></h2>
            </div>
            <div class="today"><i class="bi bi-calendar3"></i><?= date('d/m/Y') ?></div>
        </div><?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show"><?= e($flash['msg']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif ?>

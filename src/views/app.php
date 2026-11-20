<?php
/**
 * Layout principal do painel.
 * Inclui header, a view da página atual e footer.
 *
 * Partials:
 *   layout/header.php  — head, sidebar, abertura do main
 *   pages/{page}.php   — conteúdo de cada módulo
 *   layout/footer.php  — scripts globais e fechamento
 */
declare(strict_types=1);

$page = $page ?? 'dashboard';

// Mapa page → arquivo parcial (produtos e serviços compartilham o catálogo)
$pageFiles = [
    'dashboard'       => 'dashboard',
    'clientes'        => 'clientes',
    'produtos'        => 'catalogo',
    'servicos'        => 'catalogo',
    'materias'        => 'materias',
    'orcamentos'      => 'orcamentos',
    'vendas'          => 'vendas',
    'producao'        => 'producao',
    'caixa'           => 'caixa',
    'contas_receber'  => 'contas_receber',
    'contas_pagar'    => 'contas_pagar',
    'usuarios'        => 'usuarios',
];

$viewName = $pageFiles[$page] ?? 'dashboard';
$viewPath = __DIR__ . '/pages/' . $viewName . '.php';

require __DIR__ . '/layout/header.php';

if (is_file($viewPath)) {
    require $viewPath;
} else {
    echo '<div class="panel"><p class="text-secondary mb-0">Página não encontrada.</p></div>';
}

require __DIR__ . '/layout/footer.php';

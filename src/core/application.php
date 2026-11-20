<?php
declare(strict_types=1);

/**
 * Bootstrap MVC do Visual Pro.
 * Front controllers (public/index.php ou index.php) incluem este arquivo.
 */

date_default_timezone_set('America/Sao_Paulo');

$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $__https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Router.php';

// CSRF: gera o token e injeta automaticamente em todo <form method="post">
csrf_token();
ob_start('csrf_inject');

// Controllers de domínio
foreach ([
    'AuthController',
    'DashboardController',
    'ClientesController',
    'CatalogoController',
    'OrcamentosController',
    'ProducaoController',
    'CaixaController',
    'ContasReceberController',
    'ContasPagarController',
    'UsuariosController',
    'VendasController',
] as $ctrl) {
    require_once __DIR__ . '/../controllers/' . $ctrl . '.php';
}

try {
    $pdo = Database::connection();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h2>Não foi possível conectar ao banco de dados.</h2>';
    echo '<p>Importe <code>database/schema.sql</code> e confira as credenciais em <code>src/config/database.php</code>.</p>';
    error_log('[Visual Pro] Falha de conexão: ' . $e->getMessage());
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_POST['action'] ?? null;

$router = new Router();

// --- POST actions ---
$router
    ->action('login', AuthController::class, 'login')
    ->action('logout', AuthController::class, 'logout')
    ->action('cliente_save', ClientesController::class, 'save')
    ->action('cliente_delete', ClientesController::class, 'delete')
    ->action('produto_save', CatalogoController::class, 'produtoSave')
    ->action('produto_delete', CatalogoController::class, 'produtoDelete')
    ->action('servico_save', CatalogoController::class, 'servicoSave')
    ->action('servico_delete', CatalogoController::class, 'servicoDelete')
    ->action('materia_save', CatalogoController::class, 'materiaSave')
    ->action('materia_delete', CatalogoController::class, 'materiaDelete')
    ->action('materia_reativar', CatalogoController::class, 'materiaReativar')
    ->action('variacao_save', CatalogoController::class, 'variacaoSave')
    ->action('variacao_delete', CatalogoController::class, 'variacaoDelete')
    ->action('custo_extra_save', CatalogoController::class, 'custoExtraSave')
    ->action('composicao_save', CatalogoController::class, 'composicaoSave')
    ->action('composicao_delete', CatalogoController::class, 'composicaoDelete')
    ->action('orcamento_delete', OrcamentosController::class, 'delete')
    ->action('orcamento_save', OrcamentosController::class, 'save')
    ->action('orcamento_update', OrcamentosController::class, 'update')
    ->action('orcamento_status', OrcamentosController::class, 'status')
    ->action('os_update', ProducaoController::class, 'update')
    ->action('os_baixa', ProducaoController::class, 'baixa')
    ->action('caixa_save', CaixaController::class, 'save')
    ->action('caixa_delete', CaixaController::class, 'delete')
    ->action('user_save', UsuariosController::class, 'save')
    ->action('user_update', UsuariosController::class, 'update')
    ->action('user_toggle', UsuariosController::class, 'toggle')
    ->action('conta_receber_save', ContasReceberController::class, 'save')
    ->action('conta_receber_update', ContasReceberController::class, 'update')
    ->action('conta_receber_delete', ContasReceberController::class, 'delete')
    ->action('conta_receber_pagar', ContasReceberController::class, 'pagar')
    ->action('conta_receber_cancelar', ContasReceberController::class, 'cancelar')
    ->action('conta_pagar_save', ContasPagarController::class, 'save')
    ->action('conta_pagar_update', ContasPagarController::class, 'update')
    ->action('conta_pagar_delete', ContasPagarController::class, 'delete')
    ->action('conta_pagar_pagar', ContasPagarController::class, 'pagar')
    ->action('conta_pagar_estornar', ContasPagarController::class, 'estornar')
    ->action('conta_pagar_cancelar', ContasPagarController::class, 'cancelar');

// --- GET pages ---
$router
    ->page('login', AuthController::class, 'showLogin')
    ->page('dashboard', DashboardController::class, 'index', ['admin', 'vendedor', 'producao'])
    ->page('clientes', ClientesController::class, 'index', ['admin', 'vendedor'])
    ->page('produtos', CatalogoController::class, 'produtos', ['admin'])
    ->page('materias', CatalogoController::class, 'materias', ['admin'])
    ->page('servicos', CatalogoController::class, 'servicos', ['admin'])
    ->page('orcamentos', OrcamentosController::class, 'index', ['admin', 'vendedor'])
    ->page('producao', ProducaoController::class, 'index', ['admin', 'producao'])
    ->page('caixa', CaixaController::class, 'index', ['admin'])
    ->page('contas_receber', ContasReceberController::class, 'index', ['admin', 'vendedor'])
    ->page('contas_pagar', ContasPagarController::class, 'index', ['admin'])
    ->page('usuarios', UsuariosController::class, 'index', ['admin'])
    ->page('vendas', VendasController::class, 'index', ['admin', 'vendedor']);

$router->dispatch($pdo, $page, $action);

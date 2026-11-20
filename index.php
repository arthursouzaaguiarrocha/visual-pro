<?php
declare(strict_types=1);

/**
 * Compatibilidade para hospedagem compartilhada (document root = raiz do projeto).
 * Preferencialmente aponte o document root para a pasta /public.
 */

$page = $_GET['page'] ?? 'dashboard';

$routes = [
    'login'           => __DIR__ . '/src/core/application.php',
    'dashboard'       => __DIR__ . '/src/core/application.php',
    'clientes'        => __DIR__ . '/src/core/application.php',
    'produtos'        => __DIR__ . '/src/core/application.php',
    'materias'        => __DIR__ . '/src/core/application.php',
    'servicos'        => __DIR__ . '/src/core/application.php',
    'orcamentos'      => __DIR__ . '/src/core/application.php',
    'vendas'          => __DIR__ . '/src/core/application.php',
    'producao'        => __DIR__ . '/src/core/application.php',
    'caixa'           => __DIR__ . '/src/core/application.php',
    'contas_receber'  => __DIR__ . '/src/core/application.php',
    'contas_pagar'    => __DIR__ . '/src/core/application.php',
    'usuarios'        => __DIR__ . '/src/core/application.php',
    'orcamento_pdf'   => __DIR__ . '/src/controllers/OrcamentoPdfController.php',
    'os_print'        => __DIR__ . '/src/controllers/OsPrintController.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

require $routes[$page];

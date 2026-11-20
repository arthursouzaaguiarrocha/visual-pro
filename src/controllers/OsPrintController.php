<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ?page=login');
    exit;
}
if (!in_array($_SESSION['user']['perfil'] ?? '', ['admin', 'producao'], true)) {
    http_response_code(403);
    echo 'Seu perfil não tem permissão para imprimir OS.';
    exit;
}

function medDec(mixed $v): string
{
    return rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ',');
}

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tipo = $_GET['tipo'] ?? 'geral';
if (!$id || $id < 1) {
    http_response_code(400);
    echo 'OS inválida.';
    exit;
}

$tipoLabel = match ($tipo) {
    'serralheria' => 'SERRALHERIA',
    'instalacao' => 'INSTALAÇÃO',
    default => 'ORDEM DE SERVIÇO',
};

try {
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT os.*, o.descricao, o.imagem_path, o.valor, o.id AS orcamento_num, c.nome AS cliente, c.telefone, c.email, c.contato, c.endereco
        FROM ordens_servico os
        JOIN orcamentos_cv o ON o.id = os.orcamento_id
        JOIN clientes c ON c.id = o.cliente_id
        WHERE os.id = ?');
    $stmt->execute([$id]);
    $os = $stmt->fetch();
    if (!$os) {
        http_response_code(404);
        echo 'Ordem de serviço não encontrada.';
        exit;
    }
    $itens = $pdo->prepare('SELECT produto_nome, unidade, largura, altura, quantidade, preco_unitario, total FROM orcamento_itens_cv WHERE orcamento_id = ? ORDER BY id');
    $itens->execute([$os['orcamento_id']]);
    $listaItens = $itens->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[Visual Pro] Erro ao carregar OS: ' . $e->getMessage());
    echo 'Não foi possível carregar a OS. Tente novamente.';
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>OS #<?= str_pad((string)$id, 5, '0', STR_PAD_LEFT) ?> — <?= e($tipoLabel) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; color: #1a1a2e; font-size: 13px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #00AFEF; padding-bottom: 12px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 22px; color: #3E4095; }
        .badge { display: inline-block; background: #00AFEF; color: #fff; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 12px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-bottom: 16px; }
        .box { border: 1px solid #e1e7ef; border-radius: 6px; padding: 10px 12px; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6c7390; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e1e7ef; padding: 6px 8px; text-align: left; }
        th { background: #F5F7FC; font-size: 11px; text-transform: uppercase; color: #6c7390; }
        .obs { background: #F5F7FC; border-left: 3px solid #E62129; padding: 10px 12px; margin-top: 14px; border-radius: 4px; }
        .footer { margin-top: 28px; display: flex; justify-content: space-between; gap: 40px; }
        .sign { border-top: 1px solid #8491a3; padding-top: 6px; width: 220px; text-align: center; font-size: 11px; color: #6c7390; }
        @media print {
            body { margin: 12px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:16px">
        <button onclick="window.print()" style="padding:8px 16px;background:#00AFEF;color:#fff;border:0;border-radius:6px;cursor:pointer;font-weight:600">Imprimir</button>
        <button onclick="window.close()" style="padding:8px 16px;margin-left:8px;background:#e1e7ef;border:0;border-radius:6px;cursor:pointer">Fechar</button>
    </div>

    <div class="header">
        <div>
            <h1>OS #<?= str_pad((string)$id, 5, '0', STR_PAD_LEFT) ?></h1>
            <div style="margin-top:4px">Orçamento #<?= (int)$os['orcamento_num'] ?> · <?= e(date('d/m/Y H:i')) ?></div>
        </div>
        <div style="text-align:right">
            <span class="badge"><?= e($tipoLabel) ?></span>
            <div style="margin-top:8px;font-weight:600"><?= e(str_replace('_', ' ', ucfirst($os['status']))) ?></div>
            <div style="font-size:12px;color:#6c7390">Prioridade: <?= e(ucfirst($os['prioridade'] ?? 'normal')) ?></div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <h3>Cliente</h3>
            <div><strong><?= e($os['cliente']) ?></strong></div>
            <?php if (!empty($os['contato'])): ?><div>Contato: <?= e($os['contato']) ?></div><?php endif ?>
            <div>Tel: <?= e($os['telefone'] ?: '—') ?></div>
            <?php if (!empty($os['email'])): ?><div>E-mail: <?= e($os['email']) ?></div><?php endif ?>
            <?php if (!empty($os['endereco'])): ?><div>Endereço: <?= e($os['endereco']) ?></div><?php endif ?>
        </div>
        <div class="box">
            <h3>Prazos</h3>
            <div>Prazo: <strong><?= $os['prazo'] ? e(date('d/m/Y', strtotime($os['prazo']))) : '—' ?></strong></div>
            <div>Entrega: <strong><?= !empty($os['data_entrega']) ? e(date('d/m/Y H:i', strtotime($os['data_entrega']))) : '—' ?></strong></div>
            <div style="margin-top:6px">Valor total: <strong>R$ <?= number_format((float)$os['valor'], 2, ',', '.') ?></strong></div>
        </div>
    </div>

    <div class="box">
        <h3>Itens do serviço</h3>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Medidas</th>
                    <th>Qtd</th>
                    <th>Unidade</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($listaItens as $it):
                $med = '—';
                if (($it['unidade'] ?? '') === 'm2') {
                    $med = medDec($it['largura']) . ' × ' . medDec($it['altura']) . ' m';
                } elseif (($it['unidade'] ?? '') === 'metro_linear') {
                    $med = medDec($it['largura']) . ' m';
                }
                $un = ['m2' => 'm²', 'metro_linear' => 'm linear', 'unidade' => 'un'][$it['unidade'] ?? ''] ?? ($it['unidade'] ?? '—');
            ?>
                <tr>
                    <td><?= e($it['produto_nome']) ?></td>
                    <td><?= e($med) ?></td>
                    <td><?= e(number_format((float)$it['quantidade'], 2, ',', '.')) ?></td>
                    <td><?= e($un) ?></td>
                </tr>
            <?php endforeach ?>
            <?php if (!$listaItens): ?>
                <tr><td colspan="4" style="text-align:center;color:#8491a3">Sem itens detalhados</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($os['descricao']) || !empty($os['observacoes'])): ?>
    <div class="obs">
        <?php if (!empty($os['descricao'])): ?><div><strong>Descrição do trabalho:</strong> <?= e($os['descricao']) ?></div><?php endif ?>
        <?php if (!empty($os['observacoes'])): ?><div style="margin-top:6px"><strong>Observações:</strong> <?= e($os['observacoes']) ?></div><?php endif ?>
    </div>
    <?php endif ?>

    <?php if ($tipo === 'serralheria'): ?>
    <div class="box" style="margin-top:14px">
        <h3>Checklist Serralheria</h3>
        <div>□ Corte &nbsp;&nbsp; □ Solda &nbsp;&nbsp; □ Acabamento &nbsp;&nbsp; □ Pintura &nbsp;&nbsp; □ Conferência</div>
    </div>
    <?php elseif ($tipo === 'instalacao'): ?>
    <div class="box" style="margin-top:14px">
        <h3>Checklist Instalação</h3>
        <div>□ Ferramentas &nbsp;&nbsp; □ Material no local &nbsp;&nbsp; □ Fixação &nbsp;&nbsp; □ Nivelamento &nbsp;&nbsp; □ Cliente presente &nbsp;&nbsp; □ Foto final</div>
    </div>
    <?php endif ?>

    <div class="footer">
        <div class="sign">Responsável produção</div>
        <div class="sign">Responsável <?= $tipo === 'instalacao' ? 'instalação' : 'serralheria' ?></div>
        <div class="sign">Cliente / Recebido</div>
    </div>
</body>
</html>
<?php
exit;

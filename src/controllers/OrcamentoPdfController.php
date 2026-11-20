<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header('Location: ?page=login');
    exit;
}
if (!in_array($_SESSION['user']['perfil'] ?? '', ['admin', 'vendedor'], true)) {
    http_response_code(403);
    echo 'Seu perfil não tem permissão para visualizar orçamentos.';
    exit;
}

if (!function_exists('mb_internal_encoding')) {
    http_response_code(500);
    echo 'A extensão mbstring do PHP está desativada. Ative extension=mbstring no php.ini do XAMPP, reinicie o Apache e tente novamente.';
    exit;
}

function pdfEscape(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/** Medida em metros com até 3 casas, sem zeros sobrando (2,5 / 1,25 / 2,505). */
function pdfDec(mixed $value): string
{
    return rtrim(rtrim(number_format((float)$value, 3, ',', '.'), '0'), ',');
}
function pdfMoney(mixed $value): string
{
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo 'Número de orçamento inválido.';
    exit;
}

try {
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT o.*, c.nome AS cliente, c.telefone, c.email, c.contato, c.endereco FROM orcamentos_cv o INNER JOIN clientes c ON c.id=o.cliente_id WHERE o.id=?');
    $stmt->execute([$id]);
    $orcamento = $stmt->fetch();
    if (!$orcamento) {
        http_response_code(404);
        echo 'Orçamento não encontrado.';
        exit;
    }
    $stmt = $pdo->prepare('SELECT produto_nome, unidade, largura, altura, espessura, quantidade, preco_unitario, total FROM orcamento_itens_cv WHERE orcamento_id=? ORDER BY id');
    $stmt->execute([$id]);
    $itens = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Erro ao gerar PDF de orçamento: ' . $e->getMessage());
    http_response_code(500);
    echo 'Não foi possível carregar os dados do orçamento. Confira o banco de dados e importe as atualizações do schema.sql.';
    exit;
}

$linhas = '';
foreach ($itens as $item) {
    $medidas = '—';
    if ($item['unidade'] === 'm2') {
        $medidas = pdfDec($item['largura']) . ' m × ' . pdfDec($item['altura']) . ' m';
    } elseif ($item['unidade'] === 'metro_linear') {
        $medidas = pdfDec($item['largura']) . ' m';
    }
    if ((float)($item['espessura'] ?? 0) > 0) {
        $esp = pdfDec($item['espessura']) . ' m (esp.)';
        $medidas = $medidas === '—' ? $esp : $medidas . ' × ' . $esp;
    }
    $unidade = ['m2' => 'm²', 'metro_linear' => 'metro linear', 'unidade' => 'unidade'][$item['unidade']] ?? $item['unidade'];
    $linhas .= '<tr><td>' . pdfEscape($item['produto_nome']) . '</td><td>' . pdfEscape($medidas) . '</td><td>' . pdfEscape($item['quantidade']) . '</td><td>' . pdfEscape($unidade) . '</td><td>' . pdfMoney($item['preco_unitario']) . '</td><td class="right">' . pdfMoney($item['total']) . '</td></tr>';
}
if (!$linhas) {
    // Orçamentos criados em versões anteriores não possuíam itens detalhados.
    $linhas = '<tr><td>' . pdfEscape($orcamento['descricao']) . '</td><td>—</td><td>1</td><td>serviço</td><td>' . pdfMoney($orcamento['valor']) . '</td><td class="right">' . pdfMoney($orcamento['valor']) . '</td></tr>';
}


$status = ucfirst((string)$orcamento['status']);
$arteHtml = '';
if (!empty($orcamento['imagem_path'])) {

    // Caminho relativo ao projeto (ajuste se necessário)
    $baseDir = dirname(__DIR__, 2) . '/public/';
    $resultado = preg_replace('/[\/\\\\]/', '/', $baseDir);
    $arteFile = $resultado . $orcamento['imagem_path'];


    if (is_file($arteFile) && is_readable($arteFile)) {

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arteFile);

        // Dompdf lida bem com JPEG e PNG. WebP costuma falhar.
        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {

            $data = base64_encode((string) file_get_contents($arteFile));

            $arteHtml = '
                <div class="section">
                    <div class="section-title">ARTE DE REFERÊNCIA DO SERVIÇO</div>
                    <div class="art-wrap">
                        <img class="art-image"
                             src="data:' . $mime . ';base64,' . $data . '"
                             alt="Arte do serviço">
                    </div>
                </div>
            ';
        } elseif ($mime === 'image/webp') {
            // Opcional: converter WebP para PNG em tempo real (precisa da extensão GD ou Imagick)
            // Ou avise o usuário para salvar a arte como JPG/PNG
        }
    }
}
// Logo em PNG para maior compatibilidade com o Dompdf.
$logoHtml = '';
$logoCandidates = [
    dirname(__DIR__, 2) . '/public/assets/logo-visual.png',
    dirname(__DIR__, 2) . '/src/views/logo-visual.png',
    dirname(__DIR__, 2) . '/assets/logo-visual.png',
];
foreach ($logoCandidates as $logoFile) {
    if (is_file($logoFile) && is_readable($logoFile)) {
        $logoData = file_get_contents($logoFile);
        if ($logoData !== false) {
            $logoHtml = '<img class="logo-pdf" src="data:image/png;base64,'
                . base64_encode($logoData) . '" alt="Visual Pro">';
            break;
        }
    }
}

// Dados da empresa (ajuste conforme necessário)
$empresa = [
    'razao' => 'Visual Digital Comunicação Visual',
    'cnpj' => '08.850.185/0001-35',
    'endereco' => 'Rua 04, nº1246 - centro',
    'cidade' => 'Formoso do Araguaia - TO / CEP: 77470-000',
    'telefone' => '(63) 98402-1062',
    'email' => 'visualformoso01@gmail.com',
];

$html = '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><style>
@page{margin:28px 32px 50px 32px}
body{font-family:DejaVu Sans,sans-serif;color:#243247;font-size:11px;line-height:1.45}
.top{background:#3E4095;color:#fff;padding:18px 22px;border-radius:10px;margin-bottom:4px}
.top-inner{display:table;width:100%}
.top-left,.top-right{display:table-cell;vertical-align:middle}
.top-right{text-align:right}
.brand{font-size:20px;font-weight:bold;letter-spacing:.3px}
.logo-pdf{width:140px;height:60px;object-fit:contain}
.muted{color:#68708A}.top .muted{color:#c8cbf0}
.number strong{font-size:18px;letter-spacing:1px}
.empresa-box{background:#F5F7FC;border:1px solid #e1e7ef;border-radius:8px;padding:10px 14px;margin:12px 0 4px;font-size:9.5px;color:#4a5568}
.empresa-box strong{color:#3E4095}
.section{margin-top:18px}
.section-title{font-size:11px;font-weight:bold;color:#3E4095;border-bottom:2px solid #00AFEF;padding-bottom:6px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em}
.info{width:100%;border-collapse:collapse}
.info td{padding:3px 0;vertical-align:top;width:50%}
.items{width:100%;border-collapse:collapse;margin-top:6px}
.items th{background:#3E4095;color:#fff;text-align:left;font-size:9px;text-transform:uppercase;padding:8px 7px}
.items td{padding:8px 7px;border-bottom:1px solid #E2E6F2}
.items tbody tr:nth-child(even){background:#F8F9FC}
.right{text-align:right;white-space:nowrap}
.totals{margin-top:14px;margin-left:auto;width:240px;border-collapse:collapse}
.totals td{padding:5px 8px}
.grand{background:#E3F7FF;color:#3E4095;font-size:14px;font-weight:bold;border-top:2px solid #00AFEF}
.note{background:#F5F7FC;border-left:3px solid #E62129;padding:10px 12px;border-radius:4px;line-height:1.55;font-size:10.5px}
.art-wrap{border:1px solid #e1e7ef;padding:8px;text-align:center;border-radius:6px;background:#fff}
.art-image{max-width:100%;max-height:260px}
.footer{position:fixed;bottom:0;left:0;right:0;text-align:center;color:#8491a3;font-size:8.5px;border-top:1px solid #e1e7ef;padding-top:6px;line-height:1.4}
.footer strong{color:#3E4095}
</style></head><body>
<div class="top"><div class="top-inner">
<div class="top-left"><div class="brand">' . $logoHtml . '</div><div class="muted" style="margin-top:4px">Comunicação visual • Proposta comercial</div></div>
<div class="top-right"><div class="muted">ORÇAMENTO</div><strong style="font-size:18px">#' . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . '</strong><br><span class="muted">' . date('d/m/Y', strtotime((string)$orcamento['criado_em'])) . '</span></div>
</div></div>

<div class="empresa-box">
<strong>' . pdfEscape($empresa['razao']) . '</strong> &nbsp;|&nbsp; CNPJ: ' . pdfEscape($empresa['cnpj']) . '<br>
' . pdfEscape($empresa['endereco']) . ' — ' . pdfEscape($empresa['cidade']) . '<br>
Tel: ' . pdfEscape($empresa['telefone']) . ' &nbsp;|&nbsp; ' . pdfEscape($empresa['email']) . ' &nbsp;|&nbsp; ' . pdfEscape($empresa['site']) . '
</div>

<div class="section"><div class="section-title">Dados do cliente</div>
<table class="info">
<tr><td><strong>Cliente:</strong> ' . pdfEscape($orcamento['cliente']) . '</td><td><strong>Status:</strong> ' . pdfEscape($status) . '</td></tr>
<tr><td><strong>Contato:</strong> ' . pdfEscape($orcamento['contato'] ?? '—') . '</td><td><strong>Telefone:</strong> ' . pdfEscape($orcamento['telefone'] ?: '—') . '</td></tr>
<tr><td><strong>E-mail:</strong> ' . pdfEscape($orcamento['email'] ?: '—') . '</td><td><strong>Endereço:</strong> ' . pdfEscape($orcamento['endereco'] ?? '—') . '</td></tr>
</table></div>

<div class="section"><div class="section-title">Produtos e serviços</div>
<table class="items"><thead><tr><th>Descrição</th><th>Medidas</th><th>Qtd.</th><th>Unidade</th><th>Preço base</th><th class="right">Total</th></tr></thead><tbody>' . $linhas . '</tbody></table></div>

<table class="totals">
<tr><td class="muted">Subtotal</td><td class="right">' . pdfMoney($orcamento['valor']) . '</td></tr>
<tr class="grand"><td>TOTAL</td><td class="right">' . pdfMoney($orcamento['valor']) . '</td></tr>
</table>

' . $arteHtml . '

<div class="section"><div class="section-title">Observações</div>
<div class="note">' . pdfEscape($orcamento['descricao'] ?: 'Sem observações adicionais.') . '<br><br>Valores válidos conforme as condições acordadas com o cliente. Prazo de entrega e forma de pagamento a combinar.</div></div>

<div class="footer">
<strong>' . pdfEscape($empresa['razao']) . '</strong> · CNPJ ' . pdfEscape($empresa['cnpj']) . '<br>
' . pdfEscape($empresa['endereco']) . ' · ' . pdfEscape($empresa['telefone']) . ' · ' . pdfEscape($empresa['email']) . '<br>
Documento gerado em ' . date('d/m/Y H:i') . ' · Visual Pro
</div>
</body></html>';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
// Pré-visualização (inline) por padrão; baixa só com ?download=1
$dompdf->stream('orcamento-' . $id . '.pdf', ['Attachment' => !empty($_GET['download'])]);
exit;

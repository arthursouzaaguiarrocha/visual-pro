<?php
declare(strict_types=1);

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function money($v): string
{
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}
function go(string $page = 'dashboard'): never
{
    header('Location: ?page=' . urlencode($page));
    exit;
}
function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function require_login(): void
{
    if (empty($_SESSION['user'])) go('login');
}
function role(): string
{
    return $_SESSION['user']['perfil'] ?? '';
}
function can(array $roles): bool
{
    return in_array(role(), $roles, true);
}
function require_role(array $roles): void
{
    require_login();
    if (!can($roles)) {
        http_response_code(403);
        echo '<h1>Acesso negado</h1><p>Seu perfil não pode acessar esta área.</p><a href="?page=dashboard">Voltar</a>';
        exit;
    }
}

function fator_modo(string $modo, float $w, float $h, float $e, float $q): float
{
    $q = max(0.01, $q);
    $w = max(0.0, $w);
    $h = max(0.0, $h);
    $e = max(0.0, $e);
    return match ($modo) {
        'por_m2' => max(0.0001, $w * $h) * $q,
        'por_m2_le' => max(0.0001, $w * $e) * $q,
        'por_m2_ae' => max(0.0001, $h * $e) * $q,
        'por_metro', 'por_largura' => max(0.0001, $w) * $q,
        'por_altura' => max(0.0001, $h) * $q,
        'por_espessura' => max(0.0001, $e) * $q,
        'perimetro_la' => max(0.0001, ($w + $h) * 2) * $q,
        'perimetro_le' => max(0.0001, ($w + $e) * 2) * $q,
        'perimetro_ae' => max(0.0001, ($h + $e) * 2) * $q,
        'por_unidade', 'fixo', 'unidade' => $q,
        default => $q,
    };
}

function calc_linhas_materias(array $rows, float $largura, float $altura, float $espessura, float $quantidade): array
{
    $custo = 0.0;
    $detalhes = [];
    foreach ($rows as $it) {
        $base = (float)$it['quantidade'];
        $fator = fator_modo((string)$it['modo'], $largura, $altura, $espessura, $quantidade);
        $qtdUsada = $base * $fator;
        $custoLinha = $qtdUsada * (float)$it['custo'];
        $custo += $custoLinha;
        $detalhes[] = [
            'materia' => $it['materia_nome'] ?? ($it['nome'] ?? 'Matéria'),
            'modo' => $it['modo'],
            'qtd' => round($qtdUsada, 4),
            'custo_unit' => (float)$it['custo'],
            'custo' => round($custoLinha, 4),
        ];
    }
    return ['custo' => round($custo, 4), 'detalhes' => $detalhes];
}

/** @param int[] $variacaoIds */
function calc_composicao(PDO $pdo, int $composicaoId, float $largura, float $altura, float $quantidade, float $espessura = 0.0, array $variacaoIds = []): array
{
    $c = $pdo->prepare('SELECT * FROM composicoes WHERE id=? AND ativo=1');
    $c->execute([$composicaoId]);
    $comp = $c->fetch();
    if (!$comp) throw new RuntimeException('Composição não encontrada.');
    $itens = $pdo->prepare('SELECT ci.*, m.nome materia_nome, m.custo, m.unidade materia_unidade FROM composicao_itens ci JOIN materias_primas m ON m.id=ci.materia_id WHERE ci.composicao_id=? AND m.ativo=1');
    $itens->execute([$composicaoId]);
    $rows = $itens->fetchAll();
    if (!$rows) throw new RuntimeException('Composição sem matérias-primas cadastradas.');

    $baseCalc = calc_linhas_materias($rows, $largura, $altura, $espessura, $quantidade);
    $custo = $baseCalc['custo'];
    $detalhes = $baseCalc['detalhes'];
    $variacoesNomes = [];

    $variacaoIds = array_values(array_unique(array_filter(array_map('intval', $variacaoIds))));
    if ($variacaoIds) {
        $in = implode(',', $variacaoIds);
        $vars = $pdo->query("SELECT * FROM composicao_variacoes WHERE ativo=1 AND composicao_id=" . (int)$composicaoId . " AND id IN ($in)")->fetchAll();
        foreach ($vars as $v) {
            $variacoesNomes[] = $v['nome'];
            $vi = $pdo->prepare('SELECT cvi.*, m.nome materia_nome, m.custo FROM composicao_variacao_itens cvi JOIN materias_primas m ON m.id=cvi.materia_id WHERE cvi.variacao_id=? AND m.ativo=1');
            $vi->execute([(int)$v['id']]);
            $extra = calc_linhas_materias($vi->fetchAll(), $largura, $altura, $espessura, $quantidade);
            $custo += $extra['custo'];
            foreach ($extra['detalhes'] as $d) {
                $d['materia'] = $d['materia'] . ' [' . $v['nome'] . ']';
                $detalhes[] = $d;
            }
        }
    }

    $custoMaterias = $custo;
    $outros = [];
    try {
        $cx = $pdo->prepare('SELECT * FROM composicao_custos WHERE composicao_id=? ORDER BY id');
        $cx->execute([$composicaoId]);
        foreach ($cx->fetchAll() as $custoExtra) {
            $val = (float)$custoExtra['valor'];
            $modoC = (string)$custoExtra['modo'];
            if ($modoC === 'percentual_materias') {
                $linha = $custoMaterias * ($val / 100);
            } else {
                $fator = fator_modo($modoC === 'fixo' ? 'unidade' : $modoC, $largura, $altura, $espessura, $quantidade);
                // map modes
                if ($modoC === 'fixo') $fator = max(0.01, $quantidade);
                elseif ($modoC === 'por_unidade') $fator = max(0.01, $quantidade);
                elseif ($modoC === 'por_largura') $fator = max(0.0001, $largura) * max(0.01, $quantidade);
                elseif ($modoC === 'por_altura') $fator = max(0.0001, $altura) * max(0.01, $quantidade);
                elseif ($modoC === 'por_m2') $fator = max(0.0001, $largura * $altura) * max(0.01, $quantidade);
                else $fator = max(0.01, $quantidade);
                $linha = $val * $fator;
            }
            $custo += $linha;
            $outros[] = [
                'materia' => $custoExtra['nome'] . ' (outro custo)',
                'modo' => $modoC,
                'qtd' => 1,
                'custo_unit' => round($linha, 4),
                'custo' => round($linha, 4),
            ];
            $detalhes[] = end($outros);
        }
    } catch (Throwable $e) {
        // tabela ainda não migrada
    }

    $margem = (float)$comp['margem_percentual'];
    $preco = round($custo * (1 + $margem / 100), 2);
    $nome = $comp['nome'];
    if ($variacoesNomes) $nome .= ' + ' . implode(', ', $variacoesNomes);
    return [
        'comp' => $comp,
        'nome' => $nome,
        'custo' => round($custo, 4),
        'custo_materias' => round($custoMaterias, 4),
        'custo_outros' => round($custo - $custoMaterias, 4),
        'preco' => $preco,
        'margem' => $margem,
        'detalhes' => $detalhes,
        'outros' => $outros,
        'variacoes' => $variacaoIds,
        'variacoes_nomes' => $variacoesNomes,
    ];
}

/* ---------- Segurança: CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Callback de output buffering: injeta o token em todo <form method="post">. */
function csrf_inject(string $html): string
{
    if (stripos($html, '<form') === false) {
        return $html;
    }
    $field = '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
    return preg_replace('/(<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>)/i', '$1' . $field, $html) ?? $html;
}

function csrf_verify(): void
{
    $sent = (string)($_POST['_csrf'] ?? '');
    $ok = !empty($_SESSION['csrf']) && $sent !== '' && hash_equals((string)$_SESSION['csrf'], $sent);
    if (!$ok) {
        throw new RuntimeException('Sessão expirada ou formulário inválido. Recarregue a página e tente novamente.');
    }
}

/**
 * URL pública de um arquivo dentro de /public (ex.: uploads/artes/x.png),
 * funcionando tanto com document root em /public quanto na raiz do projeto.
 */
function public_url(string $rel): string
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $rootIsPublic = basename(dirname((string)($_SERVER['SCRIPT_FILENAME'] ?? ''))) === 'public';
    return $base . '/' . ($rootIsPublic ? '' : 'public/') . $rel;
}

/**
 * Converte número digitado em formato BR ou internacional para float.
 * Aceita "2,5", "2.5", "1.234,5". Valores negativos viram 0.
 */
function dec_float(mixed $v): float
{
    $s = preg_replace('/\s+/', '', (string)$v) ?? '';
    if ($s === '') return 0.0;
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);   // pontos = separador de milhar
        $s = str_replace(',', '.', $s);  // vírgula = decimal
    }
    if (!is_numeric($s)) return 0.0;
    return max(0.0, (float)$s);
}

/**
 * Garante a coluna orcamentos_cv.aprovado_em (data da venda). Se a migração ainda não
 * foi executada, tenta criá-la sozinha; se o usuário do banco não puder, devolve false
 * e o sistema continua funcionando usando atualizado_em.
 */
function ensure_aprovado_em(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        if ($pdo->query("SHOW COLUMNS FROM orcamentos_cv LIKE 'aprovado_em'")->fetch()) {
            return $ok = true;
        }
        $pdo->exec('ALTER TABLE orcamentos_cv ADD COLUMN aprovado_em DATETIME NULL');
    } catch (Throwable $e) {
        error_log('[Visual Pro] não foi possível criar aprovado_em: ' . $e->getMessage());
        return $ok = false;
    }
    try {
        $pdo->exec("UPDATE orcamentos_cv SET aprovado_em = atualizado_em WHERE status='aprovado' AND aprovado_em IS NULL");
    } catch (Throwable $e) {
        error_log('[Visual Pro] backfill aprovado_em: ' . $e->getMessage());
    }
    return $ok = true;
}

/**
 * Histórico de pagamentos das contas a pagar (cada pagamento, vale ou adiantamento).
 * Cria a tabela automaticamente se ainda não existir.
 */
function ensure_pagamentos_conta_pagar(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contas_pagar_pagamentos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            conta_id INT UNSIGNED NOT NULL,
            valor DECIMAL(12,2) NOT NULL,
            data_pagamento DATE NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT 'pagamento',
            forma VARCHAR(30) NULL,
            observacao VARCHAR(255) NULL,
            caixa_id INT UNSIGNED NULL,
            usuario_id INT UNSIGNED NULL,
            criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conta (conta_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return $ok = true;
    } catch (Throwable $e) {
        error_log('[Visual Pro] histórico de pagamentos indisponível: ' . $e->getMessage());
        return $ok = false;
    }
}

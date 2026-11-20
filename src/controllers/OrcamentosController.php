<?php

declare(strict_types=1);

class OrcamentosController extends Controller
{
    /** @var string|null caminho absoluto do último upload (para rollback) */
    public ?string $lastUploadAbsolute = null;

    public function delete(): void
    {

        $this->requireRole(['admin', 'vendedor']);
        $id = (int)($_POST['id'] ?? 0);
        $check = $this->pdo->prepare('SELECT COUNT(*) FROM ordens_servico WHERE orcamento_id=?');
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) throw new RuntimeException('Este orçamento já possui ordem de serviço. Altere o status para cancelado.');
        $s = $this->pdo->prepare("UPDATE orcamentos_cv SET status='cancelado' WHERE id=? AND status IN ('pendente','rejeitado')");
        $s->execute([$id]);
        if (!$s->rowCount()) throw new RuntimeException('Só é possível cancelar orçamentos pendentes ou rejeitados.');
        flash('Orçamento cancelado.');
        go('orcamentos');
    }

    public function save(): void
    {

        $this->requireRole(['admin', 'vendedor']);
        $cid = (int)($_POST['cliente_id'] ?? 0);
        $observacao = trim($_POST['descricao'] ?? '');
        if (!$cid) throw new RuntimeException('Selecione um cliente.');
        // Arte de referência do serviço: somente imagens raster permitidas, até 5 MB.
        $imagemRelativa = null;
        $imagemAbsoluta = null;
        if (isset($_FILES['imagem_arte']) && $_FILES['imagem_arte']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['imagem_arte'];
            if ($upload['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível enviar a imagem. Tente novamente.');
            if ((int)$upload['size'] > 5 * 1024 * 1024) throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
            if (!is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('Arquivo de imagem inválido.');
            $imageInfo = @getimagesize($upload['tmp_name']);
            $mime = $imageInfo['mime'] ?? '';
            $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!$imageInfo || !isset($exts[$mime])) throw new RuntimeException('Use uma imagem JPG ou PNG.');
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/artes';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
            $fileName = bin2hex(random_bytes(16)) . '.' . $exts[$mime];
            $imagemAbsoluta = $uploadDir . '/' . $fileName;
            $this->lastUploadAbsolute = $imagemAbsoluta;
            if (!move_uploaded_file($upload['tmp_name'], $imagemAbsoluta)) throw new RuntimeException('Falha ao salvar a imagem enviada.');
            $imagemRelativa = 'uploads/artes/' . $fileName;
        }
        $check = $this->pdo->prepare('SELECT id FROM clientes WHERE id=?');
        $check->execute([$cid]);
        if (!$check->fetchColumn()) throw new RuntimeException('Cliente não encontrado.');
        $refs = $_POST['item_ref'] ?? ($_POST['produto_id'] ?? []);
        $larguras = $_POST['largura'] ?? [];
        $alturas = $_POST['altura'] ?? [];
        $espessuras = $_POST['espessura'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $variacoesPost = $_POST['variacoes'] ?? []; // variacoes[i][] = id
        if (!is_array($refs) || count($refs) === 0) throw new RuntimeException('Adicione pelo menos um item ao orçamento.');
        $items = [];
        $total = 0.0;
        $descricoes = [];
        $find = $this->pdo->prepare('SELECT id,nome,unidade,preco FROM produtos WHERE id=? AND ativo=1');
        foreach ($refs as $i => $ref) {
            $ref = trim((string)$ref);
            if ($ref === '' || $ref === '0') continue;
            $w = dec_float($larguras[$i] ?? 0);
            $h = dec_float($alturas[$i] ?? 0);
            $q = dec_float($quantidades[$i] ?? 1);
            if ($q <= 0) throw new RuntimeException('A quantidade deve ser maior que zero.');
            // composição: c:ID  | produto: p:ID ou só número
            $e = dec_float($espessuras[$i] ?? 0);
            $varsLinha = [];
            if (isset($variacoesPost[$i]) && is_array($variacoesPost[$i])) {
                $varsLinha = array_map('intval', $variacoesPost[$i]);
            } elseif (isset($variacoesPost[(string)$i]) && is_array($variacoesPost[(string)$i])) {
                $varsLinha = array_map('intval', $variacoesPost[(string)$i]);
            }
            if (str_starts_with($ref, 'c:')) {
                $compId = (int)substr($ref, 2);
                $calc = calc_composicao($this->pdo, $compId, $w, $h, $q, $e, $varsLinha);
                $comp = $calc['comp'];
                $unidade = $comp['unidade'];
                if ($unidade === 'm2' && ($w <= 0 || $h <= 0)) throw new RuntimeException('Informe largura e altura (m) para composições por m².');
                if ($unidade === 'metro_linear' && $w <= 0) throw new RuntimeException('Informe a largura/comprimento para composições por metro linear.');
                $line = $calc['preco'];
                $total += $line;
                $items[] = ['id' => null, 'composicao_id' => $compId, 'variacoes_json' => $varsLinha ? json_encode($varsLinha) : null, 'nome' => $calc['nome'], 'unidade' => $unidade, 'largura' => $w ?: null, 'altura' => $h ?: null, 'espessura' => $e ?: null, 'quantidade' => $q, 'preco' => $q > 0 ? round($line / $q, 2) : $line, 'total' => $line, 'detalhes' => $calc['detalhes']];
                $descricoes[] = $calc['nome'] . ' (' . $q . ' un.)';
            } else {
                $pid = (int)(str_starts_with($ref, 'p:') ? substr($ref, 2) : $ref);
                if ($pid <= 0) continue;
                $find->execute([$pid]);
                $prod = $find->fetch();
                if (!$prod) throw new RuntimeException('Um dos produtos selecionados não existe ou está inativo.');
                if ($prod['unidade'] === 'm2' && ($w <= 0 || $h <= 0)) throw new RuntimeException('Informe largura e altura para produtos cobrados por m².');
                if ($prod['unidade'] === 'metro_linear' && $w <= 0) throw new RuntimeException('Informe o comprimento/largura para produtos cobrados por metro linear.');
                $base = match ($prod['unidade']) {
                    'm2' => $w * $h,
                    'metro_linear' => $w,
                    default => 1.0
                };
                $line = round($base * (float)$prod['preco'] * $q, 2);
                $total += $line;
                $items[] = ['id' => $pid, 'composicao_id' => null, 'variacoes_json' => null, 'nome' => $prod['nome'], 'unidade' => $prod['unidade'], 'largura' => $w ?: null, 'altura' => $h ?: null, 'espessura' => $e ?: null, 'quantidade' => $q, 'preco' => (float)$prod['preco'], 'total' => $line, 'detalhes' => []];
                $descricoes[] = $prod['nome'] . ' (' . $q . ' un.)';
            }
        }
        if (!$items) throw new RuntimeException('Adicione pelo menos um item válido.');
        if ($observacao !== '') $descricoes[] = 'Observações: ' . $observacao;
        $desc = implode(' | ', $descricoes);
        $this->pdo->beginTransaction();
        $s = $this->pdo->prepare('INSERT INTO orcamentos_cv(cliente_id,vendedor_id,descricao,imagem_path,valor) VALUES(?,?,?,?,?)');
        $s->execute([$cid, $_SESSION['user']['id'], $desc, $imagemRelativa, $total]);
        $orcId = (int)$this->pdo->lastInsertId();
        $ins = $this->pdo->prepare('INSERT INTO orcamento_itens_cv(orcamento_id,produto_id,composicao_id,variacoes_json,produto_nome,unidade,largura,altura,espessura,quantidade,preco_unitario,total) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($items as $it) $ins->execute([$orcId, $it['id'] ?? null, $it['composicao_id'] ?? null, $it['variacoes_json'] ?? null, $it['nome'], $it['unidade'], $it['largura'], $it['altura'], $it['espessura'] ?? null, $it['quantidade'], $it['preco'], $it['total']]);
        $this->pdo->commit();
        flash('Orçamento criado com os produtos, valores e arte anexada.');
        go('orcamentos');
    }

    public function update(): void
    {

        $this->requireRole(['admin', 'vendedor']);
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Orçamento inválido.');
        $this->pdo->beginTransaction();
        $cur = $this->pdo->prepare("SELECT * FROM orcamentos_cv WHERE id=? FOR UPDATE");
        $cur->execute([$id]);
        $orc = $cur->fetch();
        if (!$orc) {
            $this->pdo->rollBack();
            throw new RuntimeException('Orçamento não encontrado.');
        }
        if (!in_array($orc['status'], ['pendente', 'rejeitado'], true)) {
            $this->pdo->rollBack();
            throw new RuntimeException('Só é possível editar orçamentos pendentes ou rejeitados.');
        }
        $cid = (int)($_POST['cliente_id'] ?? 0);
        $observacao = trim($_POST['descricao'] ?? '');
        if (!$cid) {
            $this->pdo->rollBack();
            throw new RuntimeException('Selecione um cliente.');
        }
        $check = $this->pdo->prepare('SELECT id FROM clientes WHERE id=?');
        $check->execute([$cid]);
        if (!$check->fetchColumn()) {
            $this->pdo->rollBack();
            throw new RuntimeException('Cliente não encontrado.');
        }

        $imagemRelativa = $orc['imagem_path'];
        if (isset($_FILES['imagem_arte']) && $_FILES['imagem_arte']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['imagem_arte'];
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $this->pdo->rollBack();
                throw new RuntimeException('Não foi possível enviar a imagem.');
            }
            if ((int)$upload['size'] > 5 * 1024 * 1024) {
                $this->pdo->rollBack();
                throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
            }
            if (!is_uploaded_file($upload['tmp_name'])) {
                $this->pdo->rollBack();
                throw new RuntimeException('Arquivo de imagem inválido.');
            }
            $imageInfo = @getimagesize($upload['tmp_name']);
            $mime = $imageInfo['mime'] ?? '';
            $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!$imageInfo || !isset($exts[$mime])) {
                $this->pdo->rollBack();
                throw new RuntimeException('Use uma imagem JPG ou PNG.');
            }
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/artes';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                $this->pdo->rollBack();
                throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
            }
            $fileName = bin2hex(random_bytes(16)) . '.' . $exts[$mime];
            $imagemAbsoluta = $uploadDir . '/' . $fileName;
            $this->lastUploadAbsolute = $imagemAbsoluta;
            if (!move_uploaded_file($upload['tmp_name'], $imagemAbsoluta)) {
                $this->pdo->rollBack();
                throw new RuntimeException('Falha ao salvar a imagem.');
            }
            $imagemRelativa = 'uploads/artes/' . $fileName;
        }
        if (!empty($_POST['remover_imagem']) && $_POST['remover_imagem'] === '1') {
            $imagemRelativa = null;
        }

        $refs = $_POST['item_ref'] ?? ($_POST['produto_id'] ?? []);
        $larguras = $_POST['largura'] ?? [];
        $alturas = $_POST['altura'] ?? [];
        $espessuras = $_POST['espessura'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];
        $variacoesPost = $_POST['variacoes'] ?? []; // variacoes[i][] = id
        if (!is_array($refs) || count($refs) === 0) {
            $this->pdo->rollBack();
            throw new RuntimeException('Adicione pelo menos um item ao orçamento.');
        }
        $items = [];
        $total = 0.0;
        $descricoes = [];
        $find = $this->pdo->prepare('SELECT id,nome,unidade,preco FROM produtos WHERE id=?');
        foreach ($refs as $i => $ref) {
            $ref = trim((string)$ref);
            if ($ref === '' || $ref === '0') continue;
            $w = dec_float($larguras[$i] ?? 0);
            $h = dec_float($alturas[$i] ?? 0);
            $q = dec_float($quantidades[$i] ?? 1);
            if ($q <= 0) {
                $this->pdo->rollBack();
                throw new RuntimeException('A quantidade deve ser maior que zero.');
            }
            try {
                $e = dec_float($espessuras[$i] ?? 0);
                $varsLinha = [];
                if (isset($variacoesPost[$i]) && is_array($variacoesPost[$i])) $varsLinha = array_map('intval', $variacoesPost[$i]);
                if (str_starts_with($ref, 'c:')) {
                    $compId = (int)substr($ref, 2);
                    $calc = calc_composicao($this->pdo, $compId, $w, $h, $q, $e, $varsLinha);
                    $comp = $calc['comp'];
                    $unidade = $comp['unidade'];
                    if ($unidade === 'm2' && ($w <= 0 || $h <= 0)) throw new RuntimeException('Informe largura e altura (m) para composições por m².');
                    if ($unidade === 'metro_linear' && $w <= 0) throw new RuntimeException('Informe a largura/comprimento para composições por metro linear.');
                    $line = $calc['preco'];
                    $total += $line;
                    $items[] = ['id' => null, 'composicao_id' => $compId, 'variacoes_json' => $varsLinha ? json_encode($varsLinha) : null, 'nome' => $calc['nome'], 'unidade' => $unidade, 'largura' => $w ?: null, 'altura' => $h ?: null, 'espessura' => $e ?: null, 'quantidade' => $q, 'preco' => $q > 0 ? round($line / $q, 2) : $line, 'total' => $line];
                    $descricoes[] = $calc['nome'] . ' (' . $q . ' un.)';
                } else {
                    $pid = (int)(str_starts_with($ref, 'p:') ? substr($ref, 2) : $ref);
                    if ($pid <= 0) continue;
                    $find->execute([$pid]);
                    $prod = $find->fetch();
                    if (!$prod) throw new RuntimeException('Um dos produtos selecionados não existe.');
                    if ($prod['unidade'] === 'm2' && ($w <= 0 || $h <= 0)) throw new RuntimeException('Informe largura e altura para produtos cobrados por m².');
                    if ($prod['unidade'] === 'metro_linear' && $w <= 0) throw new RuntimeException('Informe o comprimento/largura para produtos cobrados por metro linear.');
                    $base = match ($prod['unidade']) {
                        'm2' => $w * $h,
                        'metro_linear' => $w,
                        default => 1.0
                    };
                    $line = round($base * (float)$prod['preco'] * $q, 2);
                    $total += $line;
                    $items[] = ['id' => $pid, 'composicao_id' => null, 'variacoes_json' => null, 'nome' => $prod['nome'], 'unidade' => $prod['unidade'], 'largura' => $w ?: null, 'altura' => $h ?: null, 'espessura' => $e ?: null, 'quantidade' => $q, 'preco' => (float)$prod['preco'], 'total' => $line];
                    $descricoes[] = $prod['nome'] . ' (' . $q . ' un.)';
                }
            } catch (Throwable $ex) {
                $this->pdo->rollBack();
                throw $ex;
            }
        }
        if (!$items) {
            $this->pdo->rollBack();
            throw new RuntimeException('Adicione pelo menos um item válido.');
        }
        if ($observacao !== '') $descricoes[] = 'Observações: ' . $observacao;
        $desc = implode(' | ', $descricoes);

        $upd = $this->pdo->prepare('UPDATE orcamentos_cv SET cliente_id=?, descricao=?, imagem_path=?, valor=?, status=? WHERE id=?');
        // se estava rejeitado e editou, volta para pendente
        $novoStatus = $orc['status'] === 'rejeitado' ? 'pendente' : $orc['status'];
        $upd->execute([$cid, $desc, $imagemRelativa, $total, $novoStatus, $id]);
        $this->pdo->prepare('DELETE FROM orcamento_itens_cv WHERE orcamento_id=?')->execute([$id]);
        $ins = $this->pdo->prepare('INSERT INTO orcamento_itens_cv(orcamento_id,produto_id,composicao_id,variacoes_json,produto_nome,unidade,largura,altura,espessura,quantidade,preco_unitario,total) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($items as $it) $ins->execute([$id, $it['id'] ?? null, $it['composicao_id'] ?? null, $it['variacoes_json'] ?? null, $it['nome'], $it['unidade'], $it['largura'], $it['altura'], $it['espessura'] ?? null, $it['quantidade'], $it['preco'], $it['total']]);
        $this->pdo->commit();
        flash('Orçamento #' . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . ' atualizado.');
        go('orcamentos');
    }

    public function status(): void
    {
        $this->requireRole(['admin', 'vendedor']);
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'pendente';
        if ($id <= 0) throw new RuntimeException('Orçamento inválido.');
        if (!in_array($status, ['pendente', 'aprovado', 'rejeitado', 'cancelado'], true)) throw new RuntimeException('Status inválido.');

        $temAprovadoEm = ensure_aprovado_em($this->pdo); // faz DDL, então precisa ficar antes da transação
        $this->pdo->beginTransaction();
        $cur = $this->pdo->prepare('SELECT status FROM orcamentos_cv WHERE id=? FOR UPDATE');
        $cur->execute([$id]);
        $atual = $cur->fetchColumn();
        if ($atual === false) throw new RuntimeException('Orçamento não encontrado.');
        if ($atual === $status) {
            $this->pdo->rollBack();
            flash('O orçamento já está com este status.', 'warning');
            go('orcamentos');
        }

        // Transições permitidas
        $permitidas = [
            'pendente'  => ['aprovado', 'rejeitado', 'cancelado'],
            'rejeitado' => ['pendente', 'cancelado'],
            'aprovado'  => ['cancelado'],
            'cancelado' => [],
        ];
        if (!in_array($status, $permitidas[$atual] ?? [], true)) {
            throw new RuntimeException('Não é possível mudar o orçamento de "' . $atual . '" para "' . $status . '".');
        }

        // Cancelar um orçamento aprovado: só se a OS ainda não começou e nada foi recebido
        if ($atual === 'aprovado' && $status === 'cancelado') {
            $os = $this->pdo->prepare('SELECT status FROM ordens_servico WHERE orcamento_id=? FOR UPDATE');
            $os->execute([$id]);
            $osStatus = $os->fetchColumn();
            if ($osStatus !== false && !in_array($osStatus, ['fila', 'cancelada'], true)) {
                throw new RuntimeException('A OS deste orçamento já está em andamento ou concluída. Resolva-a na Produção antes de cancelar.');
            }
            $pg = $this->pdo->prepare("SELECT COALESCE(SUM(valor_pago),0) FROM contas_receber WHERE orcamento_id=? AND status<>'cancelado'");
            $pg->execute([$id]);
            if ((float)$pg->fetchColumn() > 0) {
                throw new RuntimeException('Já existem pagamentos recebidos para este orçamento. Estorne-os antes de cancelar.');
            }
            $this->pdo->prepare("UPDATE ordens_servico SET status='cancelada' WHERE orcamento_id=?")->execute([$id]);
            $this->pdo->prepare("UPDATE contas_receber SET status='cancelado' WHERE orcamento_id=? AND status IN ('pendente','parcial')")->execute([$id]);
        }

        $this->pdo->prepare('UPDATE orcamentos_cv SET status=? WHERE id=?')->execute([$status, $id]);

        if ($status === 'aprovado') {
            // data da venda = momento da aprovação (usada em Vendas)
            if ($temAprovadoEm) $this->pdo->prepare('UPDATE orcamentos_cv SET aprovado_em=NOW() WHERE id=?')->execute([$id]);
            $this->pdo->prepare('INSERT IGNORE INTO ordens_servico(orcamento_id) VALUES(?)')->execute([$id]);
            // Gera conta a receber automática (evita duplicar se já existir para o mesmo orçamento)
            $check = $this->pdo->prepare("SELECT id FROM contas_receber WHERE orcamento_id=? AND status != 'cancelado' LIMIT 1");
            $check->execute([$id]);
            if (!$check->fetch()) {
                $orc = $this->pdo->prepare('SELECT cliente_id, descricao, valor FROM orcamentos_cv WHERE id=?');
                $orc->execute([$id]);
                $o = $orc->fetch();
                if ($o && (float)$o['valor'] > 0) {
                    $venc = date('Y-m-d', strtotime('+30 days'));
                    $ins = $this->pdo->prepare('INSERT INTO contas_receber(cliente_id,orcamento_id,descricao,valor,data_emissao,data_vencimento,usuario_id) VALUES(?,?,?,?,?,?,?)');
                    $ins->execute([
                        $o['cliente_id'],
                        $id,
                        'Orçamento #' . $id . ' — ' . mb_substr((string)$o['descricao'], 0, 180),
                        $o['valor'],
                        date('Y-m-d'),
                        $venc,
                        $_SESSION['user']['id']
                    ]);
                }
            }
        }
        $this->pdo->commit();
        flash('Status atualizado' . ($status === 'aprovado' ? ' e ordem de serviço + conta a receber criadas.' : ($status === 'cancelado' && $atual === 'aprovado' ? '; OS e conta a receber em aberto foram canceladas.' : '.')));
        go('orcamentos');
    }


    public function index(): void
    {
        $page = 'orcamentos';

        $rows = $this->pdo->query('SELECT o.*,c.nome cliente FROM orcamentos_cv o JOIN clientes c ON c.id=o.cliente_id ORDER BY o.id DESC')->fetchAll();
        $clientes = $this->pdo->query('SELECT id,nome FROM clientes ORDER BY nome')->fetchAll();
        $produtosOrc = $this->pdo->query('SELECT id,nome,unidade,preco FROM produtos WHERE ativo=1 ORDER BY nome')->fetchAll();
        $composicoesOrc = $this->pdo->query("SELECT c.*, (
            SELECT COALESCE(SUM(
              CASE ci.modo
                WHEN 'por_m2' THEN ci.quantidade * m.custo
                WHEN 'por_metro' THEN ci.quantidade * m.custo
                ELSE ci.quantidade * m.custo
              END
            ),0)
            FROM composicao_itens ci JOIN materias_primas m ON m.id=ci.materia_id WHERE ci.composicao_id=c.id AND m.ativo=1
          ) custo_base FROM composicoes c WHERE c.ativo=1 ORDER BY c.tipo, c.nome")->fetchAll();
        // JSON para JS: itens de cada composição
        $compMap = [];
        if ($composicoesOrc) {
            $cids = implode(',', array_map('intval', array_column($composicoesOrc, 'id')));
            $ciRows = $this->pdo->query("SELECT ci.composicao_id, ci.quantidade, ci.modo, m.custo, m.nome FROM composicao_itens ci JOIN materias_primas m ON m.id=ci.materia_id WHERE ci.composicao_id IN ($cids) AND m.ativo=1")->fetchAll();
            foreach ($ciRows as $r) {
                $compMap[(int)$r['composicao_id']][] = [
                    'qtd' => (float)$r['quantidade'],
                    'modo' => $r['modo'],
                    'custo' => (float)$r['custo'],
                    'nome' => $r['nome'],
                ];
            }
            foreach ($composicoesOrc as &$co) {
                $co['itens_json'] = $compMap[(int)$co['id']] ?? [];
                $margem = (float)$co['margem_percentual'];
                $co['preco_ref'] = round(((float)$co['custo_base']) * (1 + $margem / 100), 2);
                $co['variacoes'] = [];
            }
            unset($co);
            try {
                if ($composicoesOrc) {
                    $cids = implode(',', array_map('intval', array_column($composicoesOrc, 'id')));
                    $vars = $this->pdo->query("SELECT * FROM composicao_variacoes WHERE ativo=1 AND composicao_id IN ($cids) ORDER BY nome")->fetchAll();
                    $varMap = [];
                    if ($vars) {
                        $vids = implode(',', array_map('intval', array_column($vars, 'id')));
                        $vitems = $this->pdo->query("SELECT cvi.*, m.nome, m.custo FROM composicao_variacao_itens cvi JOIN materias_primas m ON m.id=cvi.materia_id WHERE cvi.variacao_id IN ($vids) AND m.ativo=1")->fetchAll();
                        foreach ($vitems as $vi) {
                            $varMap[(int)$vi['variacao_id']][] = ['qtd' => (float)$vi['quantidade'], 'modo' => $vi['modo'], 'custo' => (float)$vi['custo'], 'nome' => $vi['nome']];
                        }
                    }
                    $byComp = [];
                    foreach ($vars as $v) {
                        $byComp[(int)$v['composicao_id']][] = [
                            'id' => (int)$v['id'],
                            'nome' => $v['nome'],
                            'tipo' => $v['tipo'],
                            'itens' => $varMap[(int)$v['id']] ?? [],
                        ];
                    }
                    foreach ($composicoesOrc as &$co) {
                        $co['variacoes'] = $byComp[(int)$co['id']] ?? [];
                        $co['custos'] = [];
                    }
                    unset($co);
                    try {
                        $cids2 = implode(',', array_map('intval', array_column($composicoesOrc, 'id')));
                        $crows = $this->pdo->query("SELECT * FROM composicao_custos WHERE composicao_id IN ($cids2)")->fetchAll();
                        $cMap = [];
                        foreach ($crows as $cr) {
                            $cMap[(int)$cr['composicao_id']][] = [
                                'nome' => $cr['nome'],
                                'valor' => (float)$cr['valor'],
                                'modo' => $cr['modo'],
                            ];
                        }
                        foreach ($composicoesOrc as &$co) {
                            $co['custos'] = $cMap[(int)$co['id']] ?? [];
                        }
                        unset($co);
                    } catch (Throwable $e) {
                    }
                }
            } catch (Throwable $e) {
            }
        }
        $itensPorOrc = [];
        if ($rows) {
            $ids = array_column($rows, 'id');
            $in = implode(',', array_map('intval', $ids));
            $itens = $this->pdo->query("SELECT * FROM orcamento_itens_cv WHERE orcamento_id IN ($in) ORDER BY id")->fetchAll();
            foreach ($itens as $it) {
                $itensPorOrc[(int)$it['orcamento_id']][] = $it;
            }
        }

        $this->renderApp('orcamentos', get_defined_vars());
    }
}

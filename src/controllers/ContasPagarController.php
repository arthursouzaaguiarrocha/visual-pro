<?php
declare(strict_types=1);

class ContasPagarController extends Controller
{
    public function save(): void
    {

            $this->requireRole(['admin']);
            $fornecedor = trim($_POST['fornecedor'] ?? '') ?: null;
            $desc = trim($_POST['descricao'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '') ?: null;
            $valor = (float)($_POST['valor'] ?? 0);
            $emissao = $_POST['data_emissao'] ?? date('Y-m-d');
            $venc = $_POST['data_vencimento'] ?? date('Y-m-d', strtotime('+30 days'));
            $obs = trim($_POST['observacoes'] ?? '') ?: null;
            $parcelas = max(1, min(120, (int)($_POST['parcelas'] ?? 1)));
            $modoPagamento = $_POST['modo_pagamento'] ?? 'pix';
            $modosPagamento = ['pix', 'dinheiro', 'cartao_credito', 'cartao_debito', 'boleto', 'transferencia', 'debito_automatico', 'cheque', 'outro'];
            $periodicidade = $_POST['periodicidade'] ?? 'mensal';
            if (!in_array($periodicidade, ['mensal', 'personalizado'], true)) $periodicidade = 'mensal';
            $intervaloDias = max(1, min(365, (int)($_POST['intervalo_dias'] ?? 30)));
            if (!in_array($modoPagamento, $modosPagamento, true)) throw new RuntimeException('Modo de pagamento inválido.');
            if ($desc === '' || $valor <= 0 || $venc < $emissao) throw new RuntimeException('Informe descrição, valor maior que zero e vencimento igual ou posterior à emissão.');
            if ($parcelas === 1) {
                $intervaloDias = null;
            } elseif ($periodicidade === 'mensal') {
                $intervaloDias = 30; // referência
            }
            $grupoParcelamento = $parcelas > 1 ? bin2hex(random_bytes(16)) : null;
            $valorCentavos = (int)round($valor * 100);
            $baseCentavos = intdiv($valorCentavos, $parcelas);
            $restoCentavos = $valorCentavos % $parcelas;
            $s = $this->pdo->prepare('INSERT INTO contas_pagar(fornecedor,descricao,categoria,valor,data_emissao,data_vencimento,observacoes,usuario_id,parcelamento_id,numero_parcela,total_parcelas,periodicidade,intervalo_dias,modo_pagamento) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $this->pdo->beginTransaction();
            $dataBase = new DateTimeImmutable($venc);
            $diaMes = (int)$dataBase->format('d');
            for ($i = 0; $i < $parcelas; $i++) {
                if ($i === 0) {
                    $dataParcela = $dataBase;
                } elseif ($periodicidade === 'mensal') {
                    $dataParcela = $dataBase->modify('+' . $i . ' months');
                    // Ajusta para o mesmo dia do mês (ou último dia se o mês for menor)
                    $ultimoDia = (int)$dataParcela->format('t');
                    $diaAlvo = min($diaMes, $ultimoDia);
                    $dataParcela = $dataParcela->setDate((int)$dataParcela->format('Y'), (int)$dataParcela->format('m'), $diaAlvo);
                } else {
                    $dias = (int)($intervaloDias ?? 30);
                    $dataParcela = $dataBase->modify('+' . ($i * $dias) . ' days');
                }
                $valorParcela = ($baseCentavos + ($i < $restoCentavos ? 1 : 0)) / 100;
                $descParcela = $parcelas > 1 ? $desc . ' (' . ($i + 1) . '/' . $parcelas . ')' : $desc;
                $s->execute([$fornecedor, $descParcela, $categoria, number_format($valorParcela, 2, '.', ''), $emissao, $dataParcela->format('Y-m-d'), $obs, $_SESSION['user']['id'], $grupoParcelamento, $i + 1, $parcelas, $periodicidade, $intervaloDias, $modoPagamento]);
            }
            $this->pdo->commit();
            $msgExtra = $parcelas > 1
                ? ($periodicidade === 'mensal' ? ' mensais (mesmo dia do mês).' : ' a cada ' . (int)$intervaloDias . ' dia(s).')
                : '.';
            flash($parcelas > 1 ? $parcelas . ' parcelas cadastradas' . $msgExtra : 'Conta a pagar cadastrada.');
            go('contas_pagar');

    }

    public function update(): void
    {

            $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0); $fornecedor=trim($_POST['fornecedor'] ?? '') ?: null; $desc=trim($_POST['descricao'] ?? ''); $categoria=trim($_POST['categoria'] ?? '') ?: null; $valor=(float)($_POST['valor'] ?? 0); $emissao=$_POST['data_emissao'] ?? date('Y-m-d'); $venc=$_POST['data_vencimento'] ?? $emissao; $obs=trim($_POST['observacoes'] ?? '') ?: null;
            if ($desc==='' || $valor<=0 || $venc<$emissao) throw new RuntimeException('Confira descrição, valor e datas.');
            $s=$this->pdo->prepare("UPDATE contas_pagar SET fornecedor=?,descricao=?,categoria=?,valor=?,data_emissao=?,data_vencimento=?,observacoes=? WHERE id=? AND status='pendente' AND valor_pago=0"); $s->execute([$fornecedor,$desc,$categoria,$valor,$emissao,$venc,$obs,$id]); if (!$s->rowCount()) throw new RuntimeException('Só é possível editar contas pendentes sem pagamentos.'); flash('Conta a pagar atualizada.'); go('contas_pagar');

    }

    public function delete(): void
    {
         $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0); $s=$this->pdo->prepare("UPDATE contas_pagar SET status='cancelado' WHERE id=? AND status='pendente' AND valor_pago=0"); $s->execute([$id]); if (!$s->rowCount()) throw new RuntimeException('Somente contas pendentes sem pagamentos podem ser canceladas por esta ação.'); flash('Conta a pagar cancelada.'); go('contas_pagar'); 
    }

    public function pagar(): void
    {
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        $valorPagoAgora = round(dec_float($_POST['valor_pago'] ?? 0), 2);
        $dataPag = (string)($_POST['data_pagamento'] ?? '');
        $dt = DateTime::createFromFormat('Y-m-d', $dataPag);
        if (!$dt || $dt->format('Y-m-d') !== $dataPag) $dataPag = date('Y-m-d');
        $registrarCaixa = !empty($_POST['registrar_caixa']);

        $tipos = ['pagamento' => 'Pagamento', 'vale' => 'Vale', 'adiantamento' => 'Adiantamento'];
        $tipo = (string)($_POST['tipo'] ?? 'pagamento');
        if (!isset($tipos[$tipo])) $tipo = 'pagamento';
        $formas = ['pix', 'dinheiro', 'cartao_credito', 'cartao_debito', 'boleto', 'transferencia', 'debito_automatico', 'cheque', 'outro'];
        $forma = (string)($_POST['forma'] ?? '');
        if (!in_array($forma, $formas, true)) $forma = null;
        $obs = mb_substr(trim((string)($_POST['observacao'] ?? '')), 0, 255) ?: null;

        if ($id <= 0 || $valorPagoAgora <= 0) throw new RuntimeException('Informe um valor de pagamento válido.');

        $temHist = ensure_pagamentos_conta_pagar($this->pdo); // DDL: antes da transação
        $this->pdo->beginTransaction();
        $s = $this->pdo->prepare('SELECT * FROM contas_pagar WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        $c = $s->fetch();
        if (!$c || $c['status'] === 'cancelado' || $c['status'] === 'pago') throw new RuntimeException('Conta inválida ou já finalizada.');
        $novoPago = round((float)$c['valor_pago'] + $valorPagoAgora, 2);
        if ($novoPago > (float)$c['valor'] + 0.009) {
            throw new RuntimeException('O valor excede o que falta pagar (' . money(max(0, (float)$c['valor'] - (float)$c['valor_pago'])) . ').');
        }
        $status = $novoPago >= (float)$c['valor'] - 0.009 ? 'pago' : 'parcial';
        $dataPagFinal = $status === 'pago' ? $dataPag : ($c['data_pagamento'] ?: $dataPag);
        $upd = $this->pdo->prepare('UPDATE contas_pagar SET valor_pago=?, status=?, data_pagamento=? WHERE id=?');
        $upd->execute([$novoPago, $status, $dataPagFinal, $id]);

        $caixaId = null;
        if ($registrarCaixa) {
            $descCaixa = $tipos[$tipo] . ': ' . $c['descricao'] . ($c['fornecedor'] ? ' — ' . $c['fornecedor'] : '');
            $cx = $this->pdo->prepare('INSERT INTO movimentacoes_caixa(tipo,descricao,categoria,valor,data_movimento,usuario_id) VALUES(?,?,?,?,?,?)');
            $cx->execute(['saida', $descCaixa, $c['categoria'] ?: 'Contas a pagar', $valorPagoAgora, $dataPag, $_SESSION['user']['id']]);
            $caixaId = (int)$this->pdo->lastInsertId();
        }
        if ($temHist) {
            $h = $this->pdo->prepare('INSERT INTO contas_pagar_pagamentos(conta_id,valor,data_pagamento,tipo,forma,observacao,caixa_id,usuario_id) VALUES(?,?,?,?,?,?,?,?)');
            $h->execute([$id, $valorPagoAgora, $dataPag, $tipo, $forma, $obs, $caixaId, $_SESSION['user']['id']]);
        }
        $this->pdo->commit();

        $falta = max(0.0, (float)$c['valor'] - $novoPago);
        flash($status === 'pago'
            ? 'Conta quitada com sucesso.'
            : $tipos[$tipo] . ' de ' . money($valorPagoAgora) . ' registrado. Ainda falta pagar ' . money($falta) . '.');
        go('contas_pagar');
    }

    /** Desfaz um pagamento/vale lançado por engano (e a saída correspondente no caixa). */
    public function estornar(): void
    {
        $this->requireRole(['admin']);
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid <= 0) throw new RuntimeException('Pagamento inválido.');
        if (!ensure_pagamentos_conta_pagar($this->pdo)) throw new RuntimeException('Histórico de pagamentos indisponível.');

        $this->pdo->beginTransaction();
        $q = $this->pdo->prepare('SELECT * FROM contas_pagar_pagamentos WHERE id=? FOR UPDATE');
        $q->execute([$pid]);
        $h = $q->fetch();
        if (!$h) throw new RuntimeException('Pagamento não encontrado.');
        $q = $this->pdo->prepare('SELECT * FROM contas_pagar WHERE id=? FOR UPDATE');
        $q->execute([$h['conta_id']]);
        $c = $q->fetch();
        if (!$c || $c['status'] === 'cancelado') throw new RuntimeException('Conta cancelada: não é possível estornar.');

        $novoPago = max(0.0, round((float)$c['valor_pago'] - (float)$h['valor'], 2));
        $status = $novoPago <= 0.009 ? 'pendente' : 'parcial';
        $dataPag = null;
        if ($status === 'parcial') {
            $u = $this->pdo->prepare('SELECT MAX(data_pagamento) FROM contas_pagar_pagamentos WHERE conta_id=? AND id<>?');
            $u->execute([$h['conta_id'], $pid]);
            $dataPag = $u->fetchColumn() ?: $c['data_pagamento'];
        }
        $this->pdo->prepare('UPDATE contas_pagar SET valor_pago=?, status=?, data_pagamento=? WHERE id=?')
            ->execute([$novoPago, $status, $dataPag, $h['conta_id']]);
        if (!empty($h['caixa_id'])) {
            $this->pdo->prepare('DELETE FROM movimentacoes_caixa WHERE id=?')->execute([$h['caixa_id']]);
        }
        $this->pdo->prepare('DELETE FROM contas_pagar_pagamentos WHERE id=?')->execute([$pid]);
        $this->pdo->commit();
        flash('Pagamento de ' . money($h['valor']) . ' estornado' . (!empty($h['caixa_id']) ? ' (e removido do caixa).' : '. Confira o caixa: este lançamento não estava vinculado.'));
        go('contas_pagar');
    }

    public function cancelar(): void
    {
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        $q = $this->pdo->prepare('SELECT valor_pago FROM contas_pagar WHERE id=?');
        $q->execute([$id]);
        $pago = (float)$q->fetchColumn();
        $s = $this->pdo->prepare("UPDATE contas_pagar SET status='cancelado' WHERE id=? AND status IN ('pendente','parcial')");
        $s->execute([$id]);
        if (!$s->rowCount()) throw new RuntimeException('Conta não encontrada ou já finalizada.');
        if ($pago > 0) {
            flash('Conta a pagar cancelada. Atenção: ' . money($pago) . ' já pagos continuam lançados no caixa — ajuste manualmente se necessário.', 'warning');
        } else {
            flash('Conta a pagar cancelada.');
        }
        go('contas_pagar');
    }


    public function index(): void
    {
        $page = 'contas_pagar';

        $filtro = $_GET['filtro'] ?? 'abertas';
        $filtroAno = isset($_GET['ano']) && $_GET['ano'] !== '' ? (string)(int)$_GET['ano'] : null;
        $filtroMes = $_GET['mes'] ?? null;
        if ($filtroMes !== null && $filtroMes !== '' && $filtroMes !== 'todos') {
            $filtroMes = str_pad((string)(int)$filtroMes, 2, '0', STR_PAD_LEFT);
        } elseif ($filtroMes === '') {
            $filtroMes = null;
        }
        $filtroDe = trim($_GET['de'] ?? '') ?: null;
        $filtroAte = trim($_GET['ate'] ?? '') ?: null;
        // Se não veio nenhum período, não filtra por data (mostra tudo do status)
        $temPeriodo = $filtroAno || ($filtroDe && $filtroAte);
        if (!$temPeriodo) {
            $filtroAno = $filtroMes = $filtroDe = $filtroAte = null;
        } elseif ($filtroAno && !$filtroMes) {
            $filtroMes = 'todos';
        }
        $qsPeriodo = static function () use ($filtroAno, $filtroMes, $filtroDe, $filtroAte): string {
            $p = [];
            if ($filtroAno) $p[] = 'ano=' . urlencode($filtroAno);
            if ($filtroMes) $p[] = 'mes=' . urlencode($filtroMes);
            if ($filtroDe) $p[] = 'de=' . urlencode($filtroDe);
            if ($filtroAte) $p[] = 'ate=' . urlencode($filtroAte);
            return $p ? '&' . implode('&', $p) : '';
        };
        $periodoQuery = $qsPeriodo();
        $mesesPt = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
        if ($filtroDe && $filtroAte) {
            $periodoLabel = date('d/m/Y', strtotime($filtroDe)) . ' a ' . date('d/m/Y', strtotime($filtroAte));
        } elseif ($filtroAno && $filtroMes && $filtroMes !== 'todos') {
            $periodoLabel = ($mesesPt[(int)$filtroMes] ?? $filtroMes) . '/' . $filtroAno;
        } elseif ($filtroAno) {
            $periodoLabel = 'Ano ' . $filtroAno;
        } else {
            $periodoLabel = 'Todos os períodos';
        }


        require_once __DIR__ . '/../models/ContaPagar.php';
        $model = new ContaPagar($this->pdo);
        $rows = $model->listar($filtro, $filtroAno, $filtroMes, $filtroDe, $filtroAte);
        $resumo = $model->resumo($filtroAno, $filtroMes, $filtroDe, $filtroAte);

        // Histórico de pagamentos / vales de cada conta listada
        $historico = [];
        if ($rows && ensure_pagamentos_conta_pagar($this->pdo)) {
            $ids = array_map(static fn($r) => (int)$r['id'], $rows);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $q = $this->pdo->prepare("SELECT * FROM contas_pagar_pagamentos WHERE conta_id IN ($in) ORDER BY data_pagamento ASC, id ASC");
            $q->execute($ids);
            foreach ($q->fetchAll() as $h) {
                $historico[(int)$h['conta_id']][] = $h;
            }
        }

        $this->renderApp('contas_pagar', get_defined_vars());
    }
}

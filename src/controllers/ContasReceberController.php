<?php
declare(strict_types=1);

class ContasReceberController extends Controller
{
    public function save(): void
    {

            $this->requireRole(['admin', 'vendedor']);
            $clienteId = (int)($_POST['cliente_id'] ?? 0) ?: null;
            $desc = trim($_POST['descricao'] ?? '');
            $valor = (float)($_POST['valor'] ?? 0);
            $emissao = $_POST['data_emissao'] ?? date('Y-m-d');
            $venc = $_POST['data_vencimento'] ?? date('Y-m-d', strtotime('+30 days'));
            $obs = trim($_POST['observacoes'] ?? '') ?: null;
            if ($desc === '' || $valor <= 0 || $venc < $emissao) throw new RuntimeException('Informe descrição, valor maior que zero e vencimento igual ou posterior à emissão.');
            $s = $this->pdo->prepare('INSERT INTO contas_receber(cliente_id,descricao,valor,data_emissao,data_vencimento,observacoes,usuario_id) VALUES(?,?,?,?,?,?,?)');
            $s->execute([$clienteId, $desc, $valor, $emissao, $venc, $obs, $_SESSION['user']['id']]);
            flash('Conta a receber cadastrada.');
            go('contas_receber');

    }

    public function update(): void
    {

            $this->requireRole(['admin','vendedor']); $id=(int)($_POST['id'] ?? 0); $desc=trim($_POST['descricao'] ?? ''); $valor=(float)($_POST['valor'] ?? 0); $emissao=$_POST['data_emissao'] ?? date('Y-m-d'); $venc=$_POST['data_vencimento'] ?? $emissao; $clienteId=(int)($_POST['cliente_id'] ?? 0) ?: null; $obs=trim($_POST['observacoes'] ?? '') ?: null;
            if ($desc==='' || $valor<=0 || $venc<$emissao) throw new RuntimeException('Informe descrição, valor maior que zero e vencimento igual ou posterior à emissão.');
            $s=$this->pdo->prepare("UPDATE contas_receber SET cliente_id=?,descricao=?,valor=?,data_emissao=?,data_vencimento=?,observacoes=? WHERE id=? AND status='pendente' AND valor_pago=0"); $s->execute([$clienteId,$desc,$valor,$emissao,$venc,$obs,$id]); if (!$s->rowCount()) throw new RuntimeException('Só é possível editar contas pendentes sem pagamentos.'); flash('Conta a receber atualizada.'); go('contas_receber');

    }

    public function delete(): void
    {
         $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0); $s=$this->pdo->prepare("UPDATE contas_receber SET status='cancelado' WHERE id=? AND status='pendente' AND valor_pago=0"); $s->execute([$id]); if (!$s->rowCount()) throw new RuntimeException('Somente contas pendentes sem pagamentos podem ser canceladas por esta ação.'); flash('Conta a receber cancelada.'); go('contas_receber'); 
    }

    public function pagar(): void
    {

            $this->requireRole(['admin', 'vendedor']);
            $id = (int)($_POST['id'] ?? 0);
            $valorPagoAgora = (float)($_POST['valor_pago'] ?? 0);
            $dataPag = $_POST['data_pagamento'] ?? date('Y-m-d');
            $registrarCaixa = !empty($_POST['registrar_caixa']);
            if ($id <= 0 || $valorPagoAgora <= 0) throw new RuntimeException('Informe um valor de pagamento válido.');
            $this->pdo->beginTransaction();
            $s = $this->pdo->prepare('SELECT * FROM contas_receber WHERE id=? FOR UPDATE');
            $s->execute([$id]);
            $c = $s->fetch();
            if (!$c || $c['status'] === 'cancelado' || $c['status'] === 'pago') throw new RuntimeException('Conta inválida ou já finalizada.');
            $novoPago = (float)$c['valor_pago'] + $valorPagoAgora;
            if ($novoPago > (float)$c['valor'] + 0.009) throw new RuntimeException('Valor pago excede o total da conta.');
            $status = $novoPago >= (float)$c['valor'] - 0.009 ? 'pago' : 'parcial';
            $dataPagFinal = $status === 'pago' ? $dataPag : ($c['data_pagamento'] ?: $dataPag);
            $upd = $this->pdo->prepare('UPDATE contas_receber SET valor_pago=?, status=?, data_pagamento=? WHERE id=?');
            $upd->execute([$novoPago, $status, $dataPagFinal, $id]);
            if ($registrarCaixa) {
                $descCaixa = 'Recebimento: ' . $c['descricao'] . ($c['orcamento_id'] ? ' (Orç. #' . $c['orcamento_id'] . ')' : '');
                $cx = $this->pdo->prepare('INSERT INTO movimentacoes_caixa(tipo,descricao,categoria,valor,data_movimento,usuario_id) VALUES(?,?,?,?,?,?)');
                $cx->execute(['entrada', $descCaixa, 'Contas a receber', $valorPagoAgora, $dataPag, $_SESSION['user']['id']]);
            }
            $this->pdo->commit();
            flash($status === 'pago' ? 'Conta quitada com sucesso.' : 'Pagamento parcial registrado.');
            go('contas_receber');

    }

    public function cancelar(): void
    {
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        $q = $this->pdo->prepare('SELECT valor_pago FROM contas_receber WHERE id=?');
        $q->execute([$id]);
        $pago = (float)$q->fetchColumn();
        $s = $this->pdo->prepare("UPDATE contas_receber SET status='cancelado' WHERE id=? AND status IN ('pendente','parcial')");
        $s->execute([$id]);
        if (!$s->rowCount()) throw new RuntimeException('Conta não encontrada ou já finalizada.');
        if ($pago > 0) {
            flash('Conta a receber cancelada. Atenção: ' . money($pago) . ' já pagos continuam lançados no caixa — ajuste manualmente se necessário.', 'warning');
        } else {
            flash('Conta a receber cancelada.');
        }
        go('contas_receber');
    }


    public function index(): void
    {
        $page = 'contas_receber';

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


        require_once __DIR__ . '/../models/ContaReceber.php';
        $model = new ContaReceber($this->pdo);
        $rows = $model->listar($filtro, $filtroAno, $filtroMes, $filtroDe, $filtroAte);
        $resumo = $model->resumo($filtroAno, $filtroMes, $filtroDe, $filtroAte);
        $clientes = $this->pdo->query('SELECT id,nome FROM clientes ORDER BY nome')->fetchAll();

        $this->renderApp('contas_receber', get_defined_vars());
    }
}

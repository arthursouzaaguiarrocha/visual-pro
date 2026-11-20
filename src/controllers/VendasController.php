<?php
declare(strict_types=1);

class VendasController extends Controller
{

    public function index(): void
    {
        $page = 'vendas';
        // data da venda: aprovado_em (se a coluna existir) ou, como alternativa, atualizado_em
        $temAprov = ensure_aprovado_em($this->pdo);
        $dt = $temAprov ? 'COALESCE(aprovado_em, atualizado_em)' : 'atualizado_em';
        $dtO = $temAprov ? 'COALESCE(o.aprovado_em, o.atualizado_em)' : 'o.atualizado_em';

        $ano = (int)($_GET['ano'] ?? date('Y'));
        $mes = $_GET['mes'] ?? date('m'); // '' = ano inteiro, '01'..'12'
        if ($mes !== '' && $mes !== 'todos') {
            $mes = str_pad((string)(int)$mes, 2, '0', STR_PAD_LEFT);
            if ((int)$mes < 1 || (int)$mes > 12) $mes = date('m');
        } else {
            $mes = 'todos';
        }
        $de = trim($_GET['de'] ?? '');
        $ate = trim($_GET['ate'] ?? '');
        $usarPeriodo = $de !== '' && $ate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $de) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate);

        // Vendas = orçamentos aprovados (por data de atualização = quando mudou status)
        if ($usarPeriodo) {
            if ($de > $ate) { $tmp = $de; $de = $ate; $ate = $tmp; }
            $whereAprov = "status='aprovado' AND DATE({$dt}) BETWEEN ? AND ?";
            $paramsAprov = [$de, $ate];
            $whereCriado = "DATE(criado_em) BETWEEN ? AND ?";
            $paramsCriado = [$de, $ate];
            $whereReceb = "status IN ('pago','parcial') AND data_pagamento IS NOT NULL AND data_pagamento BETWEEN ? AND ?";
            $paramsReceb = [$de, $ate];
            $whereCaixa = "tipo='entrada' AND data_movimento BETWEEN ? AND ?";
            $paramsCaixa = [$de, $ate];
            $periodoLabel = date('d/m/Y', strtotime($de)) . ' a ' . date('d/m/Y', strtotime($ate));
        } elseif ($mes === 'todos') {
            $whereAprov = "status='aprovado' AND YEAR({$dt})=?";
            $paramsAprov = [$ano];
            $whereCriado = "YEAR(criado_em)=?";
            $paramsCriado = [$ano];
            $whereReceb = "status IN ('pago','parcial') AND data_pagamento IS NOT NULL AND YEAR(data_pagamento)=?";
            $paramsReceb = [$ano];
            $whereCaixa = "tipo='entrada' AND YEAR(data_movimento)=?";
            $paramsCaixa = [$ano];
            $periodoLabel = 'Ano ' . $ano;
        } else {
            $whereAprov = "status='aprovado' AND YEAR({$dt})=? AND MONTH({$dt})=?";
            $paramsAprov = [$ano, (int)$mes];
            $whereCriado = "YEAR(criado_em)=? AND MONTH(criado_em)=?";
            $paramsCriado = [$ano, (int)$mes];
            $whereReceb = "status IN ('pago','parcial') AND data_pagamento IS NOT NULL AND YEAR(data_pagamento)=? AND MONTH(data_pagamento)=?";
            $paramsReceb = [$ano, (int)$mes];
            $whereCaixa = "tipo='entrada' AND YEAR(data_movimento)=? AND MONTH(data_movimento)=?";
            $paramsCaixa = [$ano, (int)$mes];
            $mesesPt = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
            $periodoLabel = ($mesesPt[(int)$mes] ?? $mes) . '/' . $ano;
        }

        $s = $this->pdo->prepare("SELECT COUNT(*) qtd, COALESCE(SUM(valor),0) total FROM orcamentos_cv WHERE $whereAprov");
        $s->execute($paramsAprov);
        $vendasResumo = $s->fetch();
        $vendasResumo['ticket'] = ((int)$vendasResumo['qtd'] > 0) ? ((float)$vendasResumo['total'] / (int)$vendasResumo['qtd']) : 0;

        $s = $this->pdo->prepare("SELECT COUNT(*) qtd, COALESCE(SUM(valor),0) total FROM orcamentos_cv WHERE $whereCriado");
        $s->execute($paramsCriado);
        $criadosResumo = $s->fetch();

        $s = $this->pdo->prepare("SELECT COALESCE(SUM(valor_pago),0) FROM contas_receber WHERE $whereReceb");
        $s->execute($paramsReceb);
        $recebidoPeriodo = (float)$s->fetchColumn();

        $s = $this->pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM movimentacoes_caixa WHERE $whereCaixa");
        $s->execute($paramsCaixa);
        $caixaEntradas = (float)$s->fetchColumn();

        // Lista de vendas (aprovados no período)
        $s = $this->pdo->prepare("SELECT o.id, o.descricao, o.valor, o.status, o.criado_em, {$dtO} AS atualizado_em, c.nome cliente
            FROM orcamentos_cv o JOIN clientes c ON c.id=o.cliente_id
            WHERE $whereAprov ORDER BY {$dtO} DESC, o.id DESC LIMIT 300");
        $s->execute($paramsAprov);
        $vendasLista = $s->fetchAll();

        // Série mensal do ano selecionado (para gráfico e tabela)
        $anoSerie = $usarPeriodo ? (int)substr($de, 0, 4) : $ano;
        $s = $this->pdo->prepare("SELECT MONTH({$dt}) m, COUNT(*) qtd, COALESCE(SUM(valor),0) total
            FROM orcamentos_cv WHERE status='aprovado' AND YEAR({$dt})=?
            GROUP BY MONTH({$dt}) ORDER BY m");
        $s->execute([$anoSerie]);
        $porMesRaw = $s->fetchAll();
        $porMes = [];
        for ($i = 1; $i <= 12; $i++) {
            $porMes[$i] = ['m' => $i, 'qtd' => 0, 'total' => 0.0];
        }
        foreach ($porMesRaw as $row) {
            $porMes[(int)$row['m']] = ['m' => (int)$row['m'], 'qtd' => (int)$row['qtd'], 'total' => (float)$row['total']];
        }
        $vendasPorMes = array_values($porMes);
        $vendasAnos = $this->pdo->query("SELECT DISTINCT YEAR({$dt}) y FROM orcamentos_cv WHERE status='aprovado' AND {$dt} IS NOT NULL ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
        if (!$vendasAnos) $vendasAnos = [(int)date('Y')];
        if (!in_array($ano, array_map('intval', $vendasAnos), true)) $vendasAnos[] = $ano;
        rsort($vendasAnos);

        $this->renderApp('vendas', get_defined_vars());
    }
}

<?php
declare(strict_types=1);

final class ContaReceber
{
    public function __construct(private PDO $pdo) {}

    /** @return array{sql:string,params:array} */
    private function periodoClause(string $campo, ?string $ano, ?string $mes, ?string $de, ?string $ate): array
    {
        if ($de && $ate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $de) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
            if ($de > $ate) {
                [$de, $ate] = [$ate, $de];
            }
            return ["DATE($campo) BETWEEN ? AND ?", [$de, $ate]];
        }
        if ($ano && $mes && $mes !== 'todos') {
            return ["YEAR($campo)=? AND MONTH($campo)=?", [(int)$ano, (int)$mes]];
        }
        if ($ano) {
            return ["YEAR($campo)=?", [(int)$ano]];
        }
        return ['', []];
    }

    public function listar(string $filtro = 'abertas', ?string $ano = null, ?string $mes = null, ?string $de = null, ?string $ate = null): array
    {
        $sql = 'SELECT cr.*, c.nome cliente FROM contas_receber cr LEFT JOIN clientes c ON c.id=cr.cliente_id';
        $conds = [];
        $params = [];
        $statusWhere = [
            'abertas' => "cr.status IN ('pendente','parcial')",
            'vencidas' => "cr.status IN ('pendente','parcial') AND cr.data_vencimento < CURRENT_DATE()",
            'pagas' => "cr.status='pago'",
            'canceladas' => "cr.status='cancelado'",
        ];
        if (isset($statusWhere[$filtro])) {
            $conds[] = $statusWhere[$filtro];
        }

        // período filtra por vencimento (visão de caixa esperado) e, em pagas, por data_pagamento se houver
        $campo = ($filtro === 'pagas') ? 'COALESCE(cr.data_pagamento, cr.data_vencimento)' : 'cr.data_vencimento';
        [$pSql, $pParams] = $this->periodoClause($campo, $ano, $mes, $de, $ate);
        if ($pSql !== '') {
            $conds[] = $pSql;
            $params = array_merge($params, $pParams);
        }

        if ($conds) {
            $sql .= ' WHERE ' . implode(' AND ', $conds);
        }
        $sql .= " ORDER BY FIELD(cr.status,'pendente','parcial','pago','cancelado'), cr.data_vencimento ASC, cr.id DESC LIMIT 500";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function resumo(?string $ano = null, ?string $mes = null, ?string $de = null, ?string $ate = null): array
    {
        [$pVenc, $pVencParams] = $this->periodoClause('data_vencimento', $ano, $mes, $de, $ate);
        [$pPag, $pPagParams] = $this->periodoClause('data_pagamento', $ano, $mes, $de, $ate);

        $sql = "SELECT
            COALESCE(SUM(CASE WHEN status IN ('pendente','parcial') THEN valor-valor_pago ELSE 0 END),0) aberto,
            COALESCE(SUM(CASE WHEN status IN ('pendente','parcial') AND data_vencimento < CURRENT_DATE() THEN valor-valor_pago ELSE 0 END),0) vencido,
            COALESCE(SUM(CASE WHEN status IN ('pendente','parcial') THEN 1 ELSE 0 END),0) qtd_aberto,
            COALESCE(SUM(CASE WHEN status='pago' THEN valor ELSE 0 END),0) recebido,
            COALESCE(SUM(CASE WHEN status IN ('pago','parcial') AND data_pagamento IS NOT NULL" . ($pPag !== '' ? " AND $pPag" : '') . " THEN valor_pago ELSE 0 END),0) recebido_periodo,
            COALESCE(SUM(CASE WHEN status IN ('pendente','parcial','pago')" . ($pVenc !== '' ? " AND $pVenc" : '') . " THEN valor ELSE 0 END),0) previsto_periodo,
            COALESCE(SUM(CASE WHEN status IN ('pendente','parcial')" . ($pVenc !== '' ? " AND $pVenc" : '') . " THEN valor-valor_pago ELSE 0 END),0) aberto_periodo
        FROM contas_receber";

        $params = array_merge($pPagParams, $pVencParams, $pVencParams);
        // Order of placeholders: recebido_periodo uses pPag, previsto uses pVenc, aberto_periodo uses pVenc
        $params = [];
        if ($pPag !== '') {
            $params = array_merge($params, $pPagParams);
        }
        if ($pVenc !== '') {
            $params = array_merge($params, $pVencParams); // previsto
            $params = array_merge($params, $pVencParams); // aberto_periodo
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetch();
    }
}

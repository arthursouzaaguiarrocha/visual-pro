<?php
declare(strict_types=1);

class DashboardController extends Controller
{

    public function index(): void
    {
        $page = 'dashboard';
        $stats = [];
        $chart = [];
        $recent = [];

        if (can(['admin'])) {
            $stats['clientes'] = (int)$this->pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
            $stats['orcamentos'] = (int)$this->pdo->query('SELECT COUNT(*) FROM orcamentos_cv')->fetchColumn();
            $stats['aprovados'] = (int)$this->pdo->query("SELECT COUNT(*) FROM orcamentos_cv WHERE status='aprovado'")->fetchColumn();
            $stats['pendentes'] = (int)$this->pdo->query("SELECT COUNT(*) FROM orcamentos_cv WHERE status='pendente'")->fetchColumn();
            $stats['entradas'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM movimentacoes_caixa WHERE tipo='entrada' AND MONTH(data_movimento)=MONTH(CURRENT_DATE()) AND YEAR(data_movimento)=YEAR(CURRENT_DATE())")->fetchColumn();
            $stats['saidas'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM movimentacoes_caixa WHERE tipo='saida' AND MONTH(data_movimento)=MONTH(CURRENT_DATE()) AND YEAR(data_movimento)=YEAR(CURRENT_DATE())")->fetchColumn();
            $stats['receber_aberto'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor - valor_pago),0) FROM contas_receber WHERE status IN ('pendente','parcial')")->fetchColumn();
            $stats['pagar_aberto'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor - valor_pago),0) FROM contas_pagar WHERE status IN ('pendente','parcial')")->fetchColumn();
            $stats['receber_vencido'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor - valor_pago),0) FROM contas_receber WHERE status IN ('pendente','parcial') AND data_vencimento < CURRENT_DATE()")->fetchColumn();
            $stats['pagar_vencido'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor - valor_pago),0) FROM contas_pagar WHERE status IN ('pendente','parcial') AND data_vencimento < CURRENT_DATE()")->fetchColumn();
            $stats['totalorc'] = (float)$this->pdo->query('SELECT COALESCE(SUM(valor),0) FROM orcamentos_cv')->fetchColumn();
            $stats['totalaprov'] = (float)$this->pdo->query("SELECT COALESCE(SUM(valor),0) FROM orcamentos_cv WHERE status='aprovado'")->fetchColumn();
            $stats['os'] = (int)$this->pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE status NOT IN ('concluida','cancelada')")->fetchColumn();
            $chart = $this->pdo->query("SELECT DATE_FORMAT(criado_em,'%Y-%m') mes,COUNT(*) qtd,SUM(valor) valor,SUM(CASE WHEN status='aprovado' THEN valor ELSE 0 END) aprovado FROM orcamentos_cv WHERE criado_em>=DATE_SUB(CURRENT_DATE(),INTERVAL 6 MONTH) GROUP BY mes ORDER BY mes")->fetchAll();
            $recent = $this->pdo->query('SELECT o.id,o.descricao,o.valor,o.status,o.criado_em,c.nome cliente FROM orcamentos_cv o JOIN clientes c ON c.id=o.cliente_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
        } else {
            if (can(['vendedor'])) {
                $stats['orcamentos'] = (int)$this->pdo->query('SELECT COUNT(*) FROM orcamentos_cv')->fetchColumn();
                $stats['pendentes'] = (int)$this->pdo->query("SELECT COUNT(*) FROM orcamentos_cv WHERE status='pendente'")->fetchColumn();
            }
            $stats['os'] = (int)$this->pdo->query("SELECT COUNT(*) FROM ordens_servico WHERE status NOT IN ('concluida','cancelada')")->fetchColumn();
            if (can(['vendedor'])) $recent = $this->pdo->query('SELECT o.id,o.descricao,o.valor,o.status,o.criado_em,c.nome cliente FROM orcamentos_cv o JOIN clientes c ON c.id=o.cliente_id ORDER BY o.id DESC LIMIT 6')->fetchAll();
        }

        $this->renderApp('dashboard', get_defined_vars());
    }
}

<?php
declare(strict_types=1);

class CaixaController extends Controller
{
    public function save(): void
    {

            $this->requireRole(['admin']);
            $tipo = $_POST['tipo'] ?? 'entrada';
            $desc = trim($_POST['descricao'] ?? '');
            $valor = (float)($_POST['valor'] ?? 0);
            $data = $_POST['data_movimento'] ?? date('Y-m-d');
            if (!in_array($tipo, ['entrada', 'saida'], true) || $desc === '' || $valor <= 0) throw new RuntimeException('Informe tipo, descrição e valor maior que zero.');
            $id=(int)($_POST['id'] ?? 0);
            if ($id) { $s=$this->pdo->prepare('UPDATE movimentacoes_caixa SET tipo=?,descricao=?,categoria=?,valor=?,data_movimento=? WHERE id=?'); $s->execute([$tipo,$desc,trim($_POST['categoria'] ?? '') ?: null,$valor,$data,$id]); flash('Movimentação atualizada.'); }
            else { $s = $this->pdo->prepare('INSERT INTO movimentacoes_caixa(tipo,descricao,categoria,valor,data_movimento,usuario_id) VALUES(?,?,?,?,?,?)'); $s->execute([$tipo, $desc, trim($_POST['categoria'] ?? '') ?: null, $valor, $data, $_SESSION['user']['id']]); flash('Movimentação registrada.'); }
            go('caixa');

    }

    public function delete(): void
    {

            $this->requireRole(['admin']); $id=(int)($_POST['id'] ?? 0); $s=$this->pdo->prepare('DELETE FROM movimentacoes_caixa WHERE id=?'); $s->execute([$id]); flash('Movimentação excluída.'); go('caixa');

    }


    public function index(): void
    {
        $page = 'caixa';

        $rows = $this->pdo->query('SELECT m.*,u.nome usuario FROM movimentacoes_caixa m LEFT JOIN usuarios u ON u.id=m.usuario_id ORDER BY data_movimento DESC,id DESC LIMIT 100')->fetchAll();
        $cash = $this->pdo->query("SELECT COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE -valor END),0) saldo,COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END),0) entradas,COALESCE(SUM(CASE WHEN tipo='saida' THEN valor ELSE 0 END),0) saidas FROM movimentacoes_caixa")->fetch();

        $this->renderApp('caixa', get_defined_vars());
    }
}

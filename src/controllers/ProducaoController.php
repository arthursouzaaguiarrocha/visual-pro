<?php
declare(strict_types=1);

class ProducaoController extends Controller
{
    public function update(): void
    {

            $this->requireRole(['admin', 'producao']);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('OS inválida.');
            $status = $_POST['status'] ?? 'fila';
            $prio = $_POST['prioridade'] ?? 'normal';
            if (!in_array($prio, ['baixa', 'normal', 'alta', 'urgente'], true)) throw new RuntimeException('Prioridade inválida.');
            $prazo = ($_POST['prazo'] ?? '') ?: null;
            $obs = trim($_POST['observacoes'] ?? '');
            $dataEntrega = trim($_POST['data_entrega'] ?? '');
            $dataEntrega = $dataEntrega !== '' ? str_replace('T', ' ', $dataEntrega) . (strlen($dataEntrega) === 16 ? ':00' : '') : null;
            if (!in_array($status, ['fila', 'em_producao', 'aguardando_instalacao', 'concluida', 'cancelada'], true)) throw new RuntimeException('Status inválido.');
            if (can(['producao'])) {
                $s = $this->pdo->prepare('UPDATE ordens_servico SET status=?,observacoes=?,data_entrega=? WHERE id=?');
                $s->execute([$status, $obs, $dataEntrega, $id]);
            } else {
                $s = $this->pdo->prepare('UPDATE ordens_servico SET status=?,prioridade=?,prazo=?,observacoes=?,responsavel_id=?,data_entrega=? WHERE id=?');
                $s->execute([$status, $prio, $prazo, $obs, ($_POST['responsavel_id'] ?? '') ?: null, $dataEntrega, $id]);
            }
            flash('Ordem de serviço atualizada.');
            go('producao');

    }

    public function baixa(): void
    {

            $this->requireRole(['admin', 'producao']);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('OS inválida.');
            $s = $this->pdo->prepare("UPDATE ordens_servico SET status='concluida', data_entrega=COALESCE(data_entrega, NOW()) WHERE id=? AND status IN ('fila','em_producao','aguardando_instalacao')");
            $s->execute([$id]);
            if (!$s->rowCount()) throw new RuntimeException('Não foi possível dar baixa nesta OS (já concluída ou inválida).');
            flash('Baixa realizada: OS marcada como concluída.');
            go('producao');

    }


    public function index(): void
    {
        $page = 'producao';

        $rows = $this->pdo->query('SELECT os.*,o.descricao,o.imagem_path,o.valor,o.id orcamento_num,c.nome cliente,c.telefone FROM ordens_servico os JOIN orcamentos_cv o ON o.id=os.orcamento_id JOIN clientes c ON c.id=o.cliente_id ORDER BY FIELD(os.status,"fila","em_producao","aguardando_instalacao","concluida","cancelada"),FIELD(os.prioridade,"urgente","alta","normal","baixa"),os.id DESC')->fetchAll();
        $users = can(['admin']) ? $this->pdo->query("SELECT id,nome FROM usuarios WHERE perfil='producao' AND ativo=1 ORDER BY nome")->fetchAll() : [];

        $this->renderApp('producao', get_defined_vars());
    }
}

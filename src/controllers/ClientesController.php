<?php
declare(strict_types=1);

class ClientesController extends Controller
{
    public function save(): void
    {

            $this->requireRole(['admin', 'vendedor']);
            $nome = trim($_POST['nome'] ?? '');
            $tel = trim($_POST['telefone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contato = trim($_POST['contato'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            if ($nome === '' || $tel === '') throw new RuntimeException('Nome e telefone são obrigatórios.');
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $s = $this->pdo->prepare('UPDATE clientes SET nome=?,telefone=?,email=?,contato=?,endereco=? WHERE id=?');
                $s->execute([$nome, $tel, $email ?: null, $contato ?: null, $endereco ?: null, $id]);
            } else {
                $s = $this->pdo->prepare('INSERT INTO clientes(nome,telefone,email,contato,endereco) VALUES(?,?,?,?,?)');
                $s->execute([$nome, $tel, $email ?: null, $contato ?: null, $endereco ?: null]);
            }
            flash('Cliente salvo com sucesso.');
            go(($_POST['return_to'] ?? '') === 'orcamentos' ? 'orcamentos' : 'clientes');

    }

    public function delete(): void
    {

            $this->requireRole(['admin', 'vendedor']);
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Cliente inválido.');
            $check = $this->pdo->prepare('SELECT COUNT(*) FROM orcamentos_cv WHERE cliente_id=?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) throw new RuntimeException('Este cliente possui orçamentos vinculados e não pode ser excluído.');
            $s = $this->pdo->prepare('DELETE FROM clientes WHERE id=?'); $s->execute([$id]);
            flash('Cliente excluído.'); go('clientes');

    }


    public function index(): void
    {
        $page = 'clientes';

        $rows = $this->pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();

        $this->renderApp('clientes', get_defined_vars());
    }
}

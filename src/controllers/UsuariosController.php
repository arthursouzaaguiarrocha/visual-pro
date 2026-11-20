<?php
declare(strict_types=1);

class UsuariosController extends Controller
{
    private const PERFIS = ['admin', 'vendedor', 'producao'];

    public function save(): void
    {
        $this->requireRole(['admin']);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'vendedor';
        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 8 || !in_array($perfil, self::PERFIS, true)) {
            throw new RuntimeException('Preencha os dados. A senha deve ter pelo menos 8 caracteres.');
        }
        $s = $this->pdo->prepare('INSERT INTO usuarios(nome,email,senha,perfil) VALUES(?,?,?,?)');
        $s->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
        flash('Usuário criado.');
        go('usuarios');
    }

    /** Edita nome/perfil e, opcionalmente, redefine a senha. */
    public function update(): void
    {
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $perfil = $_POST['perfil'] ?? '';
        $senha = (string)($_POST['senha'] ?? '');
        if ($id <= 0 || $nome === '' || !in_array($perfil, self::PERFIS, true)) {
            throw new RuntimeException('Informe nome e perfil válidos.');
        }
        if ($senha !== '' && strlen($senha) < 8) {
            throw new RuntimeException('A nova senha deve ter pelo menos 8 caracteres.');
        }
        $atual = $this->pdo->prepare('SELECT perfil, ativo FROM usuarios WHERE id=?');
        $atual->execute([$id]);
        $u = $atual->fetch();
        if (!$u) throw new RuntimeException('Usuário não encontrado.');
        if ($u['perfil'] === 'admin' && $perfil !== 'admin') {
            $this->garantirOutroAdmin($id);
        }
        if ($senha !== '') {
            $s = $this->pdo->prepare('UPDATE usuarios SET nome=?, perfil=?, senha=? WHERE id=?');
            $s->execute([$nome, $perfil, password_hash($senha, PASSWORD_DEFAULT), $id]);
        } else {
            $s = $this->pdo->prepare('UPDATE usuarios SET nome=?, perfil=? WHERE id=?');
            $s->execute([$nome, $perfil, $id]);
        }
        if ($id === $this->userId()) {
            $_SESSION['user']['nome'] = $nome;
            $_SESSION['user']['perfil'] = $perfil;
        }
        flash('Usuário atualizado.' . ($senha !== '' ? ' Senha redefinida.' : ''));
        go('usuarios');
    }

    /** Ativa/desativa um usuário. */
    public function toggle(): void
    {
        $this->requireRole(['admin']);
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Usuário inválido.');
        if ($id === $this->userId()) throw new RuntimeException('Você não pode desativar o seu próprio usuário.');
        $atual = $this->pdo->prepare('SELECT perfil, ativo FROM usuarios WHERE id=?');
        $atual->execute([$id]);
        $u = $atual->fetch();
        if (!$u) throw new RuntimeException('Usuário não encontrado.');
        if ((int)$u['ativo'] === 1 && $u['perfil'] === 'admin') {
            $this->garantirOutroAdmin($id);
        }
        $novo = (int)$u['ativo'] === 1 ? 0 : 1;
        $this->pdo->prepare('UPDATE usuarios SET ativo=? WHERE id=?')->execute([$novo, $id]);
        flash($novo ? 'Usuário reativado.' : 'Usuário desativado.');
        go('usuarios');
    }

    /** Impede ficar sem nenhum administrador ativo. */
    private function garantirOutroAdmin(int $idIgnorar): void
    {
        $c = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE perfil='admin' AND ativo=1 AND id<>?");
        $c->execute([$idIgnorar]);
        if ((int)$c->fetchColumn() < 1) {
            throw new RuntimeException('É preciso manter pelo menos um administrador ativo.');
        }
    }

    public function index(): void
    {
        $page = 'usuarios';
        $rows = $this->pdo->query('SELECT id,nome,email,perfil,ativo,criado_em FROM usuarios ORDER BY id DESC')->fetchAll();
        $this->renderApp('usuarios', get_defined_vars());
    }
}

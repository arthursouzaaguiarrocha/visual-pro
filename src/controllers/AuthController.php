<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private const MAX_TENTATIVAS = 5;
    private const JANELA_SEGUNDOS = 600;

    /** Arquivo de controle de tentativas por IP (dir. temporário do sistema). */
    private function throttleFile(): string
    {
        return sys_get_temp_dir() . '/visual_login_' . sha1((string)($_SERVER['REMOTE_ADDR'] ?? 'x')) . '.json';
    }

    /** @return array{count:int,first:int} */
    private function throttleState(): array
    {
        $f = $this->throttleFile();
        $d = is_file($f) ? json_decode((string)@file_get_contents($f), true) : null;
        if (!is_array($d) || !isset($d['count'], $d['first']) || time() - (int)$d['first'] > self::JANELA_SEGUNDOS) {
            return ['count' => 0, 'first' => time()];
        }
        return ['count' => (int)$d['count'], 'first' => (int)$d['first']];
    }

    public function login(): void
    {
        $st = $this->throttleState();
        if ($st['count'] >= self::MAX_TENTATIVAS) {
            $resta = max(1, (int)ceil((self::JANELA_SEGUNDOS - (time() - $st['first'])) / 60));
            flash('Muitas tentativas. Aguarde cerca de ' . $resta . ' minuto(s) e tente de novo.', 'danger');
            go('login');
        }

        $s = $this->pdo->prepare('SELECT id,nome,email,senha,perfil FROM usuarios WHERE email=? AND ativo=1');
        $s->execute([trim($_POST['email'] ?? '')]);
        $u = $s->fetch();
        if ($u && password_verify($_POST['senha'] ?? '', $u['senha'])) {
            @unlink($this->throttleFile());
            session_regenerate_id(true);
            unset($u['senha']);
            $_SESSION['user'] = $u;
            go('dashboard');
        }

        $st['count']++;
        @file_put_contents($this->throttleFile(), json_encode($st), LOCK_EX);
        usleep(300000); // atrasa tentativas automatizadas
        flash('E-mail ou senha inválidos.', 'danger');
        go('login');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ?page=login');
        exit;
    }

    public function showLogin(): void
    {
        $page = 'login';
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $this->view('login', compact('page', 'flash'));
    }
}

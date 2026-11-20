<?php
declare(strict_types=1);

/**
 * Controller base do Visual Pro.
 * Controllers de domínio estendem esta classe.
 */
abstract class Controller
{
    public function __construct(protected PDO $pdo)
    {
    }

    /** Renderiza uma view, extraindo $data para o escopo local. */
    protected function view(string $view, array $data = []): never
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo "View não encontrada: {$view}";
            exit;
        }
        require $viewFile;
        exit;
    }

    protected function requireRole(array $roles): void
    {
        require_role($roles);
    }

    protected function userId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Diretório absoluto de uploads de artes. */
    protected function uploadDir(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/artes';
    }

    /**
     * Processa upload de arte (JPG/PNG até 5 MB).
     * @return array{0:?string,1:?string} [caminho relativo, caminho absoluto]
     */
    protected function handleArteUpload(string $field = 'imagem_arte'): array
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        $upload = $_FILES[$field];
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Não foi possível enviar a imagem. Tente novamente.');
        }
        if ((int)$upload['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('A imagem deve ter no máximo 5 MB.');
        }
        if (!is_uploaded_file($upload['tmp_name'])) {
            throw new RuntimeException('Arquivo de imagem inválido.');
        }
        $imageInfo = @getimagesize($upload['tmp_name']);
        $mime = $imageInfo['mime'] ?? '';
        $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!$imageInfo || !isset($exts[$mime])) {
            throw new RuntimeException('Use uma imagem JPG ou PNG.');
        }
        $dir = $this->uploadDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . $exts[$mime];
        $absoluto = $dir . '/' . $fileName;
        if (!move_uploaded_file($upload['tmp_name'], $absoluto)) {
            throw new RuntimeException('Falha ao salvar a imagem enviada.');
        }
        return ['uploads/artes/' . $fileName, $absoluto];
    }

    /** Títulos das páginas do painel. */
    protected function pageTitle(string $page): string
    {
        return [
            'dashboard' => 'Dashboard',
            'clientes' => 'Clientes',
            'produtos' => 'Produtos',
            'materias' => 'Matérias-primas',
            'servicos' => 'Serviços',
            'orcamentos' => 'Orçamentos',
            'producao' => 'Ordens de serviço',
            'caixa' => 'Fluxo de caixa',
            'contas_receber' => 'Contas a receber',
            'contas_pagar' => 'Contas a pagar',
            'usuarios' => 'Usuários',
            'vendas' => 'Vendas e relatórios',
        ][$page] ?? 'Dashboard';
    }

    /**
     * Renderiza o layout principal (app.php) com dados da página.
     * @param array<string,mixed> $data
     */
    protected function renderApp(string $page, array $data = []): never
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $title = $this->pageTitle($page);
        // Remove internos do PHP / controller
        unset($data['this'], $data['pdo'], $data['controller']);
        $data = array_merge([
            'page' => $page,
            'title' => $title,
            'flash' => $flash,
            'stats' => $data['stats'] ?? [],
        ], $data);
        $this->view('app', $data);
    }
}

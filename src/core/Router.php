<?php
declare(strict_types=1);

/**
 * Roteador simples: mapeia page/action → Controller@método.
 */
final class Router
{
    /** @var array<string, array{0:class-string,1:string}> */
    private array $actions = [];

    /** @var array<string, array{0:class-string,1:string,2:list<string>}> */
    private array $pages = [];

    public function action(string $name, string $controller, string $method): self
    {
        $this->actions[$name] = [$controller, $method];
        return $this;
    }

    /** @param list<string> $roles */
    public function page(string $name, string $controller, string $method, array $roles = []): self
    {
        $this->pages[$name] = [$controller, $method, $roles];
        return $this;
    }

    public function dispatch(PDO $pdo, string $page, ?string $action = null): void
    {
        // POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== null && $action !== '') {
            // Proteção CSRF para todas as ações (inclusive login)
            try {
                csrf_verify();
            } catch (RuntimeException $ex) {
                flash($ex->getMessage(), 'danger');
                go($action === 'login' ? 'login' : $page);
            }
            if (!isset($this->actions[$action])) {
                flash('Ação inválida.', 'danger');
                go($page);
            }
            [$class, $method] = $this->actions[$action];
            // login não exige sessão; demais actions sim
            if ($action !== 'login') {
                require_login();
            }
            $controller = new $class($pdo);
            try {
                $controller->$method();
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // limpa upload órfão se o controller expôs a propriedade
                if (property_exists($controller, 'lastUploadAbsolute') && is_string($controller->lastUploadAbsolute ?? null)) {
                    $f = $controller->lastUploadAbsolute;
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }
                // PDOException estende RuntimeException: tratar primeiro para não vazar SQL.
                if ($ex instanceof PDOException) {
                    if (str_contains($ex->getMessage(), 'Duplicate')) {
                        $msg = 'Já existe um registro com estes dados (e-mail ou código duplicado).';
                    } else {
                        error_log('[Visual Pro] ' . $ex->getMessage());
                        $cod = preg_match('/^[0-9A-Za-z]{3,6}$/', (string)$ex->getCode()) ? ' (erro técnico ' . $ex->getCode() . ')' : '';
                        $msg = 'Não foi possível salvar. Confira os dados e tente novamente.' . $cod;
                    }
                } elseif ($ex instanceof RuntimeException) {
                    $msg = $ex->getMessage(); // mensagens de validação escritas nos controllers
                } else {
                    error_log('[Visual Pro] ' . $ex::class . ': ' . $ex->getMessage());
                    $msg = 'Ocorreu um erro inesperado. Tente novamente.';
                }
                flash($msg, 'danger');
                go($page);
            }
            return;
        }

        // Login redirect
        if ($page === 'login' && !empty($_SESSION['user'])) {
            go('dashboard');
        }
        if ($page !== 'login') {
            require_login();
        }

        if (!isset($this->pages[$page])) {
            $page = 'dashboard';
        }

        [$class, $method, $roles] = $this->pages[$page];
        if ($roles) {
            require_role($roles);
        }

        $controller = new $class($pdo);
        $controller->$method();
    }
}

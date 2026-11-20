<?php

class Cliente
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Listar todos os clientes
    public function all(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clientes");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar cliente pelo nome
    public function find_by_name(string $nome): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clientes WHERE nome = :nome"
        );

        $stmt->execute(['nome' => $nome]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar cliente pelo ID
    public function find_by_id(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clientes WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cadastrar ou atualizar cliente
    public function create_or_update(
        int $id,
        string $nome,
        string $telefone,
        ?string $email = null,
        ?string $contato = null,
        ?string $endereco = null
    ): bool {
        if ($id > 0) {
            $sql = "UPDATE clientes
                    SET nome = :nome,
                        telefone = :telefone,
                        email = :email,
                        contato = :contato,
                        endereco = :endereco
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email,
                'contato' => $contato,
                'endereco' => $endereco,
                'id' => $id
            ]);
        }

        $sql = "INSERT INTO clientes (nome, telefone, email, contato, endereco)
                VALUES (:nome, :telefone, :email, :contato, :endereco)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'nome' => $nome,
            'telefone' => $telefone,
            'email' => $email,
            'contato' => $contato,
            'endereco' => $endereco
        ]);
    }

    // Excluir cliente
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM clientes WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}

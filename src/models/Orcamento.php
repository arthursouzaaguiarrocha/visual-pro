
<?php

class Orcamento
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Listar todos os orçamentos
    public function all(): array
    {
        $sql = "SELECT o.*, c.nome AS cliente_nome
                FROM orcamentos o
                INNER JOIN clientes c ON c.id = o.cliente_id
                ORDER BY o.id DESC";

        return $this->pdo->query($sql)
                         ->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar orçamento pelo ID
    public function find_by_id(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, c.nome AS cliente_nome
             FROM orcamentos o
             INNER JOIN clientes c ON c.id = o.cliente_id
             WHERE o.id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cadastrar orçamento
    public function create(
        int $cliente_id,
        string $descricao,
        float $valor
    ): bool {
        $sql = "INSERT INTO orcamentos
                (cliente_id, descricao, valor)
                VALUES (:cliente_id, :descricao, :valor)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'cliente_id' => $cliente_id,
            'descricao' => $descricao,
            'valor' => $valor
        ]);
    }

    // Atualizar orçamento
    public function update(
        int $id,
        int $cliente_id,
        string $descricao,
        float $valor,
        string $status
    ): bool {
        $sql = "UPDATE orcamentos
                SET cliente_id = :cliente_id,
                    descricao = :descricao,
                    valor = :valor,
                    status = :status
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'cliente_id' => $cliente_id,
            'descricao' => $descricao,
            'valor' => $valor,
            'status' => $status,
            'id' => $id
        ]);
    }

    // Excluir orçamento
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM orcamentos WHERE id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}

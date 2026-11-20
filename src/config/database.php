<?php
declare(strict_types=1);

/**
 * Conexão com o banco de dados.
 * Preferência: variáveis de ambiente (DB_HOST, DB_NAME, DB_USER, DB_PASS).
 * Fallback: valores abaixo (adequados para XAMPP local).
 */
class Database
{
    public static function connection(): PDO
    {
        $host   = getenv('DB_HOST') ?: '';
        $dbname = getenv('DB_NAME') ?: '';
        $user   = getenv('DB_USER') ?: '';
        $pass   = getenv('DB_PASS') ?: '';

        return new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Mantém NOW()/CURRENT_DATE no horário de Brasília (alinha com o PHP)
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'",
            ]
        );
    }
}

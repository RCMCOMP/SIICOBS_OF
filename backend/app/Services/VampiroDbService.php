<?php

namespace App\Services;

use PDO;
use PDOException;
use Exception;
use Illuminate\Support\Facades\Log;

class VampiroDbService
{
    private static ?PDO $pdo = null;

    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $connection = config('database.default', env('DB_CONNECTION', 'sqlite'));
            $database = config("database.connections.{$connection}.database", env('DB_DATABASE', 'demo_bdvampiro.sqlite'));
            $host = config("database.connections.{$connection}.host", env('DB_HOST', '127.0.0.1'));
            $port = config("database.connections.{$connection}.port", env('DB_PORT', '3306'));
            $uid = config("database.connections.{$connection}.username", env('DB_USERNAME', 'root'));
            $pwd = config("database.connections.{$connection}.password", env('DB_PASSWORD', ''));

            if ($connection === 'sqlite') {
                try {
                    $dbPath = $database;
                    if (!file_exists($dbPath) && file_exists(database_path($dbPath))) {
                        $dbPath = database_path($dbPath);
                    } elseif (!file_exists($dbPath) && file_exists(database_path('demo_bdvampiro.sqlite'))) {
                        $dbPath = database_path('demo_bdvampiro.sqlite');
                    }
                    self::$pdo = new PDO("sqlite:{$dbPath}", null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                } catch (PDOException $e) {
                    Log::error("Error conectando a SQLite bdvampiro: " . $e->getMessage());
                    throw new Exception("Error conectando a SQLite: " . $e->getMessage());
                }
            } elseif ($connection === 'mysql') {
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                    self::$pdo = new PDO($dsn, $uid, $pwd, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                } catch (PDOException $e) {
                    Log::error("Error conectando a MySQL bdvampiro: " . $e->getMessage());
                    throw new Exception("Error conectando a MySQL bdvampiro: " . $e->getMessage());
                }
            } else {
                // Fallback a SQL Server por ODBC
                $driver = env('DB_ODBC_DRIVER', 'ODBC Driver 17 for SQL Server');
                $dsn = "odbc:Driver={{$driver}};Server={$host};Database={$database};Uid={$uid};Pwd={$pwd};";

                try {
                    self::$pdo = new PDO($dsn, $uid, $pwd, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (PDOException $e) {
                    try {
                        self::$pdo = new PDO("odbc:sqllocal", $uid, $pwd, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                    } catch (Exception $ex) {
                        Log::error("Error conectando a BD Vampiro MSSQL: " . $e->getMessage() . " / " . $ex->getMessage());
                        throw new Exception("Error de conexión con la base de datos bdvampiro: " . $e->getMessage());
                    }
                }
            }
        }
        return self::$pdo;
    }

    public static function toUtf8($data)
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $k => $v) {
                $cleanKey = is_string($k) ? self::toUtf8($k) : $k;
                $clean[$cleanKey] = self::toUtf8($v);
            }
            return $clean;
        }
        if (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = mb_convert_encoding($data, 'UTF-8', 'Windows-1252');
            }
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        return $data;
    }

    public function select(string $sql, array $params = []): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return self::toUtf8($results);
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $res = $this->select($sql, $params);
        return !empty($res) ? $res[0] : null;
    }

    public function insert(string $sql, array $params = []): bool
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function update(string $sql, array $params = []): int
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function statement(string $sql, array $params = []): bool
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function paginate(string $baseSql, array $params = [], int $page = 1, int $perPage = 25, string $orderBy = '1 ASC'): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $connection = config('database.default', env('DB_CONNECTION', 'sqlite'));
        
        // Conteo total
        $countSql = "SELECT COUNT(*) as total_count FROM ({$baseSql}) AS subquery_count";
        $totalRow = $this->selectOne($countSql, $params);
        $total = $totalRow['total_count'] ?? 0;

        if ($connection === 'mysql' || $connection === 'sqlite') {
            $pagedSql = "{$baseSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        } else {
            $pagedSql = "{$baseSql} ORDER BY {$orderBy} OFFSET {$offset} ROWS FETCH NEXT {$perPage} ROWS ONLY";
        }
        
        $items = $this->select($pagedSql, $params);

        return [
            'data' => $items,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }
}

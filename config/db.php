<?php
/**
 * config/db.php — Connexion MariaDB via PDO
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'association_db');
define('DB_USER', 'root');          // ← à modifier
define('DB_PASS', '');              // ← à modifier
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO (singleton).
 */
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production, loguer l'erreur sans l'afficher
            error_log('[DB] Connexion échouée: ' . $e->getMessage());
            http_response_code(503);
            die(json_encode(['success' => false, 'message' => 'Service temporairement indisponible.']));
        }
    }
    return $pdo;
}

/**
 * Exécute une requête préparée et retourne le statement.
 */
function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Récupère une seule ligne.
 */
function dbFetchOne(string $sql, array $params = []): ?array {
    $row = dbQuery($sql, $params)->fetch();
    return $row ?: null;
}

/**
 * Récupère toutes les lignes.
 */
function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

/**
 * Insert et retourne le dernier ID.
 */
function dbInsert(string $table, array $data): int {
    $cols = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders})", array_values($data));
    return (int) getPDO()->lastInsertId();
}

/**
 * Update avec conditions.
 */
function dbUpdate(string $table, array $data, array $where): int {
    $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
    $cond = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($where)));
    $params = array_merge(array_values($data), array_values($where));
    return dbQuery("UPDATE `{$table}` SET {$set} WHERE {$cond}", $params)->rowCount();
}

/**
 * Delete avec conditions.
 */
function dbDelete(string $table, array $where): int {
    $cond = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($where)));
    return dbQuery("DELETE FROM `{$table}` WHERE {$cond}", array_values($where))->rowCount();
}

/**
 * Compte les lignes avec conditions optionnelles.
 */
function dbCount(string $table, array $where = []): int {
    if (empty($where)) {
        return (int)(dbFetchOne("SELECT COUNT(*) c FROM `{$table}`")['c'] ?? 0);
    }
    $cond = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($where)));
    return (int)(dbFetchOne("SELECT COUNT(*) c FROM `{$table}` WHERE {$cond}", array_values($where))['c'] ?? 0);
}

/**
 * Retourne un paramètre système depuis la table parametres.
 */
function getParam(string $cle, string $default = ''): string {
    try {
        $row = dbFetchOne('SELECT valeur FROM parametres WHERE cle = ?', [$cle]);
        return $row['valeur'] ?? $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Paginate query - retourne [rows, total, pages].
 */
function dbPaginate(string $sql, array $params, int $page, int $perPage): array {
    $countSql = preg_replace('/SELECT\s+.+?\s+FROM/si', 'SELECT COUNT(*) AS c FROM', $sql);
    $countSql = preg_replace('/ORDER\s+BY\s+.+$/si', '', $countSql);
    $total    = (int)(dbFetchOne($countSql, $params)['c'] ?? 0);
    $offset   = ($page - 1) * $perPage;
    $rows     = dbFetchAll($sql . " LIMIT {$perPage} OFFSET {$offset}", $params);
    return [
        'rows'  => $rows,
        'total' => $total,
        'pages' => max(1, (int)ceil($total / $perPage)),
        'page'  => $page,
    ];
}

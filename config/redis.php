<?php
/**
 * config/redis.php — Connexion Redis via Predis
 */

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Retourne une instance client Predis (singleton).
 *
 * @return Predis\Client
 */
function getRedis(): ?Predis\Client {
    static $redis = null;

    if ($redis === null) {
        try {
            $redis = new Predis\Client([
                'scheme' => getenv('REDIS_SCHEME') ?: 'tcp',
                'host'   => getenv('REDIS_HOST')   ?: '127.0.0.1',
                'port'   => getenv('REDIS_PORT')   ?: 6379,
            ]);
            // Teste la connexion pour échouer rapidement si Redis n'est pas disponible
            $redis->connect();
        } catch (Exception $e) {
            error_log('[Redis] Connexion échouée: ' . $e->getMessage());
            return null; // Retourne null pour que l'application puisse fonctionner sans cache
        }
    }
    return $redis;
}
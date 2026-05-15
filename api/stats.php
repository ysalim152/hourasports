<?php
/**
 * api/actualites/stats.php — Statistiques pour le dashboard admin des actualités.
 * GET /api/actualites/stats.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

// Seuls les coachs et admins peuvent voir les statistiques
requireRole('coach');

header('Content-Type: application/json; charset=utf-8');
// On peut mettre en cache ces stats pour une courte durée
header('Cache-Control: max-age=120');

try {
    $pdo = getPDO();
    $data = [];

    // --- 1. Statistiques par statut ---
    $statsStatus = $pdo->query(
        "SELECT statut, COUNT(*) AS total FROM actualites GROUP BY statut"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['stats_by_status'] = [
        'brouillon' => (int)($statsStatus['brouillon'] ?? 0),
        'publie'    => (int)($statsStatus['publie'] ?? 0),
        'archive'   => (int)($statsStatus['archive'] ?? 0),
    ];

    // --- 2. Statistiques par catégorie ---
    $statsCategory = $pdo->query(
        "SELECT categorie, COUNT(*) AS total
         FROM actualites
         WHERE categorie IS NOT NULL AND categorie != ''
         GROUP BY categorie"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    $data['stats_by_category'] = array_map('intval', $statsCategory);

    // --- 3. Statistiques d'engagement global ---
    $engagement = dbFetchOne(
        "SELECT
            SUM(vues) AS total_views,
            SUM(likes_count) AS total_likes,
            SUM(shares_count) AS total_shares,
            SUM(comments_count) AS total_comments
         FROM actualites"
    );
    $data['engagement'] = [
        'total_views'    => (int)($engagement['total_views'] ?? 0),
        'total_likes'    => (int)($engagement['total_likes'] ?? 0),
        'total_shares'   => (int)($engagement['total_shares'] ?? 0),
        'total_comments' => (int)($engagement['total_comments'] ?? 0),
    ];

    // --- 4. Articles récents ---
    $data['recent_articles'] = dbFetchAll(
        "SELECT id, titre, slug, vues, likes_count, published_at
         FROM actualites
         WHERE statut = 'publie'
         ORDER BY published_at DESC
         LIMIT 5"
    );

    // --- 5. Articles "tendance" (les plus aimés sur les 30 derniers jours) ---
    $data['trending'] = dbFetchAll(
        "SELECT id, titre, slug, vues, likes_count
         FROM actualites
         WHERE statut = 'publie' AND published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY likes_count DESC, vues DESC
         LIMIT 5"
    );

    // --- 6. Articles programmés ---
    $data['scheduled'] = dbFetchAll(
        "SELECT id, titre, slug, scheduled_at,
                CONCAT(u.prenom, ' ', u.nom) AS auteur
         FROM actualites a
         LEFT JOIN utilisateurs u ON a.auteur_id = u.id
         WHERE a.statut = 'brouillon' AND a.scheduled_at > NOW()
         ORDER BY a.scheduled_at ASC
         LIMIT 5"
    );

    // --- 7. Articles les plus vus ---
    $data['most_viewed'] = dbFetchAll(
        "SELECT id, titre, slug, vues
         FROM actualites
         WHERE statut = 'publie'
         ORDER BY vues DESC
         LIMIT 5"
    );

    // --- Réponse finale ---
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'generated_at' => date('c'),
    ]);

} catch (PDOException $e) {
    error_log('[API stats] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur lors de la récupération des statistiques.']);
}

?>

```

### Points clés de cet endpoint

*   **Sécurisé** : Il vérifie que l'utilisateur a au moins le rôle `coach` pour accéder aux données.
*   **Performant** : Il utilise des requêtes SQL agrégées (`GROUP BY`, `SUM`, `COUNT`) pour laisser la base de données faire le gros du travail, ce qui est beaucoup plus rapide que de tout traiter en PHP.
*   **Complet** : Il fournit toutes les données nécessaires pour construire un tableau de bord riche, comme spécifié dans votre documentation, et j'y ai même ajouté les articles les plus vus pour plus de pertinence.
*   **Mise en cache** : Il inclut un en-tête `Cache-Control` pour que le navigateur du client puisse mettre en cache les résultats pendant 2 minutes, évitant des appels répétés et inutiles lors de la navigation dans l'interface d'administration.

Vous pouvez maintenant appeler `GET /api/actualites/stats.php` depuis votre JavaScript pour alimenter dynamiquement votre page de tableau de bord.

<!--
[PROMPT_SUGGESTION]Comment puis-je ajouter un système de commentaires imbriqués (réponses aux commentaires) ?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Génère le code JavaScript pour afficher les statistiques de `api/actualites/stats.php` dans le dashboard admin.[/PROMPT_SUGGESTION]
-->
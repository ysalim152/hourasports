<?php
/**
 * api/actualites.php — CRUD Articles / Blog
 * GET    /api/actualites.php           → liste (publiques)
 * GET    /api/actualites.php?id=X      → un article
 * GET    /api/actualites.php?slug=X    → par slug
 * POST   /api/actualites.php           → créer   (coach+)
 * PUT    /api/actualites.php?id=X      → modifier (coach+)
 * DELETE /api/actualites.php?id=X      → supprimer (admin)
 * PUT    /api/actualites.php?action=publish&id=X → publier/dépublier
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$slug   = trim($_GET['slug']   ?? '');
$action = trim($_GET['action'] ?? '');

// Lecture publique autorisée, écriture nécessite coach+
$isPublicRead = ($method === 'GET');

if (!$isPublicRead) {
    requireRole('coach');
}

try {
    switch ($method) {

        /* ── LIST / ONE ── */
        case 'GET':
            if ($id || $slug) {
                $col = $id ? 'a.id' : 'a.slug';
                $val = $id ?: $slug;
                // Si non connecté → uniquement les publiés
                $statutFilter = isLoggedIn() ? '' : ' AND a.statut = "publie"';
                $row = dbFetchOne(
                    "SELECT a.id, a.titre, a.slug, a.contenu, a.extrait, a.image,
                            a.categorie, a.tags, a.statut, a.vues, a.published_at, a.created_at,
                            CONCAT(u.prenom,' ',u.nom) AS auteur, u.id AS auteur_id
                     FROM actualites a
                     LEFT JOIN utilisateurs u ON u.id = a.auteur_id
                     WHERE {$col} = ?{$statutFilter}",
                    [$val]
                );
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Article introuvable.']);
                    exit;
                }
                // Incrémenter vues
                dbQuery('UPDATE actualites SET vues = vues + 1 WHERE id = ?', [$row['id']]);
                $row['vues']++;
                $row['tags'] = json_decode($row['tags'] ?? '[]', true) ?: [];
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                $statut   = trim($_GET['statut']    ?? '');
                $categorie= trim($_GET['categorie'] ?? '');
                $q        = trim($_GET['q']         ?? '');
                $page     = max(1, (int)($_GET['page']     ?? 1));
                $perPage  = min(50, max(5, (int)($_GET['per_page'] ?? 10)));

                $where  = [];
                $params = [];

                // Non connecté → publiés seulement
                if (!isLoggedIn()) {
                    $where[] = 'a.statut = "publie"';
                } elseif ($statut) {
                    $where[] = 'a.statut = ?'; $params[] = $statut;
                }
                if ($categorie) { $where[] = 'a.categorie = ?'; $params[] = $categorie; }
                if ($q) {
                    $where[] = '(a.titre LIKE ? OR a.extrait LIKE ?)';
                    $like = "%{$q}%"; $params[] = $like; $params[] = $like;
                }

                $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
                $total    = (int)(dbFetchOne(
                    "SELECT COUNT(*) c FROM actualites a {$whereSQL}", $params
                )['c'] ?? 0);
                $offset   = ($page - 1) * $perPage;

                $rows = dbFetchAll(
                    "SELECT a.id, a.titre, a.slug, a.extrait, a.image, a.categorie,
                            a.tags, a.statut, a.vues, a.published_at, a.created_at,
                            CONCAT(u.prenom,' ',u.nom) AS auteur
                     FROM actualites a
                     LEFT JOIN utilisateurs u ON u.id = a.auteur_id
                     {$whereSQL}
                     ORDER BY a.published_at DESC, a.created_at DESC
                     LIMIT {$perPage} OFFSET {$offset}",
                    $params
                );

                foreach ($rows as &$r) {
                    $r['tags'] = json_decode($r['tags'] ?? '[]', true) ?: [];
                }

                echo json_encode([
                    'success'  => true,
                    'data'     => $rows,
                    'total'    => $total,
                    'page'     => $page,
                    'per_page' => $perPage,
                    'pages'    => max(1, (int)ceil($total / $perPage)),
                ]);
            }
            break;

        /* ── CREATE ── */
        case 'POST':
            $d = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($d['titre'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Le titre est obligatoire.']);
                exit;
            }
            // Générer slug unique
            $slug = strtolower(trim($d['slug'] ?? $d['titre']));
            $slug = preg_replace('/[àáâäãåā]/u','a', $slug);
            $slug = preg_replace('/[éèêëē]/u','e',  $slug);
            $slug = preg_replace('/[ìíîïī]/u','i',  $slug);
            $slug = preg_replace('/[òóôöõøō]/u','o',$slug);
            $slug = preg_replace('/[ùúûüū]/u','u',  $slug);
            $slug = preg_replace('/ç/u','c', $slug);
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', trim($slug, '-'));
            // Unicité
            $baseSlug = $slug;
            $i = 1;
            while (dbFetchOne('SELECT id FROM actualites WHERE slug = ?', [$slug])) {
                $slug = $baseSlug . '-' . $i++;
            }

            $statut      = in_array($d['statut'] ?? '', ['brouillon','publie','archive']) ? $d['statut'] : 'brouillon';
            $publishedAt = ($statut === 'publie') ? date('Y-m-d H:i:s') : null;

            $newId = dbInsert('actualites', [
                'titre'        => $d['titre'],
                'slug'         => $slug,
                'contenu'      => $d['contenu']   ?? '',
                'extrait'      => $d['extrait']   ?? null,
                'image'        => $d['image']     ?? null,
                'categorie'    => $d['categorie'] ?? null,
                'tags'         => !empty($d['tags']) ? json_encode($d['tags']) : null,
                'auteur_id'    => currentUserId(),
                'statut'       => $statut,
                'published_at' => $publishedAt,
            ]);

            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'creation_article',
                'table_cible'    => 'actualites',
                'id_cible'       => $newId,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Article créé.', 'id' => $newId, 'slug' => $slug]);
            break;

        /* ── UPDATE ── */
        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            $d = json_decode(file_get_contents('php://input'), true) ?? [];

            // Action rapide : publier/dépublier
            if ($action === 'publish') {
                $current = dbFetchOne('SELECT statut FROM actualites WHERE id = ?', [$id]);
                if (!$current) { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Article introuvable.']); exit; }
                $newStatut    = $current['statut'] === 'publie' ? 'brouillon' : 'publie';
                $publishedAt  = $newStatut === 'publie' ? date('Y-m-d H:i:s') : null;
                dbUpdate('actualites',
                    ['statut' => $newStatut, 'published_at' => $publishedAt],
                    ['id' => $id]
                );
                echo json_encode(['success' => true, 'message' => "Article {$newStatut}.", 'statut' => $newStatut]);
                break;
            }

            $statut      = in_array($d['statut'] ?? '', ['brouillon','publie','archive']) ? $d['statut'] : null;
            $publishedAt = ($statut === 'publie') ? ($d['published_at'] ?? date('Y-m-d H:i:s')) : null;

            $updateData = array_filter([
                'titre'        => $d['titre']     ?? null,
                'contenu'      => $d['contenu']   ?? null,
                'extrait'      => $d['extrait']   ?? null,
                'image'        => $d['image']     ?? null,
                'categorie'    => $d['categorie'] ?? null,
                'tags'         => !empty($d['tags']) ? json_encode($d['tags']) : null,
                'statut'       => $statut,
                'published_at' => $publishedAt,
            ], fn($v) => $v !== null);

            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour.']);
                exit;
            }
            dbUpdate('actualites', $updateData, ['id' => $id]);

            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'modification_article',
                'table_cible'    => 'actualites',
                'id_cible'       => $id,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Article mis à jour.']);
            break;

        /* ── DELETE ── */
        case 'DELETE':
            requireRole('admin');
            if (!$id) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID requis.']); exit; }
            $art = dbFetchOne('SELECT titre FROM actualites WHERE id = ?', [$id]);
            dbDelete('actualites', ['id' => $id]);
            dbInsert('audit_log', [
                'utilisateur_id' => currentUserId(),
                'action'         => 'suppression_article',
                'table_cible'    => 'actualites',
                'id_cible'       => $id,
                'details'        => json_encode($art),
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            echo json_encode(['success' => true, 'message' => 'Article supprimé.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non supportée.']);
    }

} catch (PDOException $e) {
    error_log('[API actualites] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}

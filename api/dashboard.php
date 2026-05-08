<?php
/**
 * api/dashboard.php — Statistiques dashboard admin
 * GET /api/dashboard.php
 * GET /api/dashboard.php?section=membres|cotisations|sessions|planning
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

requireRole('coach');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=120');

$section = $_GET['section'] ?? 'all';

try {
    $data = [];

    if ($section === 'all' || $section === 'kpi') {
        $data['kpi'] = [
            'membres_actifs'     => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="actif" AND role_id IN (3,4)')['c'] ?? 0),
            'adherents'          => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="actif" AND role_id=3')['c'] ?? 0),
            'participants'       => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="actif" AND role_id=4')['c'] ?? 0),
            'coaches'            => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="actif" AND role_id=2')['c'] ?? 0),
            'equipes_actives'    => (int)(dbFetchOne('SELECT COUNT(*) c FROM equipes WHERE statut="actif"')['c'] ?? 0),
            'sessions_mois'      => (int)(dbFetchOne('SELECT COUNT(*) c FROM sessions_entrainement WHERE DATE_FORMAT(date_debut,"%Y-%m")=DATE_FORMAT(NOW(),"%Y-%m")')['c'] ?? 0),
            'sessions_planifiees'=> (int)(dbFetchOne('SELECT COUNT(*) c FROM sessions_entrainement WHERE statut="planifie"')['c'] ?? 0),
            'messages_nouveaux'  => (int)(dbFetchOne('SELECT COUNT(*) c FROM contacts WHERE statut="nouveau"')['c'] ?? 0),
            'comptes_en_attente' => (int)(dbFetchOne('SELECT COUNT(*) c FROM utilisateurs WHERE statut="en_attente"')['c'] ?? 0),
            'nouveaux_membres_mois' => (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE DATE_FORMAT(created_at,"%Y-%m")=DATE_FORMAT(NOW(),"%Y-%m")')['c'] ?? 0),
        ];
    }

    if ($section === 'all' || $section === 'cotisations') {
        $payees   = (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE cotisation_payee="oui"')['c'] ?? 0);
        $nonPayees= (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE cotisation_payee="non"')['c'] ?? 0);
        $partiels = (int)(dbFetchOne('SELECT COUNT(*) c FROM membres WHERE cotisation_payee="partiel"')['c'] ?? 0);
        $total    = $payees + $nonPayees + $partiels;
        $data['cotisations'] = [
            'payees'    => $payees,
            'non_payees'=> $nonPayees,
            'partielles'=> $partiels,
            'total'     => $total,
            'taux_pct'  => $total > 0 ? round($payees / $total * 100) : 0,
            'par_formule' => dbFetchAll(
                'SELECT formule_cotisation AS formule, COUNT(*) AS nb,
                        SUM(CASE WHEN cotisation_payee="oui" THEN montant_cotisation ELSE 0 END) AS montant
                 FROM membres WHERE formule_cotisation IS NOT NULL GROUP BY formule_cotisation'
            ),
        ];
    }

    if ($section === 'all' || $section === 'disciplines') {
        $data['disciplines'] = dbFetchAll(
            'SELECT c.nom, c.icone, c.couleur, c.places_max,
                    COUNT(pd.id) AS inscrits,
                    COUNT(CASE WHEN pd.statut="actif" THEN 1 END) AS actifs
             FROM categories c
             LEFT JOIN participant_disciplines pd ON pd.categorie_id = c.id
             WHERE c.actif = 1
             GROUP BY c.id ORDER BY inscrits DESC'
        );
    }

    if ($section === 'all' || $section === 'sessions') {
        $data['sessions_prochaines'] = dbFetchAll(
            'SELECT s.id, s.titre, s.type, s.date_debut, s.date_fin, s.lieu, s.statut, s.capacite,
                    e.nom AS equipe, CONCAT(u.prenom," ",u.nom) AS coach
             FROM sessions_entrainement s
             LEFT JOIN equipes e ON e.id = s.equipe_id
             LEFT JOIN utilisateurs u ON u.id = s.coach_id
             WHERE s.statut IN ("planifie","en_cours")
               AND s.date_debut >= NOW()
             ORDER BY s.date_debut ASC LIMIT 5'
        );
        $data['sessions_recentes'] = dbFetchAll(
            'SELECT s.id, s.titre, s.type, s.date_debut, s.statut,
                    e.nom AS equipe,
                    COUNT(DISTINCT p.id) AS nb_presents
             FROM sessions_entrainement s
             LEFT JOIN equipes e ON e.id = s.equipe_id
             LEFT JOIN presences p ON p.session_id = s.id AND p.statut="present"
             WHERE s.statut = "termine"
             GROUP BY s.id
             ORDER BY s.date_debut DESC LIMIT 5'
        );
    }

    if ($section === 'all' || $section === 'activite') {
        $data['activite_recente'] = dbFetchAll(
            'SELECT al.action, al.table_cible, al.details, al.created_at,
                    CONCAT(u.prenom," ",u.nom) AS utilisateur, r.nom AS role
             FROM audit_log al
             LEFT JOIN utilisateurs u ON u.id = al.utilisateur_id
             LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY al.created_at DESC LIMIT 10'
        );
    }

    if ($section === 'all' || $section === 'roles') {
        $data['repartition_roles'] = dbFetchAll(
            'SELECT r.nom, r.label, r.couleur, r.icone,
                    COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN utilisateurs u ON u.role_id = r.id AND u.statut="actif"
             GROUP BY r.id ORDER BY r.niveau_acces DESC'
        );
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    error_log('[API dashboard] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}

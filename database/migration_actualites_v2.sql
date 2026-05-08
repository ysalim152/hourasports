-- ============================================================
-- SCRIPT MIGRATION : AMÉLIORATION SYSTÈME ACTUALITÉS
-- Connexion à la base de données existante `association_db`
-- À exécuter après sauvegarde de la DB
-- ============================================================

USE `association_db`;

-- ============================================================
-- 1️⃣ ÉTAPE 1 : MODIFIER TABLE EXISTANTE `actualites`
-- ============================================================

-- Ajouter colonnes de programmation et engagement
ALTER TABLE `actualites` ADD COLUMN (
  `scheduled_at`      DATETIME          DEFAULT NULL
    COMMENT 'Publication programmée',
  `image_thumbnail`   VARCHAR(255)      DEFAULT NULL
    COMMENT 'Thumbnail 600×400px auto-généré',
  `image_webp`        VARCHAR(255)      DEFAULT NULL
    COMMENT 'Version WebP optimisée',
  `likes_count`       INT UNSIGNED      DEFAULT 0
    COMMENT 'Nombre de likes (dénormalisé pour perf)',
  `comments_count`    INT UNSIGNED      DEFAULT 0
    COMMENT 'Nombre de commentaires',
  `shares_count`      INT UNSIGNED      DEFAULT 0
    COMMENT 'Nombre de partages',
  `reading_time`      TINYINT UNSIGNED  DEFAULT 5
    COMMENT 'Minutes estimées de lecture',
  `is_featured`       TINYINT(1)        DEFAULT 0
    COMMENT 'Article épinglé/en vedette',
  `featured_until`    DATETIME          DEFAULT NULL
    COMMENT 'Date d''expiration de l''épinglage',
  `sport_id`          TINYINT UNSIGNED  DEFAULT NULL
    COMMENT 'Référence au sport (FK)',
  `age_group`         JSON              DEFAULT NULL
    COMMENT 'Groupes d''âge cibles: ["seniors","juniors","enfants"]',
  `notif_sent`        TINYINT(1)        DEFAULT 0
    COMMENT 'Notification push envoyée',
  `notif_sent_at`     DATETIME          DEFAULT NULL
    COMMENT 'Date d''envoi de la notification',
  `meta_description`  VARCHAR(160)      DEFAULT NULL
    COMMENT 'SEO: meta description',
  `meta_keywords`     VARCHAR(200)      DEFAULT NULL
    COMMENT 'SEO: meta keywords'
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Ajouter indexes pour optimiser requêtes
ALTER TABLE `actualites` 
  ADD INDEX `idx_scheduled_at` (`scheduled_at`),
  ADD INDEX `idx_featured` (`is_featured`, `featured_until`),
  ADD INDEX `idx_sport_id` (`sport_id`),
  ADD INDEX `idx_published_recent` (`published_at` DESC, `statut`);

-- Ajouter clé étrangère vers sport
ALTER TABLE `actualites`
  ADD CONSTRAINT `fk_actualites_sport`
    FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- ============================================================
-- 2️⃣ ÉTAPE 2 : CRÉER TABLE DE RÉFÉRENCE `sports`
-- ============================================================

CREATE TABLE IF NOT EXISTS `sports` (
  `id`          TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(50)       NOT NULL,
  `slug`        VARCHAR(50)       NOT NULL,
  `icone`       VARCHAR(5)        DEFAULT '⚽',
  `couleur`     VARCHAR(7)        DEFAULT '#e63946',
  `description` VARCHAR(200)      DEFAULT NULL,
  `actif`       TINYINT(1)        DEFAULT 1,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nom` (`nom`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Référentiel des sports proposés par l''association';

-- Insérer les sports de base (adaptez selon votre association)
INSERT INTO `sports` (`nom`, `slug`, `icone`, `couleur`, `description`) VALUES
('Football', 'football', '⚽', '#e63946', 'Sports collectif ballon'),
('Basketball', 'basketball', '🏀', '#f4a261', 'Sports collectif ballon'),
('Volleyball', 'volleyball', '🏐', '#2ecc71', 'Sports collectif filet'),
('Handball', 'handball', '🤾', '#3498db', 'Sports collectif ballon'),
('Natation', 'natation', '🏊', '#9b59b6', 'Sports aquatique'),
('Athlétisme', 'athletisme', '🏃', '#e74c3c', 'Sports individuels piste'),
('Judo', 'judo', '🥋', '#34495e', 'Arts martiaux lutte'),
('Tennis', 'tennis', '🎾', '#f1c40f', 'Sports raquette');

-- ============================================================
-- 3️⃣ ÉTAPE 3 : CRÉER TABLE `actualite_likes`
-- ============================================================

CREATE TABLE IF NOT EXISTS `actualite_likes` (
  `id`            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `actualite_id`  INT UNSIGNED      NOT NULL,
  `user_id`       INT UNSIGNED      NOT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_article` (`actualite_id`, `user_id`),
  KEY `fk_likes_actualite` (`actualite_id`),
  KEY `fk_likes_user` (`user_id`),
  KEY `idx_likes_date` (`created_at`),
  
  CONSTRAINT `fk_likes_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suivi des likes utilisateurs sur les articles';

-- ============================================================
-- 4️⃣ ÉTAPE 4 : CRÉER TABLE `actualite_saves` (Favoris)
-- ============================================================

CREATE TABLE IF NOT EXISTS `actualite_saves` (
  `id`            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `actualite_id`  INT UNSIGNED      NOT NULL,
  `user_id`       INT UNSIGNED      NOT NULL,
  `saved_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_saved` (`actualite_id`, `user_id`),
  KEY `fk_saves_user` (`user_id`),
  
  CONSTRAINT `fk_saves_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_saves_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Articles sauvegardés par les utilisateurs (À lire plus tard)';

-- ============================================================
-- 5️⃣ ÉTAPE 5 : CRÉER TABLE `actualite_commentaires` (Phase 2)
-- ============================================================

CREATE TABLE IF NOT EXISTS `actualite_commentaires` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `actualite_id`    INT UNSIGNED      NOT NULL,
  `user_id`         INT UNSIGNED      NOT NULL,
  `comment_parent_id` INT UNSIGNED    DEFAULT NULL
    COMMENT 'Pour les réponses imbriquées',
  `contenu`         TEXT              NOT NULL,
  `statut`          ENUM('en_attente','approuve','rejete','spam') 
                    DEFAULT 'en_attente',
  `signales_count`  TINYINT UNSIGNED  DEFAULT 0,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `fk_comments_actualite` (`actualite_id`),
  KEY `fk_comments_user` (`user_id`),
  KEY `fk_comments_parent` (`comment_parent_id`),
  KEY `idx_comments_statut` (`statut`),
  KEY `idx_comments_date` (`created_at` DESC),
  
  CONSTRAINT `fk_comments_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_parent`
    FOREIGN KEY (`comment_parent_id`) REFERENCES `actualite_commentaires` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Commentaires sur les articles (Phase 2)';

-- ============================================================
-- 6️⃣ ÉTAPE 6 : MISE À JOUR DES DONNÉES EXISTANTES
-- ============================================================

-- Calculer les temps de lecture pour articles existants
-- (Estimation: 200 mots par minute)
UPDATE `actualites` 
SET `reading_time` = CEIL(CHAR_LENGTH(CONCAT(titre, ' ', contenu)) / 1000);

-- Si vous avez besoin de mapper les catégories existantes à des sports:
-- UPDATE `actualites` 
-- SET `sport_id` = (SELECT id FROM `sports` WHERE slug = LOWER(`actualites`.`categorie`))
-- WHERE `categorie` IS NOT NULL;

-- Les articles existants gardent: statut='publie', is_featured=0, likes_count=0, etc.

-- ============================================================
-- 7️⃣ ÉTAPE 7 : CRÉER VUE POUR STATISTIQUES ARTICLES
-- ============================================================

CREATE OR REPLACE VIEW `vw_actualites_statistiques` AS
SELECT 
  a.id,
  a.titre,
  a.slug,
  COUNT(DISTINCT l.user_id) AS total_likes,
  COUNT(DISTINCT c.id) AS total_comments,
  COUNT(DISTINCT s.user_id) AS total_saves,
  a.vues,
  a.published_at,
  CONCAT(u.prenom, ' ', u.nom) AS auteur,
  a.statut,
  a.is_featured
FROM `actualites` a
LEFT JOIN `actualite_likes` l ON a.id = l.actualite_id
LEFT JOIN `actualite_commentaires` c ON a.id = c.actualite_id AND c.statut = 'approuve'
LEFT JOIN `actualite_saves` s ON a.id = s.actualite_id
LEFT JOIN `utilisateurs` u ON a.auteur_id = u.id
GROUP BY a.id
ORDER BY a.published_at DESC;

-- ============================================================
-- 8️⃣ ÉTAPE 8 : PROCÉDURE STOCKÉE - PUBLICATION PROGRAMMÉE
-- ============================================================

DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `proc_publier_articles_programmes`()
READS SQL DATA
COMMENT 'À exécuter par cron toutes les heures'
BEGIN
  UPDATE `actualites`
  SET `statut` = 'publie',
      `published_at` = NOW(),
      `notif_sent` = 0
  WHERE `statut` = 'brouillon'
    AND `scheduled_at` IS NOT NULL
    AND `scheduled_at` <= NOW();
END$$

DELIMITER ;

-- ============================================================
-- 9️⃣ ÉTAPE 9 : TRIGGER - METTRE À JOUR COMPTEURS
-- ============================================================

DELIMITER $$

-- Trigger: Incrémenter likes_count au like
CREATE TRIGGER IF NOT EXISTS `trg_likes_insert` AFTER INSERT ON `actualite_likes`
FOR EACH ROW
BEGIN
  UPDATE `actualites` 
  SET `likes_count` = `likes_count` + 1
  WHERE `id` = NEW.actualite_id;
END$$

-- Trigger: Décrémenter likes_count au unlike
CREATE TRIGGER IF NOT EXISTS `trg_likes_delete` AFTER DELETE ON `actualite_likes`
FOR EACH ROW
BEGIN
  UPDATE `actualites` 
  SET `likes_count` = GREATEST(0, `likes_count` - 1)
  WHERE `id` = OLD.actualite_id;
END$$

-- Trigger: Incrémenter comments_count (commentaires approuvés)
CREATE TRIGGER IF NOT EXISTS `trg_comments_insert` AFTER INSERT ON `actualite_commentaires`
FOR EACH ROW
BEGIN
  IF NEW.statut = 'approuve' THEN
    UPDATE `actualites` 
    SET `comments_count` = `comments_count` + 1
    WHERE `id` = NEW.actualite_id;
  END IF;
END$$

-- Trigger: Mettre à jour updated_at article si nouveau commentaire
CREATE TRIGGER IF NOT EXISTS `trg_comments_update` AFTER UPDATE ON `actualite_commentaires`
FOR EACH ROW
BEGIN
  UPDATE `actualites` 
  SET `updated_at` = NOW()
  WHERE `id` = NEW.actualite_id;
END$$

DELIMITER ;

-- ============================================================
-- 🔟 ÉTAPE 10 : VÉRIFICATION FINALE
-- ============================================================

-- Afficher la structure de la table actualites
DESC `actualites`;

-- Afficher les nouvelles tables
SHOW TABLES LIKE 'actualite_%';
SHOW TABLES LIKE 'sports';

-- Vérifier les données
SELECT COUNT(*) AS total_articles FROM `actualites`;
SELECT COUNT(*) AS total_sports FROM `sports`;
SELECT COUNT(*) AS total_likes FROM `actualite_likes`;
SELECT COUNT(*) AS total_saves FROM `actualite_saves`;

-- Vérifier les procédures/triggers
SHOW PROCEDURE STATUS LIKE '%publier%';
SHOW TRIGGERS LIKE 'trg_%';

-- ============================================================
-- ⚠️ NOTES IMPORTANTES
-- ============================================================

/*
1. SAUVEGARDE: Faites une sauvegarde complète AVANT exécution
   
2. ORDRE D'EXÉCUTION:
   - Exécuter ce script entièrement (les ALTER fonctionnent même si colonnes existent)
   - Vérifier les erreurs
   - Tester les endpoints API
   
3. DONNÉES DE TEST (optionnel):
   - Les articles existants conservent leurs valeurs
   - reading_time, likes_count, comments_count seront initialisés à 0/5
   
4. INDEXES:
   - Création automatique sur clés primaires et étrangères
   - Recherche sur published_at + statut optimisée pour liste publique
   
5. MIGRATIONS FUTURES:
   - Pour ajouter de nouveaux champs, toujours ALTER TABLE + CREATE INDEX
   - Pour modifier structures, TOUJOURS faire backup avant
   
6. CRON À CONFIGURER (sur votre serveur):
   */3 * * * * curl https://votresite.com/api/cron/publish-scheduled.php
   
   Cela triggera la procédure stocker proc_publier_articles_programmes()
*/

-- ============================================================
-- 📊 STATISTIQUES INITIALES (après migration)
-- ============================================================

SELECT 
  'Articles' AS type,
  COUNT(*) AS total
FROM `actualites`
UNION ALL
SELECT 'Sports', COUNT(*) FROM `sports`
UNION ALL
SELECT 'Likes', COUNT(*) FROM `actualite_likes`
UNION ALL
SELECT 'Saves', COUNT(*) FROM `actualite_saves`
UNION ALL
SELECT 'Commentaires', COUNT(*) FROM `actualite_commentaires`;

-- ============================================================
-- SCRIPT DE BASE DE DONNÉES COMPLET : association_db
-- Version fusionnée incluant la refonte du module d'actualités
--
-- NOTE : Ce fichier est une version consolidée.
-- Intégrez ces sections dans votre script `association_db.sql` principal.
-- ============================================================

-- Assurez-vous que la base de données est créée et sélectionnée au début de votre script principal.
CREATE DATABASE IF NOT EXISTS `association_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `association_db`;

-- Forcer le jeu de caractères de la connexion pour accepter les émojis
SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;

-- ============================================================
-- 1. CRÉER LA TABLE DE RÉFÉRENCE `sports` (AVANT `actualites`)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Référentiel des sports proposés';

-- Insérer les sports de base
INSERT IGNORE INTO `sports` (`nom`, `slug`, `icone`, `couleur`, `description`) VALUES
('Football', 'football', '⚽', '#e63946', 'Sports collectif ballon'),
('Basketball', 'basketball', '🏀', '#f4a261', 'Sports collectif ballon'),
('Volleyball', 'volleyball', '🏐', '#2ecc71', 'Sports collectif filet'),
('Handball', 'handball', '🤾', '#3498db', 'Sports collectif ballon'),
('Natation', 'natation', '🏊', '#9b59b6', 'Sports aquatique'),
('Athlétisme', 'athletisme', '🏃', '#e74c3c', 'Sports individuels piste'),
('Judo', 'judo', '🥋', '#34495e', 'Arts martiaux lutte'),
('Tennis', 'tennis', '🎾', '#f1c40f', 'Sports raquette');

-- ============================================================
-- 1.5. CRÉER LES TABLES `roles` ET `utilisateurs` (AVANT `actualites`)
-- ============================================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(50) NOT NULL,
  `label` VARCHAR(50) NOT NULL,
  `niveau_acces` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `roles` (`id`, `nom`, `label`, `niveau_acces`) VALUES
(1, 'admin', 'Administrateur', 4),
(2, 'coach', 'Coach', 3),
(3, 'adherent', 'Adhérent', 2),
(4, 'participant', 'Participant', 1),
(5, 'visiteur', 'Visiteur', 0);

CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role_id` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `statut` ENUM('actif','inactif','en_attente','suspendu') DEFAULT 'en_attente',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `derniere_connexion` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. MODIFIER LA DÉCLARATION `CREATE TABLE actualites`
-- Remplacez votre ancien `CREATE TABLE actualites` par celui-ci.
-- ============================================================

-- Supprimer les éléments dépendants AVANT de supprimer la table `actualites`
DROP VIEW IF EXISTS `vw_actualites_statistiques`;
DROP TABLE IF EXISTS `actualite_likes`;
DROP TABLE IF EXISTS `actualite_saves`;
DROP TABLE IF EXISTS `actualite_commentaires`;
DROP TABLE IF EXISTS `actualites`;
CREATE TABLE `actualites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `contenu` TEXT,
  `extrait` VARCHAR(500) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `categorie` VARCHAR(50) DEFAULT NULL,
  `tags` JSON DEFAULT NULL,
  `auteur_id` INT UNSIGNED DEFAULT NULL,
  `statut` ENUM('brouillon','publie','archive') DEFAULT 'brouillon',
  `vues` INT UNSIGNED DEFAULT 0,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- NOUVELLES COLONNES DE LA MIGRATION
  `scheduled_at` DATETIME DEFAULT NULL COMMENT 'Publication programmée',
  `image_thumbnail` VARCHAR(255) DEFAULT NULL COMMENT 'Thumbnail 600×400px auto-généré',
  `image_webp` VARCHAR(255) DEFAULT NULL COMMENT 'Version WebP optimisée',
  `likes_count` INT UNSIGNED DEFAULT 0 COMMENT 'Nombre de likes (dénormalisé)',
  `comments_count` INT UNSIGNED DEFAULT 0 COMMENT 'Nombre de commentaires',
  `shares_count` INT UNSIGNED DEFAULT 0 COMMENT 'Nombre de partages',
  `reading_time` TINYINT UNSIGNED DEFAULT 5 COMMENT 'Minutes estimées de lecture',
  `is_featured` TINYINT(1) DEFAULT 0 COMMENT 'Article épinglé/en vedette',
  `featured_until` DATETIME DEFAULT NULL COMMENT 'Date d''expiration de l''épinglage',
  `sport_id` TINYINT UNSIGNED DEFAULT NULL COMMENT 'Référence au sport (FK)',
  `age_group` JSON DEFAULT NULL COMMENT 'Groupes d''âge cibles: ["seniors","juniors"]',
  `notif_sent` TINYINT(1) DEFAULT 0 COMMENT 'Notification push envoyée',
  `notif_sent_at` DATETIME DEFAULT NULL COMMENT 'Date d''envoi de la notification',
  `meta_description` VARCHAR(160) DEFAULT NULL COMMENT 'SEO: meta description',
  `meta_keywords` VARCHAR(200) DEFAULT NULL COMMENT 'SEO: meta keywords',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `fk_actualites_auteur` (`auteur_id`),
  -- NOUVEAUX INDEX
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_featured` (`is_featured`, `featured_until`),
  KEY `idx_sport_id` (`sport_id`),
  KEY `idx_published_recent` (`published_at` DESC, `statut`),

  -- Assurez-vous que la table `utilisateurs` existe déjà
  CONSTRAINT `fk_actualites_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  -- NOUVELLE CLÉ ÉTRANGÈRE
  CONSTRAINT `fk_actualites_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. AJOUTER LES NOUVELLES TABLES D'ENGAGEMENT
-- Placez ce bloc après la création des tables `actualites` et `utilisateurs`.
-- ============================================================

CREATE TABLE IF NOT EXISTS `actualite_likes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actualite_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_article` (`actualite_id`, `user_id`),
  CONSTRAINT `fk_likes_actualite` FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Suivi des likes sur les articles';

CREATE TABLE IF NOT EXISTS `actualite_saves` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actualite_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `saved_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_saved` (`actualite_id`, `user_id`),
  CONSTRAINT `fk_saves_actualite` FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saves_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Articles sauvegardés par les utilisateurs';

CREATE TABLE IF NOT EXISTS `actualite_commentaires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actualite_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `comment_parent_id` INT UNSIGNED DEFAULT NULL COMMENT 'Pour les réponses imbriquées',
  `contenu` TEXT NOT NULL,
  `statut` ENUM('en_attente','approuve','rejete','spam') DEFAULT 'en_attente',
  `signales_count` TINYINT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comments_actualite` (`actualite_id`),
  KEY `fk_comments_user` (`user_id`),
  KEY `fk_comments_parent` (`comment_parent_id`),
  CONSTRAINT `fk_comments_actualite` FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_parent` FOREIGN KEY (`comment_parent_id`) REFERENCES `actualite_commentaires` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Commentaires sur les articles (Phase 2)';

-- ============================================================
-- 4. AJOUTER LA VUE, LA PROCÉDURE ET LES TRIGGERS
-- Placez ce bloc à la fin de votre script SQL.
-- ============================================================

-- VUE POUR STATISTIQUES
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

-- PROCÉDURE STOCKÉE POUR PUBLICATION PROGRAMMÉE
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

-- TRIGGERS POUR METTRE À JOUR LES COMPTEURS
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `trg_likes_insert` AFTER INSERT ON `actualite_likes`
FOR EACH ROW
BEGIN
  UPDATE `actualites`
  SET `likes_count` = `likes_count` + 1
  WHERE `id` = NEW.actualite_id;
END$$

CREATE TRIGGER IF NOT EXISTS `trg_likes_delete` AFTER DELETE ON `actualite_likes`
FOR EACH ROW
BEGIN
  UPDATE `actualites`
  SET `likes_count` = GREATEST(0, `likes_count` - 1)
  WHERE `id` = OLD.actualite_id;
END$$

CREATE TRIGGER IF NOT EXISTS `trg_comments_insert` AFTER INSERT ON `actualite_commentaires`
FOR EACH ROW
BEGIN
  IF NEW.statut = 'approuve' THEN
    UPDATE `actualites`
    SET `comments_count` = `comments_count` + 1
    WHERE `id` = NEW.actualite_id;
  END IF;
END$$

DELIMITER ;

-- ============================================================
-- FIN DE LA FUSION
-- N'oubliez pas d'inclure les INSERTs de données de démo
-- pour les nouvelles tables si nécessaire.
-- ============================================================

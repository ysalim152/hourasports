-- ============================================================
-- association_db.sql — Base de données Association Sportive v2
-- MariaDB / MySQL — Cohérent avec les 5 rôles inscrire.html
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `association_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `association_db`;

-- -----------------------------------------------------------
-- TABLE: roles  (5 rôles correspondant à inscrire.html)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(50)      NOT NULL,
  `label`       VARCHAR(80)      NOT NULL,
  `description` VARCHAR(255)     DEFAULT NULL,
  `couleur`     VARCHAR(7)       DEFAULT '#8d99ae',
  `icone`       VARCHAR(10)      DEFAULT '👤',
  `niveau_acces` TINYINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT '0=visiteur 1=participant 2=adherent 3=coach 4=admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`nom`,`label`,`description`,`couleur`,`icone`,`niveau_acces`) VALUES
(1,'admin',       'Administrateur',    'Accès complet au backoffice',                       '#e63946','🛡️',4),
(2,'coach',       'Coach / Entraîneur','Gère équipes, sessions et présences',               '#f4a261','🎽',3),
(3,'adherent',    'Adhérent',          'Membre actif avec cotisation annuelle',              '#2ecc71','🏅',2),
(4,'participant', 'Participant',        'Inscrit à une ou plusieurs disciplines sportives',  '#3498db','⚽',1),
(5,'visiteur',    'Visiteur',          'Accès lecture seule au site public',                '#8d99ae','👁️',0);

-- -----------------------------------------------------------
-- TABLE: utilisateurs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `nom`             VARCHAR(80)       NOT NULL,
  `prenom`          VARCHAR(80)       NOT NULL,
  `email`           VARCHAR(160)      NOT NULL,
  `mot_de_passe`    VARCHAR(255)      NOT NULL COMMENT 'bcrypt hash',
  `telephone`       VARCHAR(20)       DEFAULT NULL,
  `date_naissance`  DATE              DEFAULT NULL,
  `sexe`            ENUM('M','F','Autre') DEFAULT NULL,
  `avatar`          VARCHAR(255)      DEFAULT NULL,
  `ville`           VARCHAR(100)      DEFAULT NULL,
  `role_id`         TINYINT UNSIGNED  NOT NULL DEFAULT 5
    COMMENT 'FK → roles.id, défaut=visiteur',
  `statut`          ENUM('actif','inactif','suspendu','en_attente') NOT NULL DEFAULT 'actif'
    COMMENT 'en_attente = admin/coach non encore validé',
  `token_reset`     VARCHAR(100)      DEFAULT NULL,
  `token_exp`       DATETIME          DEFAULT NULL,
  `derniere_connexion` DATETIME       DEFAULT NULL,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `fk_utilisateurs_role` (`role_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_utilisateurs_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comptes de démo (mot de passe : Admin@2024)
INSERT INTO `utilisateurs`
  (`nom`,`prenom`,`email`,`mot_de_passe`,`telephone`,`ville`,`role_id`,`statut`) VALUES
('Dupont',  'Admin',  'admin@association.dz', '$2y$12$KIx5sP1uU4P.w1mNzEJO3ulbN0bPiMJZaE8F9eI.3G5iHVIiA1Xuu', '+213 555 100 200','Blida',1,'actif'),
('Martin',  'Sophie', 'coach@association.dz', '$2y$12$KIx5sP1uU4P.w1mNzEJO3ulbN0bPiMJZaE8F9eI.3G5iHVIiA1Xuu', '+213 555 200 300','Blida',2,'actif'),
('Benali',  'Karim',  'karim@email.com',      '$2y$12$KIx5sP1uU4P.w1mNzEJO3ulbN0bPiMJZaE8F9eI.3G5iHVIiA1Xuu', '+213 555 111 222','Blida',4,'actif'),
('Kaddour', 'Leila',  'leila@email.com',      '$2y$12$KIx5sP1uU4P.w1mNzEJO3ulbN0bPiMJZaE8F9eI.3G5iHVIiA1Xuu', '+213 555 333 444','Blida',3,'actif'),
('Hamidi',  'Omar',   'visiteur@email.com',   '$2y$12$KIx5sP1uU4P.w1mNzEJO3ulbN0bPiMJZaE8F9eI.3G5iHVIiA1Xuu', NULL,              'Alger',5,'actif');

-- -----------------------------------------------------------
-- TABLE: codes_invitation  (codes secrets admin/coach)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `codes_invitation` (
  `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`       VARCHAR(30)       NOT NULL,
  `role_id`    TINYINT UNSIGNED  NOT NULL,
  `actif`      TINYINT(1)        NOT NULL DEFAULT 1,
  `usage_max`  SMALLINT UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
  `usage_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `expire_at`  DATETIME          DEFAULT NULL,
  `created_at` TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `fk_codes_role` (`role_id`),
  CONSTRAINT `fk_codes_role`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `codes_invitation` (`code`,`role_id`,`actif`,`usage_max`) VALUES
('ADMIN2025', 1, 1, 5),
('COACH2025', 2, 1, 20);

-- -----------------------------------------------------------
-- TABLE: categories (disciplines)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(80)       NOT NULL,
  `description` TEXT              DEFAULT NULL,
  `icone`       VARCHAR(10)       DEFAULT '🏅',
  `couleur`     VARCHAR(7)        DEFAULT '#e63946',
  `places_max`  SMALLINT UNSIGNED DEFAULT 30,
  `actif`       TINYINT(1)        NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`nom`,`description`,`icone`,`couleur`,`places_max`) VALUES
('Football',    'Sport collectif 11 contre 11',    '⚽','#27ae60',60),
('Basketball',  'Basket-ball 5 contre 5',           '🏀','#f39c12',24),
('Tennis',      'Tennis simple et double',           '🎾','#3498db',30),
('Natation',    'Natation sportive et loisir',       '🏊','#1abc9c',25),
('Athlétisme',  'Courses, sauts et lancers',         '🏃','#e74c3c',40),
('Arts Martiaux','Judo, Karaté, Taekwondo',          '🥋','#9b59b6',50),
('Cyclisme',    'VTT et vélo de route',              '🚴','#e67e22',20),
('Volleyball',  'Volley-ball en salle et plage',     '🏐','#2980b9',20);

-- -----------------------------------------------------------
-- TABLE: equipes
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipes` (
  `id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`           VARCHAR(100)      NOT NULL,
  `categorie_id`  SMALLINT UNSIGNED NOT NULL,
  `coach_id`      INT UNSIGNED      DEFAULT NULL,
  `description`   TEXT              DEFAULT NULL,
  `effectif_max`  TINYINT UNSIGNED  NOT NULL DEFAULT 20,
  `statut`        ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  `annee_creation` YEAR             DEFAULT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipes_categorie` (`categorie_id`),
  KEY `fk_equipes_coach` (`coach_id`),
  CONSTRAINT `fk_equipes_categorie`
    FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_equipes_coach`
    FOREIGN KEY (`coach_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `equipes` (`nom`,`categorie_id`,`coach_id`,`effectif_max`,`annee_creation`) VALUES
('Équipe A Football',  1, 2, 18, 2019),
('Équipe U17 Football',1, 2, 16, 2020),
('Team Basketball',    2, 2, 12, 2021),
('Club Tennis',        3, NULL,30, 2018),
('Natation Seniors',   4, NULL,25, 2017);

-- -----------------------------------------------------------
-- TABLE: profils_coach  (spécifique au rôle coach)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profils_coach` (
  `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED     NOT NULL,
  `diplome`       VARCHAR(80)       DEFAULT NULL,
  `experience_ans` VARCHAR(10)      DEFAULT NULL,
  `bio`           TEXT              DEFAULT NULL,
  `disponibilites` VARCHAR(200)     DEFAULT NULL COMMENT 'JSON array de jours',
  `linkedin`      VARCHAR(255)      DEFAULT NULL,
  `fonction`      VARCHAR(80)       DEFAULT NULL,
  `statut_validation` ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  `valide_par`    INT UNSIGNED      DEFAULT NULL,
  `valide_le`     DATETIME          DEFAULT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coach_user` (`utilisateur_id`),
  CONSTRAINT `fk_profils_coach_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profil coach démo
INSERT INTO `profils_coach` (`utilisateur_id`,`diplome`,`experience_ans`,`bio`,`disponibilites`,`statut_validation`,`valide_le`) VALUES
(2,'bees2','5-10','Coach diplômée d\'État, 8 ans d\'expérience.','["Lundi","Mercredi","Vendredi","Samedi"]','valide',NOW());

-- Lien coach ↔ spécialités (plusieurs disciplines par coach)
CREATE TABLE IF NOT EXISTS `coach_specialites` (
  `coach_id`      INT UNSIGNED      NOT NULL,
  `categorie_id`  SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`coach_id`,`categorie_id`),
  CONSTRAINT `fk_cspec_coach`
    FOREIGN KEY (`coach_id`) REFERENCES `profils_coach` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cspec_cat`
    FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coach_specialites` VALUES (1,1),(1,2);

-- -----------------------------------------------------------
-- TABLE: membres  (rôles adhérent + participant)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `membres` (
  `id`                INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id`    INT UNSIGNED      NOT NULL,
  `equipe_id`         SMALLINT UNSIGNED DEFAULT NULL,
  `numero_licence`    VARCHAR(30)       DEFAULT NULL,
  `date_adhesion`     DATE              NOT NULL,
  `date_renouvellement` DATE            DEFAULT NULL,
  -- Cotisation (adhérent)
  `formule_cotisation` ENUM('mensuel','semestriel','annuel') DEFAULT NULL,
  `cotisation_payee`  ENUM('oui','non','partiel') NOT NULL DEFAULT 'non',
  `montant_cotisation` DECIMAL(8,2)     DEFAULT NULL,
  -- Médical
  `groupe_sanguin`    ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `condition_medicale` VARCHAR(100)     DEFAULT NULL,
  `certificat_medical` VARCHAR(255)     DEFAULT NULL,
  -- Urgence
  `contact_urgence_nom` VARCHAR(100)    DEFAULT NULL,
  `contact_urgence_tel` VARCHAR(20)     DEFAULT NULL,
  -- Misc
  `notes`             TEXT              DEFAULT NULL,
  `created_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_utilisateur` (`utilisateur_id`),
  UNIQUE KEY `uk_licence` (`numero_licence`),
  KEY `fk_membres_equipe` (`equipe_id`),
  KEY `idx_cotisation` (`cotisation_payee`),
  CONSTRAINT `fk_membres_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_membres_equipe`
    FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membre démo (Leila = adhérente, Karim = participant)
INSERT INTO `membres`
  (`utilisateur_id`,`equipe_id`,`numero_licence`,`date_adhesion`,
   `formule_cotisation`,`cotisation_payee`,`montant_cotisation`,`groupe_sanguin`) VALUES
(4, NULL,'LIC-2025-0004','2024-09-01','annuel','oui', 4500.00,'A+'),
(3, 1,   'LIC-2025-0003','2024-09-01', NULL,   'non', NULL,   'O+');

-- -----------------------------------------------------------
-- TABLE: participant_disciplines  (disciplines + niveau par participant)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `participant_disciplines` (
  `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED     NOT NULL,
  `categorie_id`  SMALLINT UNSIGNED NOT NULL,
  `niveau`        ENUM('debutant','intermediaire','avance','competiteur') DEFAULT NULL,
  `date_inscription` DATE           NOT NULL DEFAULT (CURDATE()),
  `statut`        ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_part_disc` (`utilisateur_id`,`categorie_id`),
  KEY `fk_pd_cat` (`categorie_id`),
  CONSTRAINT `fk_pd_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_cat`
    FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `participant_disciplines` (`utilisateur_id`,`categorie_id`,`niveau`) VALUES
(3,1,'intermediaire');

-- -----------------------------------------------------------
-- TABLE: sessions_entrainement
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions_entrainement` (
  `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `titre`        VARCHAR(150)      NOT NULL,
  `equipe_id`    SMALLINT UNSIGNED DEFAULT NULL,
  `coach_id`     INT UNSIGNED      DEFAULT NULL,
  `type`         ENUM('entrainement','match','tournoi','competition','autre') NOT NULL DEFAULT 'entrainement',
  `lieu`         VARCHAR(200)      DEFAULT NULL,
  `date_debut`   DATETIME          NOT NULL,
  `date_fin`     DATETIME          NOT NULL,
  `description`  TEXT              DEFAULT NULL,
  `capacite`     TINYINT UNSIGNED  DEFAULT NULL,
  `statut`       ENUM('planifie','en_cours','termine','annule') NOT NULL DEFAULT 'planifie',
  `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sessions_equipe` (`equipe_id`),
  KEY `fk_sessions_coach` (`coach_id`),
  KEY `idx_sessions_date` (`date_debut`),
  KEY `idx_statut_date` (`statut`,`date_debut`),
  CONSTRAINT `fk_sessions_equipe`
    FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sessions_coach`
    FOREIGN KEY (`coach_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions_entrainement`
  (`titre`,`equipe_id`,`coach_id`,`type`,`lieu`,`date_debut`,`date_fin`,`statut`) VALUES
('Entraînement Football Lundi',   1,2,'entrainement','Terrain Principal',   '2025-05-05 09:00:00','2025-05-05 11:00:00','termine'),
('Match Amical U17',              2,2,'match',        'Stade Municipal',     '2025-05-10 15:00:00','2025-05-10 17:00:00','planifie'),
('Entraînement Basketball',       3,2,'entrainement','Salle Omnisports',    '2025-05-06 14:00:00','2025-05-06 16:00:00','termine'),
('Tournoi Tennis Printemps',      4,NULL,'tournoi',   'Courts Couverts',    '2025-05-12 08:00:00','2025-05-12 18:00:00','planifie'),
('Natation Seniors Mercredi',     5,NULL,'entrainement','Piscine Olympique','2025-05-07 07:00:00','2025-05-07 09:00:00','termine'),
('Entraînement Football Vendredi',1,2,'entrainement','Terrain Principal',   '2025-05-09 09:00:00','2025-05-09 11:00:00','planifie');

-- -----------------------------------------------------------
-- TABLE: presences
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `presences` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id`  INT UNSIGNED NOT NULL,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `statut`      ENUM('present','absent','excuse','retard') NOT NULL DEFAULT 'absent',
  `note`        VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presence` (`session_id`,`utilisateur_id`),
  KEY `fk_presences_user` (`utilisateur_id`),
  CONSTRAINT `fk_presences_session`
    FOREIGN KEY (`session_id`) REFERENCES `sessions_entrainement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_presences_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `presences` (`session_id`,`utilisateur_id`,`statut`) VALUES
(1,3,'present'),(1,4,'absent'),
(3,3,'present'),(3,4,'present'),
(5,4,'excuse');

-- -----------------------------------------------------------
-- TABLE: planning
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `planning` (
  `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `titre`        VARCHAR(150)      NOT NULL,
  `description`  TEXT              DEFAULT NULL,
  `session_id`   INT UNSIGNED      DEFAULT NULL,
  `equipe_id`    SMALLINT UNSIGNED DEFAULT NULL,
  `couleur`      VARCHAR(7)        NOT NULL DEFAULT '#e63946',
  `date_debut`   DATETIME          NOT NULL,
  `date_fin`     DATETIME          NOT NULL,
  `recurrence`   ENUM('aucune','quotidien','hebdomadaire','mensuel') NOT NULL DEFAULT 'aucune',
  `lieu`         VARCHAR(200)      DEFAULT NULL,
  `created_by`   INT UNSIGNED      DEFAULT NULL,
  `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_planning_session` (`session_id`),
  KEY `fk_planning_equipe` (`equipe_id`),
  KEY `idx_planning_date` (`date_debut`),
  CONSTRAINT `fk_planning_session`
    FOREIGN KEY (`session_id`) REFERENCES `sessions_entrainement` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_planning_equipe`
    FOREIGN KEY (`equipe_id`) REFERENCES `equipes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_planning_createur`
    FOREIGN KEY (`created_by`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planning` (`titre`,`equipe_id`,`couleur`,`date_debut`,`date_fin`,`recurrence`,`lieu`,`created_by`) VALUES
('Entraînement Football',    1,'#e63946','2025-05-05 09:00:00','2025-05-05 11:00:00','hebdomadaire','Terrain Principal',1),
('Entraînement Basketball',  3,'#f39c12','2025-05-06 14:00:00','2025-05-06 16:00:00','hebdomadaire','Salle Omnisports', 1),
('Tournoi Tennis Printemps', 4,'#f39c12','2025-05-12 08:00:00','2025-05-12 18:00:00','aucune',       'Courts Couverts',  1),
('Match Amical U17',         2,'#3498db','2025-05-10 15:00:00','2025-05-10 17:00:00','aucune',       'Stade Municipal',  1),
('Assemblée Générale',       NULL,'#9b59b6','2025-05-25 18:00:00','2025-05-25 20:00:00','aucune','Salle de Réunion',1);

-- -----------------------------------------------------------
-- TABLE: actualites (blog)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `actualites` (
  `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `titre`        VARCHAR(200)      NOT NULL,
  `slug`         VARCHAR(220)      NOT NULL,
  `contenu`      LONGTEXT          NOT NULL,
  `extrait`      TEXT              DEFAULT NULL,
  `image`        VARCHAR(255)      DEFAULT NULL,
  `categorie`    VARCHAR(80)       DEFAULT NULL,
  `tags`         VARCHAR(255)      DEFAULT NULL COMMENT 'JSON array',
  `auteur_id`    INT UNSIGNED      DEFAULT NULL,
  `statut`       ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
  `vues`         INT UNSIGNED      NOT NULL DEFAULT 0,
  `published_at` DATETIME          DEFAULT NULL,
  `created_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `fk_actualites_auteur` (`auteur_id`),
  KEY `idx_actualites_statut` (`statut`),
  CONSTRAINT `fk_actualites_auteur`
    FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `actualites` (`titre`,`slug`,`contenu`,`extrait`,`categorie`,`tags`,`auteur_id`,`statut`,`vues`,`published_at`) VALUES
('Victoire Éclatante en Finale Régionale','victoire-finale-regionale',
 '<p>Notre équipe première a remporté la finale régionale de football avec un score de 3-1 contre les champions sortants. Une victoire méritée après des mois d\'entraînement intensif.</p>',
 'Notre équipe première a remporté la finale régionale de football avec un score de 3-1.',
 'Football','["football","victoire","finale"]',1,'publie',342,NOW()),
('Inscriptions Ouvertes Saison 2025-2026','inscriptions-ouvertes-2025',
 '<p>Les inscriptions pour la nouvelle saison sportive sont officiellement ouvertes. Rejoignez-nous dans toutes les disciplines!</p>',
 'Les inscriptions pour la nouvelle saison sont officiellement ouvertes.',
 'Association','["inscription","saison","nouveautés"]',1,'publie',215,NOW()),
('Nouveau Coach pour l\'Équipe Basketball','nouveau-coach-basketball',
 '<p>Nous avons le plaisir d\'accueillir notre nouveau coach professionnel de basketball. Bienvenue dans la famille!</p>',
 'Nous accueillons notre nouveau coach professionnel de basketball.',
 'Basketball','["basketball","coach"]',1,'publie',178,NOW());

-- -----------------------------------------------------------
-- TABLE: contacts (formulaire de contact)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`        VARCHAR(100) NOT NULL,
  `email`      VARCHAR(160) NOT NULL,
  `sujet`      VARCHAR(200) DEFAULT NULL,
  `message`    TEXT         NOT NULL,
  `statut`     ENUM('nouveau','lu','repondu','archive') NOT NULL DEFAULT 'nouveau',
  `reponse`    TEXT         DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contacts` (`nom`,`email`,`sujet`,`message`,`statut`) VALUES
('Benali Karim','karim@email.com','Inscription Basketball','Je souhaite inscrire mon fils à l\'équipe basketball. Quels sont les créneaux ?','nouveau'),
('Hamidi Fatima','fatima.h@gmail.com','Renouvellement Cotisation','Comment procéder pour le renouvellement de ma cotisation ?','nouveau'),
('Meziane Omar','omar@outlook.com','Demande de Partenariat','Représentant d\'une marque sportive locale, je souhaite discuter d\'un partenariat.','lu'),
('Rahmani Sara','sara.r@email.dz','Certificat Médical','J\'ai perdu mon certificat médical. Comment en obtenir un nouveau ?','repondu'),
('Bouzid Ahmed','ahmed.b@gmail.com','Informations Tennis','Je débute au tennis à 35 ans. Proposez-vous des cours pour adultes débutants ?','nouveau');

-- -----------------------------------------------------------
-- TABLE: parametres (configuration système)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parametres` (
  `cle`        VARCHAR(80)  NOT NULL,
  `valeur`     TEXT         DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parametres` (`cle`,`valeur`,`description`) VALUES
('nom_association',     'Association Sportive Club',    'Nom de l\'association'),
('slogan',              'L\'Excellence Sportive Commence Ici', 'Slogan affiché dans le hero'),
('email_contact',       'contact@association.dz',       'Email principal'),
('telephone',           '+213 555 123 456',             'Téléphone'),
('adresse',             '12 Rue du Sport, Blida 09000', 'Adresse physique'),
('saison_courante',     '2024-2025',                    'Saison sportive en cours'),
('inscription_ouverte', '1',                            '1=oui, 0=non'),
('validation_manuelle', '0',                            '1=admin valide manuellement'),
('cotisation_mensuelle','500',                          'DA/mois'),
('cotisation_semestrielle','2500',                      'DA/6 mois'),
('cotisation_annuelle', '4500',                         'DA/an'),
('code_admin',          'ADMIN2025',                    'Code requis pour rôle admin'),
('code_coach',          'COACH2025',                    'Code requis pour rôle coach'),
('facebook',            'https://facebook.com/assoc',   'Page Facebook'),
('instagram',           'https://instagram.com/assoc',  'Page Instagram'),
('description_courte',  'Club sportif multidisciplinaire fondé en 2010.','Description courte');

-- -----------------------------------------------------------
-- TABLE: notifications (alertes in-app)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `type`           VARCHAR(40)  NOT NULL,
  `titre`          VARCHAR(150) NOT NULL,
  `message`        TEXT         DEFAULT NULL,
  `lien`           VARCHAR(255) DEFAULT NULL,
  `lu`             TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`utilisateur_id`),
  KEY `idx_notif_lu` (`lu`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`utilisateur_id`,`type`,`titre`,`message`,`lu`) VALUES
(1,'message_nouveau','Nouveau message','Karim Benali vous a envoyé un message.',0),
(1,'inscription','Nouvelle inscription','Visiteur5 vient de créer un compte.',0),
(2,'session_rappel','Session demain','Entraînement Football prévu demain à 09h.',0),
(3,'cotisation','Cotisation bientôt échue','Votre adhésion expire le 31/08/2025.',0);

-- -----------------------------------------------------------
-- TABLE: audit_log (journal des actions admin)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED DEFAULT NULL,
  `action`         VARCHAR(80)  NOT NULL,
  `table_cible`    VARCHAR(50)  DEFAULT NULL,
  `id_cible`       INT UNSIGNED DEFAULT NULL,
  `details`        TEXT         DEFAULT NULL,
  `ip_address`     VARCHAR(45)  DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`utilisateur_id`),
  KEY `idx_audit_action` (`action`),
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- VUES UTILITAIRES
-- -----------------------------------------------------------
CREATE OR REPLACE VIEW `v_membres_complets` AS
SELECT
  u.id AS utilisateur_id,
  CONCAT(u.prenom,' ',u.nom) AS nom_complet,
  u.email, u.telephone, u.ville, u.statut,
  r.nom AS role, r.label AS role_label,
  m.id AS membre_id, m.numero_licence, m.date_adhesion,
  m.formule_cotisation, m.cotisation_payee,
  m.groupe_sanguin,
  e.nom AS equipe,
  c.nom AS categorie
FROM utilisateurs u
JOIN roles r ON r.id = u.role_id
LEFT JOIN membres m ON m.utilisateur_id = u.id
LEFT JOIN equipes e ON e.id = m.equipe_id
LEFT JOIN categories c ON c.id = e.categorie_id
WHERE u.role_id IN (3,4);

CREATE OR REPLACE VIEW `v_sessions_details` AS
SELECT
  s.id, s.titre, s.type, s.lieu, s.date_debut, s.date_fin,
  s.statut, s.capacite,
  e.nom AS equipe,
  c.nom AS categorie,
  CONCAT(u.prenom,' ',u.nom) AS coach,
  COUNT(DISTINCT p.id) AS nb_presents
FROM sessions_entrainement s
LEFT JOIN equipes e ON e.id = s.equipe_id
LEFT JOIN categories c ON c.id = e.categorie_id
LEFT JOIN utilisateurs u ON u.id = s.coach_id
LEFT JOIN presences p ON p.session_id = s.id AND p.statut = 'present'
GROUP BY s.id;

CREATE OR REPLACE VIEW `v_participants_disciplines` AS
SELECT
  u.id AS utilisateur_id,
  CONCAT(u.prenom,' ',u.nom) AS nom_complet,
  u.email,
  c.nom AS discipline, c.icone,
  pd.niveau, pd.date_inscription, pd.statut,
  m.numero_licence
FROM participant_disciplines pd
JOIN utilisateurs u ON u.id = pd.utilisateur_id
JOIN categories c ON c.id = pd.categorie_id
LEFT JOIN membres m ON m.utilisateur_id = u.id;

CREATE OR REPLACE VIEW `v_stats_dashboard` AS
SELECT
  (SELECT COUNT(*) FROM utilisateurs WHERE statut='actif' AND role_id IN (3,4)) AS membres_actifs,
  (SELECT COUNT(*) FROM equipes WHERE statut='actif') AS equipes_actives,
  (SELECT COUNT(*) FROM sessions_entrainement WHERE statut='planifie') AS sessions_planifiees,
  (SELECT COUNT(*) FROM contacts WHERE statut='nouveau') AS messages_nouveaux,
  (SELECT COUNT(*) FROM membres WHERE cotisation_payee='oui') AS cotisations_payees,
  (SELECT COUNT(*) FROM membres WHERE cotisation_payee='non') AS cotisations_impayees,
  (SELECT COUNT(*) FROM utilisateurs WHERE statut='en_attente') AS comptes_en_attente;

-- -----------------------------------------------------------
-- INDEX SUPPLÉMENTAIRES
-- -----------------------------------------------------------
ALTER TABLE `utilisateurs` ADD INDEX IF NOT EXISTS `idx_role_statut` (`role_id`,`statut`);
ALTER TABLE `membres` ADD INDEX IF NOT EXISTS `idx_adherent_cotis` (`formule_cotisation`,`cotisation_payee`);
ALTER TABLE `presences` ADD INDEX IF NOT EXISTS `idx_statut_presence` (`statut`);
ALTER TABLE `notifications` ADD INDEX IF NOT EXISTS `idx_user_lu` (`utilisateur_id`,`lu`);

COMMIT;

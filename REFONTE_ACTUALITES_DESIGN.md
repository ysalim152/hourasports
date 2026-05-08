# 🏆 REFONTE SYSTÈME D'ACTUALITÉS
## Plan Complet : Design UX/UI + Architecture Full-Stack

---

## 📋 PARTIE 1 : RECOMMANDATIONS FONCTIONNELLES

### 1.1 AFFICHAGE PUBLIC (Côté Utilisateur)

#### Structure des Catégories
```
📰 ACTUALITÉS
├─ 🏆 Résultats (Victoires/résultats de matchs)
├─ 📅 Événements (Tournois, galas, rencontres)
├─ 🏢 Vie du Club (Annonces, changements, infos générales)
└─ 👥 Nominations/Honneurs (Membres du mois, championnats)
```

#### Fonctionnalités d'Engagement
- **❤️ Système de Likes** : Nécessite authentification, permet "j'aime" par utilisateur
- **🔗 Partages Sociaux** : WhatsApp, Facebook, Twitter, copier lien
- **📌 Sauvegarde en favoris** : Articles en liste "À lire plus tard"
- **💬 Commentaires** : (optionnel phase 2)

#### Filtres & Recherche
- Filtre par **catégorie** (Résultats/Événements/Vie du Club)
- Filtre par **sport/discipline** (Football, Basketball, etc.)
- Filtre par **groupe d'âge/catégorie** (Séniors, Juniors, etc.)
- **Recherche textuelle** en temps réel
- **Tri** : Plus récent, Tendance (likes), Trending (vues)

#### Éléments Visuels Obligatoires
| Élément | Description | Affichage |
|---------|-------------|-----------|
| **Miniature/Image** | 600×400px, aspect ratio 3:2 | Coin supérieur gauche de la carte |
| **Titre** | Max 70 caractères | Gras, taille L (1.8rem sur desktop) |
| **Extrait** | Max 150 caractères | Gris clair, taille M |
| **Auteur** | "Par Sophie Martin" | Avatar (32×32px) + nom |
| **Date** | "il y a 2 jours" | Format relatif + date complète au hover |
| **Temps de lecture** | "5 min de lecture" | 📖 Icon + durée estimée |
| **Catégorie Badge** | Couleur selon type | Ex: "🏆 Résultats", "📅 Événements" |
| **Compteur** | ❤️ 42 | 👀 234 vues | 🔗 Partages | Bottom right |
| **Status** | Brouillon/Archivé (admin only) | Badge optionnel |

### 1.2 INTERFACE D'ADMINISTRATION CRUD

#### Tableau de Bord Récapitulatif
```
┌─────────────────────────────────────┐
│  📊 TABLEAU DE BORD - ACTUALITÉS   │
├─────────────────────────────────────┤
│ 📝 Brouillons: 5     │ 📤 Programmés: 2  │
│ ✅ Publiés: 23      │ 🗂️  Archivés: 12   │
│ 👁️ Vues totales: 3,452 │ ❤️ Likes: 854      │
└─────────────────────────────────────┘

┌─ ACTIONS RAPIDES ─────────────────┐
│ [+ Nouvel Article] [📅 Programmés] │
│ [📊 Statistiques] [🗑️ Corbeille]   │
└───────────────────────────────────┘
```

#### Formulaire de Création/Édition (Mobile-First)
```
┌─────────────────────────────────────────┐
│  ✏️ NOUVEL ARTICLE                     │
├─────────────────────────────────────────┤
│                                        │
│ 📸 Image en vedette                    │
│ [Cliquer ou glisser] ⚡ Max 5MB       │
│ ├─ Couverture 1200×630px (preview)   │
│ └─ Thumbnail 600×400px (auto-généré) │
│                                        │
│ ⚙️ CONTENU                             │
│ [Titre:___________________________]   │
│ Max 200 caractères (73 restants)     │
│                                        │
│ [Extrait:________________]             │
│ Suggestion auto: 1ers 150 caractères │
│                                        │
│ 📝 Contenu riche (WYSIWYG)            │
│ ┌──────────────────────────────────┐  │
│ │ B I U   •   ⬜ 🎨   🔗   📷      │  │
│ │                                │  │
│ │ [Contenu éditable...]          │  │
│ │                                │  │
│ └──────────────────────────────────┘  │
│ Temps de lecture: ~4 min              │
│                                        │
│ 🏷️ MÉTADONNÉES                        │
│ Catégorie: [Résultats ▼]             │
│ Tags: [football, victoire, +ajouter] │
│ Sport: [Football ▼]                  │
│ Groupe d'âge: [☑ Séniors ☑ Juniors] │
│                                        │
│ 📅 PROGRAMMATION                      │
│ ☐ Programmer la publication           │
│   [Date: __/__/2026] [Heure: __:__]  │
│                                        │
│ 🔔 NOTIFICATIONS PUSH                 │
│ ☑ Envoyer notification aux followers  │
│   Texte: [Nouvel article...]         │
│                                        │
│ 👁️ STATUT & ACTIONS                    │
│ Statut: [Brouillon ▼]                │
│                                        │
│ [💾 Enregistrer] [👁️ Aperçu]         │
│ [❌ Annuler]     [🔗 Partager]       │
│                                        │
│ [Supprimer] (danger zone)            │
└─────────────────────────────────────────┘
```

#### Tableau Listage (Responsive)
```
🔍 [Rechercher...] [Filtrer ⬇] [+ Créer]

┌─────────────────────────────────────────────────┐
│ ☑ │ Titre             │ Cat. │ État │ Vues │ ⋮  │
├─────────────────────────────────────────────────┤
│ ☑ │ Victoire Finale   │ 🏆  │ ✅  │ 342 │ ⋮  │
│   │ Par Sophie M. | il y a 2j        │ 🔗    │
├─────────────────────────────────────────────────┤
│ ☑ │ Inscriptions 2025 │ 📢  │ ✅  │ 215 │ ⋮  │
│   │ Par Admin | il y a 7j            │ 🔗    │
├─────────────────────────────────────────────────┤
│ ☑ │ Nouveau Coach     │ 👥  │ 📝  │  -  │ ⋮  │
│   │ Par Sophie M. | Programmé        │ 🔗    │
└─────────────────────────────────────────────────┘

Menu contextuel (⋮):
├─ ✏️ Éditer
├─ 👁️ Voir
├─ 📊 Stats détaillées
├─ 📅 Reprogrammer
├─ 📌 Épingler/Dépingler
├─ 🔄 Dupliquer
└─ 🗑️ Supprimer
```

---

## 🎨 PARTIE 2 : WIREFRAMES TEXTUELS

### 2.1 PAGE PUBLIQUE D'ACTUALITÉS (Mobile-First)

#### Version Mobile (375px)
```
┌──────────────────────┐
│ 📰 ACTUALITÉS      │
│ ┌──────────────────┐ │
│ │ 🏆 Résultats   │ │
│ │ 📅 Événements  │ │
│ │ 🏢 Vie du Club │ │
│ │ 👥 Honneurs    │ │
│ └──────────────────┘ │
│ 🔍 [Rechercher...]  │
│                    │
│ ┌──────────────────┐ │
│ │  ╭────────────╮  │ │
│ │  │ [PHOTO 3:2]│  │ │
│ │  ├────────────┤  │ │
│ │  │ 🏆 RÉSULTAT│  │ │
│ │  │            │  │ │
│ │  │ Victoire   │  │ │
│ │  │ Éclatante  │  │ │
│ │  │            │  │ │
│ │  │ Notre équi │  │ │
│ │  │ pe a...    │  │ │
│ │  │            │  │ │
│ │  │ 👤 Sophie  │  │ │
│ │  │ 📖 5 min   │  │ │
│ │  │ il y a 2j  │  │ │
│ │  ├────────────┤  │ │
│ │  │❤️42 👁️234 │  │ │
│ │  │ 🔗  Lire   │  │ │
│ │  ╰────────────╯  │ │
│ └──────────────────┘ │
│ [Charger plus...]    │
└──────────────────────┘
```

#### Version Tablette (768px)
```
┌─────────────────────────────────────────┐
│   🏆 ACTUALITÉS - LES DERNIÈRES ACTU    │
├────────┬────────┬────────┬──────────────┤
│ 🏆 Rés │ 📅 Evé │ 🏢 Vie │ 🔍 Rech     │
├────────┴────────┴────────┴──────────────┤
│                                        │
│  Trier: [▼ Récent] [Tendance] [Viral]  │
│                                        │
│ ┌──────────────────┐  ┌──────────────┐ │
│ │  ╭────────────╮  │  │ 📅 ÉVÉNEMENT │ │
│ │  │  [PHOTO]   │  │  │              │ │
│ │  ├────────────┤  │  │ Tournoi      │ │
│ │  │ 🏆 RÉSULTAT│  │  │ Inter...     │ │
│ │  │ Victoire...│  │  │              │ │
│ │  │ Sophie Mar │  │  │ By Admin     │ │
│ │  │ 📖 5 min   │  │  │ 📖 3 min     │ │
│ │  │ il y a 2j  │  │  │ il y a 1j    │ │
│ │  ├────────────┤  │  │ ❤️8 👁️67   │ │
│ │  │❤️42 👁️234 │  │  │ [Lire]       │ │
│ │  ╰────────────╯  │  └──────────────┘ │
│ └──────────────────┘                    │
│                                        │
│ ┌──────────────────┐  ┌──────────────┐ │
│ │ 🏢 VIE DU CLUB   │  │ 👥 HONNEURS  │ │
│ │                  │  │              │ │
│ │ Inscriptions     │  │ Membre du    │ │
│ │ Ouvertes...      │  │ mois: Karim  │ │
│ │                  │  │              │ │
│ └──────────────────┘  └──────────────┘ │
└────────────────────────────────────────┘
```

#### Version Desktop (1440px)
```
┌────────────────────────────────────────────────────────────────┐
│ ACTUALITÉS  🏆 Résultats  📅 Événements  🏢 Vie du Club  🔍   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Affichage: [Grille ▼]  Trier: [Récent ▼] [Filtrer ⬇]        │
│                                                                │
│ ┌──────────────────┐  ┌──────────────────┐  ┌─────────────┐  │
│ │    ╭────────╮   │  │    ╭────────╮   │  │ ╭────────╮  │  │
│ │    │ PHOTO  │   │  │    │ PHOTO  │   │  │ │ PHOTO  │  │  │
│ │    ├────────┤   │  │    ├────────┤   │  │ ├────────┤  │  │
│ │    │ 🏆     │   │  │    │ 📅     │   │  │ │ 🏢     │  │  │
│ │    │        │   │  │    │        │   │  │ │        │  │  │
│ │    │Victoire│   │  │    │Tournoi │   │  │ │Inscr.  │  │  │
│ │    │        │   │  │    │        │   │  │ │        │  │  │
│ │    │Sophie  │   │  │    │Admin   │   │  │ │Admin   │  │  │
│ │    │📖5min  │   │  │    │📖3min  │   │  │ │📖2min  │  │  │
│ │    │il y a  │   │  │    │il y a  │   │  │ │il y a  │  │  │
│ │    │2j      │   │  │    │1j      │   │  │ │7j      │  │  │
│ │    ├────────┤   │  │    ├────────┤   │  │ ├────────┤  │  │
│ │    │❤️42    │   │  │    │❤️8     │   │  │ │❤️23    │  │  │
│ │    ├────────┤   │  │    ├────────┤   │  │ ├────────┤  │  │
│ │    │[Lire]  │   │  │    │[Lire]  │   │  │ │[Lire]  │  │  │
│ │    ╰────────╯   │  │    ╰────────╯   │  │ ╰────────╯  │  │
│ │ 👁️ 234 vues    │  │ 👁️ 67 vues    │  │ 👁️ 189 vues │  │
│ └──────────────────┘  └──────────────────┘  └─────────────┘  │
│                                                                │
│ [Charger plus articles...]                                   │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### 2.2 INTERFACE D'ADMINISTRATION

#### Dashboard Admin (Vue d'ensemble)
```
┌──────────────────────────────────────────────────────┐
│  ADMIN > ACTUALITÉS                     [👤 Profile]│
├──────────────────────────────────────────────────────┤
│                                                      │
│  📊 TABLEAUX DE BORD                                │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌──────────┐ │
│  │  5     │  │   23   │  │   12   │  │ 3,452    │ │
│  │📝Brouil│  │✅Publi │  │🗂️ Arch │  │👁️ Vues   │ │
│  └────────┘  └────────┘  └────────┘  └──────────┘ │
│                                                      │
│  ┌──────────────────┐  ┌───────────────────────┐  │
│  │ ❤️ Likes: 854    │  │ 🔔 Notif: 3 en attente│  │
│  │ 🔗 Partages: 324 │  │ ⚠️ Articles expirant  │  │
│  └──────────────────┘  └───────────────────────┘  │
│                                                      │
│  [+ NOUVEL ARTICLE] [📅 PROGRAMMÉS] [📊 STATS]    │
│                                                      │
│  📰 ARTICLES RÉCENTS                               │
│  ┌──────────────────────────────────────────────┐  │
│  │ [Rechercher...] [Filtre ⬇]  [Export CSV]   │  │
│  ├──────────────────────────────────────────────┤  │
│  │ ☑ Titre          │ Auteur   │ État │ Vues   │  │
│  ├──────────────────────────────────────────────┤  │
│  │ ☑ Victoire...    │ Sophie   │ ✅  │ 342   │  │
│  │ ☑ Inscriptions.. │ Admin    │ ✅  │ 215   │  │
│  │ ☑ Nouveau Coach. │ Sophie   │ 📝  │  -    │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
│  📌 ARTICLES ÉPINGLÉS (Vedette)                    │
│  ┌─────────────────┬─────────────────────────┐    │
│  │ [IMAGE] Victoi. │ ⭐⭐⭐⭐⭐ 342 vues    │    │
│  │                 │ Pinné jusqu'au 31/05   │    │
│  │ [Dépingler]     │ [Modifier] [+ Ajouter]│    │
│  └─────────────────┴─────────────────────────┘    │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## 🗄️ PARTIE 3 : SCHÉMA DE BASE DE DONNÉES

### 3.1 MODIFICATIONS & EXTENSIONS TABLE `actualites`

#### Champs à AJOUTER
```sql
ALTER TABLE `actualites` ADD COLUMN (
  -- Programmation
  `scheduled_at`      DATETIME          DEFAULT NULL
    COMMENT 'Publication programmée',
  
  -- Images optimisées
  `image_thumbnail`   VARCHAR(255)      DEFAULT NULL
    COMMENT 'Thumbnail 600×400px auto-généré',
  `image_webp`        VARCHAR(255)      DEFAULT NULL
    COMMENT 'Version WebP optimisée',
  
  -- Engagement
  `likes_count`       INT UNSIGNED      DEFAULT 0,
  `comments_count`    INT UNSIGNED      DEFAULT 0,
  `shares_count`      INT UNSIGNED      DEFAULT 0,
  
  -- Métadonnées
  `reading_time`      TINYINT UNSIGNED  DEFAULT 5
    COMMENT 'Minutes estimées',
  `is_featured`       TINYINT(1)        DEFAULT 0
    COMMENT 'Article en vedette/épinglé',
  `featured_until`    DATETIME          DEFAULT NULL
    COMMENT 'Date d\'expiration de l\'épinglage',
  
  -- Sport & Catégories
  `sport_id`          TINYINT UNSIGNED  DEFAULT NULL
    COMMENT 'FK → sports.id',
  `age_group`         VARCHAR(50)       DEFAULT NULL
    COMMENT 'JSON: ["seniors","juniors","enfants"]',
  
  -- Notifications
  `notif_sent`        TINYINT(1)        DEFAULT 0
    COMMENT 'Notification push envoyée?',
  `notif_sent_at`     DATETIME          DEFAULT NULL,
  
  -- Métadonnées SEO (bonus)
  `meta_description`  VARCHAR(160)      DEFAULT NULL,
  `meta_keywords`     VARCHAR(200)      DEFAULT NULL
);

-- Ajouter les INDEX manquants
ALTER TABLE `actualites` ADD INDEX `idx_scheduled_at` (`scheduled_at`);
ALTER TABLE `actualites` ADD INDEX `idx_featured` (`is_featured`, `featured_until`);
ALTER TABLE `actualites` ADD INDEX `idx_sport` (`sport_id`);
ALTER TABLE `actualites` ADD CONSTRAINT `fk_actualites_sport`
  FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE SET NULL;
```

### 3.2 NOUVELLE TABLE : `actualite_likes`

```sql
CREATE TABLE IF NOT EXISTS `actualite_likes` (
  `id`            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `actualite_id`  INT UNSIGNED      NOT NULL,
  `user_id`       INT UNSIGNED      NOT NULL,
  `created_at`    TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_article` (`actualite_id`, `user_id`),
  KEY `fk_likes_actualite` (`actualite_id`),
  KEY `fk_likes_user` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  
  CONSTRAINT `fk_likes_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT 'Suivi des likes utilisateurs sur les articles';
```

### 3.3 NOUVELLE TABLE : `actualite_commentaires` (optionnel phase 2)

```sql
CREATE TABLE IF NOT EXISTS `actualite_commentaires` (
  `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `actualite_id`    INT UNSIGNED      NOT NULL,
  `user_id`         INT UNSIGNED      NOT NULL,
  `comment_parent_id` INT UNSIGNED    DEFAULT NULL
    COMMENT 'Pour les réponses à des commentaires',
  `contenu`         TEXT              NOT NULL,
  `statut`          ENUM('en_attente','approuve','rejete') 
                    DEFAULT 'en_attente',
  `signales_count`  TINYINT UNSIGNED  DEFAULT 0,
  `created_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `fk_commentaires_actualite` (`actualite_id`),
  KEY `fk_commentaires_user` (`user_id`),
  KEY `fk_commentaires_parent` (`comment_parent_id`),
  KEY `idx_statut` (`statut`),
  
  CONSTRAINT `fk_commentaires_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_commentaires_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_commentaires_parent`
    FOREIGN KEY (`comment_parent_id`) REFERENCES `actualite_commentaires` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.4 NOUVELLE TABLE : `actualite_saves` (Favoris utilisateur)

```sql
CREATE TABLE IF NOT EXISTS `actualite_saves` (
  `id`            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `actualite_id`  INT UNSIGNED      NOT NULL,
  `user_id`       INT UNSIGNED      NOT NULL,
  `saved_at`      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_saved` (`actualite_id`, `user_id`),
  KEY `fk_saves_user` (`user_id`),
  
  CONSTRAINT `fk_saves_actualite`
    FOREIGN KEY (`actualite_id`) REFERENCES `actualites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saves_user`
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.5 TABLE `sports` (Référentiel - si n'existe pas)

```sql
CREATE TABLE IF NOT EXISTS `sports` (
  `id`          TINYINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(50)       NOT NULL,
  `slug`        VARCHAR(50)       NOT NULL,
  `icone`       VARCHAR(5)        DEFAULT '⚽',
  `couleur`     VARCHAR(7)        DEFAULT '#e63946',
  `description` VARCHAR(200)      DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sports` (`nom`,`slug`,`icone`,`couleur`) VALUES
('Football','football','⚽','#e63946'),
('Basketball','basketball','🏀','#f4a261'),
('Volleyball','volleyball','🏐','#2ecc71'),
('Handball','handball','🤾','#3498db'),
('Natation','natation','🏊','#9b59b6'),
('Athlétisme','athletisme','🏃','#e74c3c'),
('Judo','judo','🥋','#34495e'),
('Tennis','tennis','🎾','#f1c40f');
```

### 3.6 SCHÉMA VISUEL SIMPLIFIÉ

```
┌────────────────────────────────────────┐
│         UTILISATEURS                   │
│ ┌──────────────────────────────────┐  │
│ │ id | nom | email | role_id       │  │
│ └──────────────────────────────────┘  │
│              ▲                         │
│              │                         │
│   ┌──────────┼──────────┬────────┐    │
│   │          │          │        │    │
│   ▼          ▼          ▼        ▼    │
│ [FK:auteur] [FK:user]  [FK:user] [FK:user]
│   │          │          │        │    │
│   │          │          │        │    │
│   ▼          ▼          ▼        ▼    │
│ ACTUALITÉS  LIKES     COMMENTS  SAVES │
│ ┌────────────────┐  ┌──────────┐     │
│ │ id   │ titre   │  │ user_id  │     │
│ │ slug │ contenu │  │ actualité│     │
│ │ cat  │ image   │  │ created  │     │
│ │status│featured │  └──────────┘     │
│ │vues  │ likes_c │                   │
│ │      │ sched.. │                   │
│ └────────────────┘                   │
│        │ (FK)                         │
│        ▼                              │
│      SPORTS                           │
│   (référentiel)                       │
└────────────────────────────────────────┘
```

---

## 🔧 PARTIE 4 : IMPLÉMENTATION TECHNOLOGIQUE

### 4.1 STACK RECOMMANDÉ

#### Frontend - Affichage Utilisateur
```
📦 Dépendances suggérées :
├─ TinyMCE 7.x ou Quill 2.x (Éditeur WYSIWYG)
├─ Axios (requêtes API)
├─ Moment.js ou date-fns (dates relatives)
├─ Image Compression JS (compression client)
├─ Lightbox.js (modal galerie images)
├─ Slugify.js (génération slug auto)
└─ PushJS (notifications web)
```

#### Backend - API & Admin
```
🔐 Architecture PHP :
├─ ✅ PDO pour requêtes préparées
├─ ✅ JWT ou Session pour authentification
├─ Upload: Intervention Image (redimensionnement)
├─ WYSIWYG: Sanitize HTML avec HTML Purifier
├─ Scheduleur: Cronjob ou tâche système
└─ Images: WebP + JPEG + Responsive srcset
```

### 4.2 ENDPOINTS API À CRÉER

```
📨 ACTUALITÉS - ENDPOINTS

GET    /api/actualites.php?filter=recent&limit=10&page=1
  → Liste paginée avec filtres

GET    /api/actualites.php?id=X&include=likes,comments
  → Article détaillé + engagement

GET    /api/actualites.php?sport=football&category=resultats
  → Filtrage par sport et catégorie

GET    /api/actualites.php?featured=1
  → Articles en vedette

POST   /api/actualites.php (coach+)
  → Créer article (+ upload image)

PUT    /api/actualites.php?id=X (coach+)
  → Modifier article

DELETE /api/actualites.php?id=X (admin)
  → Supprimer

PUT    /api/actualites.php?action=publish&id=X
  → Publier/Dépublier

PUT    /api/actualites.php?action=feature&id=X&until=DATE
  → Épingler article

🔗 ENGAGEMENT

POST   /api/likes.php?article_id=X (connecté)
  → Aimer/Contraimer article

GET    /api/likes.php?article_id=X
  → Liste utilisateurs ayant aimé

POST   /api/saves.php?article_id=X (connecté)
  → Ajouter en favoris

GET    /api/saves.php (connecté)
  → Mes articles sauvegardés

📸 UPLOAD & IMAGES

POST   /api/upload-image.php (coach+)
  → Upload image, retourne URL + thumbnails

POST   /api/bulk-upload.php
  → Upload multiple

🔔 NOTIFICATIONS

POST   /api/notifications/send-push.php (admin)
  → Envoyer notification push article

GET    /api/notifications.php?article_id=X
  → Statut envoi notifications
```

### 4.3 OPTIMISATIONS PERFORMANCE

| Optimisation | Implémentation |
|-------------|-----------------|
| **Lazy Load Images** | `loading="lazy"` + Intersection Observer |
| **Image Optimization** | WebP/JPEG, srcset responsive, compression |
| **API Caching** | Redis ou fichier JSON (articles populaires) |
| **DB Queries** | Indexes sur `published_at`, `is_featured`, `sport_id` |
| **Pagination** | Limite par défaut 12 articles |
| **Minification** | CSS/JS minifiés en production |
| **Service Worker** | Cache offline pour articles déjà lus |

---

## 🎨 PARTIE 5 : DIRECTIVES DESIGN SPORTIF

### 5.1 PALETTE COULEURS
```
🔴 Rouge/Primaire: #e63946  (Victoires, Urgence)
🟠 Orange/Accent: #f4a261   (Événements, CTAs)
🟢 Vert/Succès: #2ecc71     (Articles publiés, Achevé)
🟡 Jaune/Attention: #f39c12  (Programmés, À vérifier)
⚫ Gris/Neutre: #8d99ae      (Texte secondaire)
🔵 Bleu foncé/Fond: #0d1b2a (Contraste maximal)

Dégradés Héroïques:
gradient(135deg, #0d1b2a 0%, #1a2a3a 50%, #e63946 100%)
```

### 5.2 TYPOGRAPHIE
```
Titres (Display): Bebas Neue, Impact (Majuscules gras)
  Font-weight: 700
  Letter-spacing: 0.05em
  
Corps (Body): Rajdhani, Segoe UI (Géométrique, moderne)
  Font-weight: 400-600
  Line-height: 1.6
  
Tailles:
  H1 (Hero): 3.2rem / 2rem (mobile)
  H2 (Section): 2.4rem / 1.8rem
  H3 (Sous-titre): 1.8rem / 1.4rem
  Body: 1rem / 0.95rem
  Small: 0.85rem / 0.8rem
```

### 5.3 ÉLÉMENTS VISUELS SPORTIFS
```
Icônes:
  ✅ Publiés     →  Checkmark vert
  📝 Brouillon   →  Crayon gris
  🗂️ Archivé      →  Boîte grise
  ⭐ Épinglé     →  Star or
  🔥 Trending    →  Flamme rouge
  ⚡ Urgent      →  Éclair
  
Badges Sportifs:
  [🏆 RÉSULTAT]  Fond rouge
  [📅 ÉVÉNEMENT] Fond orange
  [🏢 VIE CLUB]  Fond bleu
  [👥 HONNEURS]  Fond vert

Animations:
  - Hover carte: Élévation (+4px shadow), Teinte primaire
  - Like button: Bounce + Rougeissement
  - Compteur vues: Ticker smooth
  - Images: Fade-in 0.3s ease-out
```

### 5.4 MICROINTERACTIONS
```
Sur LIKE:
  1. ❤️ s'agrandit (120%) avec animation spring
  2. Compteur +1 smooth (0.2s)
  3. Confettis mineurs autour bouton
  4. Feedback haptic si mobile

Sur SHARE:
  1. Bouton copie url : "📋 Copié!" (2s)
  2. Menu partage : slide-up from bottom (mobile)

Sur HOVER TITRE:
  1. Underline animé (0.3s)
  2. Couleur passe au primaire
  3. Cursor pointer

Sur FILTRE:
  1. Cartes disparaissent (fade-out 0.2s)
  2. Nouvelles cartes apparaissent (fade-in 0.3s)
  3. Compteur résultats animé
```

---

## 📋 LISTE CHAMPS DB - RÉSUMÉ COMPLET

### Modifications Table Existante `actualites`
```
✅ EXISTANTS (à garder)     | ➕ À AJOUTER              | ❌ À MODIFIER
─────────────────────────────────────────────────────────────────────
id                         | scheduled_at              | Aucun
titre                      | image_thumbnail           |
slug                       | image_webp                |
contenu                    | likes_count               |
extrait                    | comments_count            |
image                      | shares_count              |
categorie                  | reading_time              |
tags                       | is_featured               |
auteur_id                  | featured_until            |
statut                     | sport_id (FK)             |
vues                       | age_group (JSON)          |
published_at               | notif_sent                |
created_at                 | notif_sent_at             |
updated_at                 | meta_description (SEO)    |
                          | meta_keywords (SEO)       |
```

### Nouvelles Tables
```
ACTUALITE_LIKES           ACTUALITE_COMMENTAIRES    ACTUALITE_SAVES
├─ id                     ├─ id                      ├─ id
├─ actualite_id (FK)      ├─ actualite_id (FK)       ├─ actualite_id (FK)
├─ user_id (FK)           ├─ user_id (FK)            ├─ user_id (FK)
└─ created_at             ├─ comment_parent_id       └─ saved_at
                          ├─ contenu
SPORTS (Référentiel)      ├─ statut
├─ id                     ├─ signales_count
├─ nom                    ├─ created_at
├─ slug                   └─ updated_at
├─ icone
├─ couleur
└─ description
```

---

## 🚀 PHASE DE DÉPLOIEMENT RECOMMANDÉE

### Phase 1 : Fondations (Semaine 1-2)
- ✅ Mise à jour schéma DB (ALTER + nouvelles tables)
- ✅ API CRUD complète (endpoints listés)
- ✅ Upload/redimensionnement images

### Phase 2 : Frontend Utilisateur (Semaine 2-3)
- ✅ Affichage cartes modernes
- ✅ Système filtres/recherche
- ✅ Like/save (engagement)

### Phase 3 : Admin (Semaine 3-4)
- ✅ Éditeur WYSIWYG
- ✅ Dashboard CRUD
- ✅ Programmation publication

### Phase 4 : Notifications (Semaine 4-5)
- ✅ Push notifications
- ✅ Partages sociaux
- ✅ Tests utilisateurs

---

## 📱 CHECKLIST MOBILE-FIRST

- [ ] Cartes 100% de la largeur (margin gutter 1rem)
- [ ] Boutons min 44px x 44px (accessibility)
- [ ] Tap targets espacées min 8px
- [ ] Images responsive avec srcset
- [ ] Menu filtres : sticky en haut
- [ ] Lazy-loading images au scroll
- [ ] Zéro scroll horizontal
- [ ] Texte min 16px (lisibilité)
- [ ] Contraste WCAG AA minimum (#fff sur #e63946 OK)
- [ ] Test sur iPhone 12 mini + Android 5.0 min

---

**Document généré pour refonte complète système d'actualités**
**Version: 1.0 | Dernière mise à jour: Mai 2026**

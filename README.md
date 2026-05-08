# 🏆 AppAss v3 — Application de Gestion d'Association Sportive

> Stack : **HTML5 · CSS3 · JavaScript Vanilla · PHP 8 · MariaDB**  
> 44 fichiers · 10 866 lignes · 100% responsive · Architecture MVC-like

---

## 📋 Résumé rapide

| Catégorie         | Détail                                                            |
|-------------------|-------------------------------------------------------------------|
| Pages publiques   | 6 (accueil, activités, à propos, contact, espace membre, 404)    |
| Pages auth        | 2 (connexion, inscription 5 rôles)                               |
| Pages admin       | 11 (dashboard, membres, équipes, sessions, présences, planning, actualités, messages, rapports, profil, paramètres) |
| APIs PHP          | 13 endpoints REST                                                 |
| Tables MariaDB    | 17 tables + 4 vues SQL                                           |
| Rôles utilisateurs| 5 (admin, coach, adhérent, participant, visiteur)                 |

---

## 📁 Structure

```
AppAss/
├── .htaccess                    Sécurité Apache, 404, cache, CORS
├── index.html                   Redirection → public/blog.html
│
├── public/
│   ├── blog.html                Accueil + actualités live
│   ├── activites.html           8 disciplines + filtres + horaires
│   ├── apropos.html             Histoire + équipe + partenaires
│   ├── contact.html             Formulaire AJAX + FAQ
│   ├── espace-membre.html       Dashboard membre connecté
│   ├── 404.html                 Page erreur personnalisée
│   │
│   ├── auth/
│   │   ├── login.html           Connexion (5 rôles, fallback démo)
│   │   ├── inscrire.html        Inscription 5 rôles × 5 étapes
│   │   └── logout.php
│   │
│   └── admin/
│       ├── dashboard.html       KPIs + graphiques + activité live
│       ├── membres.html         CRUD + filtres + pagination + CSV
│       ├── equipes.html         CRUD + vue cartes/tableau
│       ├── sessions.html        CRUD + statut rapide
│       ├── presences.html       Feuille émargement + heatmap
│       ├── planning.html        Calendrier Mois/Semaine/Liste
│       ├── actualites.html      Blog WYSIWYG + tags + aperçu
│       ├── messages.html        Messagerie contact + réponse
│       ├── rapport.html         Statistiques + exports CSV
│       ├── profil.html          Profil + sécurité + sessions actives
│       └── parametres.html      Configuration système complète
│
├── api/                         13 REST APIs (JSON)
│   ├── login.php                Connexion → redirect selon rôle
│   ├── register.php             Inscription 5 rôles + codes secrets
│   ├── contact.php              Contact public + gestion admin
│   ├── membres.php              CRUD membres (adhérents + participants)
│   ├── equipes.php              CRUD équipes + spécialités coach
│   ├── sessions.php             CRUD sessions d'entraînement
│   ├── presences.php            CRUD + bulk save présences
│   ├── planning.php             CRUD événements calendrier
│   ├── actualites.php           CRUD blog + publish toggle
│   ├── profil.php               GET/PUT profil + mot de passe
│   ├── dashboard.php            Stats agrégées par section
│   ├── notifications.php        Notifications in-app
│   └── parametres.php           Configuration système
│
├── config/
│   └── db.php                   PDO singleton + helpers SQL (dbQuery, dbFetchOne, dbInsert, dbUpdate, dbDelete, dbCount, dbPaginate, getParam)
│
├── includes/
│   ├── auth_check.php           requireAuth(), requireRole(), loginUser(), csrfToken(), hasAccess(), currentUser()
│   ├── admin_sidebar.php        Sidebar PHP réutilisable
│   ├── header.php               En-tête HTML partagé
│   └── footer.php               Pied de page HTML partagé
│
├── assets/
│   ├── css/style.css            Design system complet (variables, composants, responsive)
│   └── js/app.js                Toast, Modal, API, Auth, AdminNotif, Export, DataTable
│
└── database/
    └── association_db.sql       17 tables + 4 vues + 16 jeux de données démo
```

---

## ⚙️ Installation

```bash
# 1. Déployer
cp -r AppAss/ /var/www/html/

# 2. Base de données
mysql -u root -p < AppAss/database/association_db.sql

# 3. Configurer
nano AppAss/config/db.php
#  → define('DB_USER', 'votre_user');
#  → define('DB_PASS', 'votre_mot_de_passe');

# 4. Permissions
chmod -R 755 AppAss/
mkdir -p AppAss/logs AppAss/assets/img/uploads
chmod 775 AppAss/logs AppAss/assets/img/uploads

# 5. Apache — activer mod_rewrite
a2enmod rewrite
# Vérifier que AllowOverride All est activé pour le vhost

# 6. Accéder
http://localhost/AppAss/
```

---

## 🔑 Comptes de démonstration

| Rôle         | Email                   | Mot de passe | Redirection après login       |
|--------------|-------------------------|--------------|-------------------------------|
| Admin 🛡️     | admin@association.dz    | Admin@2024   | `/admin/dashboard.html`       |
| Coach 🎽     | coach@association.dz    | Admin@2024   | `/admin/dashboard.html`       |
| Adhérent 🏅  | leila@email.com         | Admin@2024   | `/espace-membre.html`         |
| Participant ⚽| karim@email.com         | Admin@2024   | `/espace-membre.html`         |
| Visiteur 👁️  | visiteur@email.com      | Admin@2024   | `/blog.html`                  |

> ✅ **Mode démo** : Sans serveur PHP, tous les comptes ci-dessus fonctionnent via un fallback JS.

---

## 👥 Rôles & Inscription

| Rôle        | Code requis | Statut initial | Spécificités inscription          |
|-------------|-------------|----------------|-----------------------------------|
| Admin 🛡️    | `ADMIN2025` | `en_attente`   | Validation manuelle 48h + fonction |
| Coach 🎽    | `COACH2025` | `actif`        | Spécialités + diplôme + disponibilités |
| Adhérent 🏅 | —           | `actif`        | Formule cotisation + infos médicales |
| Participant ⚽| —          | `actif`        | Disciplines + niveau par sport |
| Visiteur 👁️ | —           | `actif`        | Source + intérêt (facultatif) |

---

## 🗄️ Base de données

| Table                     | Description                                      |
|---------------------------|--------------------------------------------------|
| `roles`                   | 5 rôles avec niveau_acces (0→4)                 |
| `utilisateurs`            | Tous les comptes + statut                        |
| `codes_invitation`        | Codes admin/coach avec compteur d'usages         |
| `categories`              | 8 disciplines sportives                          |
| `equipes`                 | Équipes par discipline                           |
| `profils_coach`           | Diplôme, expérience, bio, disponibilités         |
| `coach_specialites`       | Coach ↔ disciplines (N:N)                       |
| `membres`                 | Cotisation, urgence, certificat médical          |
| `participant_disciplines` | Discipline + niveau par participant              |
| `sessions_entrainement`   | Sessions avec coach, lieu, capacité              |
| `presences`               | present/absent/excuse/retard par session         |
| `planning`                | Calendrier avec récurrence                       |
| `actualites`              | Blog avec slug, tags JSON, statut                |
| `contacts`                | Messages formulaire + réponse admin              |
| `parametres`              | Configuration clé-valeur                         |
| `notifications`           | Alertes in-app                                   |
| `audit_log`               | Journal complet des actions                      |

---

## 🛠️ Endpoints API

```
# Auth (publics)
POST /api/login.php                   Connexion
POST /api/register.php                Inscription (5 rôles)

# Contact (public)  
POST /api/contact.php                 Envoi formulaire

# Admin (auth requise)
GET|PUT /api/profil.php               Profil utilisateur
GET|PUT /api/profil.php?action=password  Changement mot de passe

GET|POST|PUT|DELETE /api/membres.php  CRUD membres
GET|POST|PUT|DELETE /api/equipes.php  CRUD équipes
GET|POST|PUT|DELETE /api/sessions.php CRUD sessions
GET|POST|PUT|DELETE /api/presences.php CRUD + bulk
GET|POST|PUT|DELETE /api/planning.php CRUD planning
GET|POST|PUT|DELETE /api/actualites.php CRUD blog
GET|PUT|DELETE /api/contact.php       Gestion messages
GET|PUT|DELETE /api/notifications.php Notifications
GET|PUT /api/parametres.php           Configuration

GET /api/dashboard.php                Stats dashboard
GET /api/dashboard.php?section=kpi|cotisations|disciplines|sessions|activite|roles
```

---

## 🎨 Design System

**Couleurs :**
```css
--primary: #e63946   /* rouge sport  */
--secondary: #1d3557 /* bleu nuit    */
--dark: #0d1b2a      /* fond sombre  */
--success: #2ecc71   --warning: #f39c12   --danger: #e74c3c
```
**Rôles :** admin=#e63946 · coach=#f4a261 · adhérent=#2ecc71 · participant=#3498db · visiteur=#8d99ae

**JS globaux (`app.js`) :**
```js
Toast.success/error/warning/info(msg)    // Notifications
Modal.open(id) / Modal.close(id)         // Modales animées
confirmAction(msg, callback)             // Dialogue confirmation
API.get/post/put/delete(url, data)       // Fetch helper
Auth.save/get/role/niveau/isLoggedIn()   // Session côté client
AdminNotif.render()                      // Notifications admin
animateCounter(el, target)               // Compteurs animés
Export.csv(data, filename)               // Export CSV
DataTable(id, options)                   // Tableaux paginés
```

---

## 🔒 Sécurité

| Mesure                   | Détail                                              |
|--------------------------|-----------------------------------------------------|
| Mots de passe            | bcrypt hash (cost=12)                               |
| Sessions PHP             | httponly · samesite=Lax · strict_mode               |
| SQL Injection            | PDO préparé sur toutes les requêtes                 |
| CSRF                     | Token session sur formulaires sensibles             |
| Rate limiting            | Contact : 1 message / 5 min par IP                 |
| Codes secrets            | Admin et Coach nécessitent un code                  |
| Validation manuelle      | Comptes admin en statut `en_attente`                |
| Audit trail              | audit_log sur toutes les actions admin              |
| Headers HTTP             | X-Frame-Options, X-Content-Type, XSS-Protection     |
| Fichiers sensibles       | config/ et includes/ bloqués via .htaccess          |
| Vérification rôle        | Côté serveur sur chaque API                         |

---

## 📊 Statistiques finales

| Métrique          | Valeur |
|-------------------|--------|
| Fichiers          | 44     |
| Lignes HTML       | 7 185  |
| Lignes PHP        | 2 389  |
| Lignes CSS        | 300    |
| Lignes JS         | 428    |
| Lignes SQL        | 564    |
| **TOTAL LIGNES**  | **10 866** |

---

© 2025 — Association Sportive Club · Blida, Algérie

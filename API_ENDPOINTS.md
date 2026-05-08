# 📡 API ENDPOINTS - DOCUMENTATION COMPLÈTE

## Préface
Tous les endpoints retournent du **JSON**. L'authentification utilise les sessions PHP existantes.

---

## 🔐 AUTHENTIFICATION

### Vérifier authentification
```http
GET /api/actualites.php?action=check-auth
```
**Réponse**:
```json
{
  "authenticated": true,
  "user_id": 1,
  "role": "coach",
  "user_name": "Sophie Martin"
}
```

---

## 📰 ACTUALITÉS - ENDPOINTS LECTUR
E

### 1. Lister articles (Public)
```http
GET /api/actualites.php?limit=12&page=1&status=publie
```
**Paramètres**:
```
limit    (int)     : Articles par page (défaut: 12, max: 100)
page     (int)     : Numéro page (défaut: 1)
status   (string)  : 'publie'|'brouillon'|'archive' (défaut: publie)
sort_by  (string)  : 'recent'|'trending'|'views' (défaut: recent)
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "total": 42,
  "page": 1,
  "limit": 12,
  "pages": 4,
  "data": [
    {
      "id": 1,
      "titre": "Victoire Éclatante en Finale Régionale",
      "slug": "victoire-finale-regionale",
      "extrait": "Notre équipe première a remporté la finale régionale...",
      "image": "/uploads/actualites/victoire.jpg",
      "image_thumbnail": "/uploads/actualites/victoire-thumb.jpg",
      "categorie": "resultats",
      "tags": ["football", "victoire", "finale"],
      "auteur": "Sophie Martin",
      "auteur_id": 2,
      "statut": "publie",
      "vues": 342,
      "likes_count": 45,
      "comments_count": 8,
      "shares_count": 12,
      "reading_time": 5,
      "is_featured": true,
      "featured_until": "2026-05-31T23:59:59",
      "sport_id": 1,
      "age_group": ["seniors", "juniors"],
      "published_at": "2026-05-06T10:30:00",
      "created_at": "2026-05-05T09:15:00"
    },
    ...
  ]
}
```

**Erreur 401**:
```json
{
  "error": "Non authentifié",
  "code": "UNAUTHORIZED"
}
```

---

### 2. Obtenir un article
```http
GET /api/actualites.php?id=1
```
**OU**
```http
GET /api/actualites.php?slug=victoire-finale-regionale
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "titre": "Victoire Éclatante en Finale Régionale",
    "slug": "victoire-finale-regionale",
    "contenu": "<p>Notre équipe première a remporté...</p>",
    "extrait": "Notre équipe première a remporté la finale régionale...",
    "image": "/uploads/actualites/victoire.jpg",
    "image_thumbnail": "/uploads/actualites/victoire-thumb.jpg",
    "image_webp": "/uploads/actualites/victoire.webp",
    "categorie": "resultats",
    "tags": ["football", "victoire", "finale"],
    "auteur": "Sophie Martin",
    "auteur_id": 2,
    "avatar_auteur": "/uploads/avatars/sophie.jpg",
    "statut": "publie",
    "vues": 343,
    "likes_count": 45,
    "comments_count": 8,
    "shares_count": 12,
    "reading_time": 5,
    "is_featured": true,
    "featured_until": "2026-05-31T23:59:59",
    "sport_id": 1,
    "age_group": ["seniors", "juniors"],
    "meta_description": "Victoire de notre équipe football en finale régionale...",
    "meta_keywords": "football, victoire, finale, sport",
    "published_at": "2026-05-06T10:30:00",
    "created_at": "2026-05-05T09:15:00",
    "updated_at": "2026-05-06T11:20:00"
  }
}
```

**Erreur 404**:
```json
{
  "error": "Article non trouvé",
  "code": "NOT_FOUND"
}
```

---

### 3. Filtrer articles
```http
GET /api/actualites.php?category=resultats&sport_id=1&age_group=seniors&search=football
```
**Paramètres**:
```
category     (string) : 'resultats'|'evenements'|'vie-du-club'|'honneurs'
sport_id     (int)    : ID du sport (FK vers table sports)
age_group    (string) : 'enfants'|'juniors'|'seniors'
search       (string) : Recherche textuelle (titre, extrait, tags)
date_from    (date)   : YYYY-MM-DD
date_to      (date)   : YYYY-MM-DD
featured     (bool)   : true → seulement articles épinglés
```

**Réponse**: Même structure que listage

---

### 4. Articles épinglés (Vedette)
```http
GET /api/actualites.php?featured=1&limit=5
```
**Réponse**: Liste articles avec `is_featured=true`

---

## ✍️ ACTUALITÉS - ENDPOINTS CRUD (Admin)

### 5. Créer un article
```http
POST /api/actualites.php
Content-Type: multipart/form-data

Body:
{
  "titre": "Nouvel article",
  "slug": "nouvel-article",
  "extrait": "Résumé court",
  "contenu": "<p>Contenu riche HTML</p>",
  "image": [File object],
  "categorie": "resultats",
  "tags": ["football", "news"],
  "sport_id": 1,
  "age_group": ["seniors", "juniors"],
  "statut": "brouillon",
  "scheduled_at": null,
  "is_featured": false,
  "send_notification": true,
  "notification_text": "Nouvelle actualité!",
  "meta_description": "SEO description",
  "meta_keywords": "football, news"
}
```

**Authentification requise**: Role `coach` ou `admin`

**Réponse** (Succès 201):
```json
{
  "success": true,
  "message": "Article créé avec succès",
  "data": {
    "id": 45,
    "titre": "Nouvel article",
    "slug": "nouvel-article",
    ...
  }
}
```

**Erreur 400** (Validation):
```json
{
  "error": "Validation échouée",
  "code": "VALIDATION_ERROR",
  "fields": {
    "titre": "Le titre est requis",
    "image": "Image > 5MB"
  }
}
```

**Erreur 403** (Permission):
```json
{
  "error": "Permission refusée",
  "code": "FORBIDDEN",
  "required_role": "coach"
}
```

---

### 6. Modifier un article
```http
PUT /api/actualites.php?id=45
Content-Type: multipart/form-data

Body: {
  "titre": "Article modifié",
  "extrait": "...",
  "contenu": "...",
  "image": [optionnel],
  ... autres champs
}
```

**Authentification**: Author ou Admin

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Article modifié",
  "data": { ... }
}
```

---

### 7. Supprimer un article
```http
DELETE /api/actualites.php?id=45
```

**Authentification**: Admin uniquement

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Article supprimé"
}
```

**Erreur 403**:
```json
{
  "error": "Seul l'admin peut supprimer",
  "code": "ADMIN_ONLY"
}
```

---

### 8. Publier/Dépublier
```http
PUT /api/actualites.php?action=publish&id=45&status=publie
```

**Paramètres**:
```
action    : 'publish'
id        : Article ID
status    : 'publie'|'brouillon'|'archive'
published_at : null|YYYY-MM-DD HH:MM:SS (optionnel)
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Article publié",
  "data": {
    "id": 45,
    "statut": "publie",
    "published_at": "2026-05-08T10:30:00"
  }
}
```

---

### 9. Épingler/Dépingler article
```http
PUT /api/actualites.php?action=feature&id=45
Content-Type: application/json

Body: {
  "is_featured": true,
  "featured_until": "2026-05-31T23:59:59"
}
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Article épinglé jusqu'au 31-05-2026"
}
```

---

## ❤️ LIKES - ENDPOINTS

### 10. Aimer un article
```http
POST /api/likes.php?article_id=45
```

**Authentification**: Requise (connecté)

**Réponse** (Succès 201):
```json
{
  "success": true,
  "message": "Article aimé",
  "article_id": 45,
  "user_id": 3,
  "likes_count": 46,
  "liked": true
}
```

**Erreur 400** (Déjà aimé):
```json
{
  "error": "Vous avez déjà aimé cet article",
  "code": "ALREADY_LIKED"
}
```

---

### 11. Retirer un like (Contraimer)
```http
DELETE /api/likes.php?article_id=45
```

**Authentification**: Requise

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Like retiré",
  "article_id": 45,
  "likes_count": 45,
  "liked": false
}
```

---

### 12. Lister les likes d'un article
```http
GET /api/likes.php?article_id=45&limit=10
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "total": 45,
  "data": [
    {
      "user_id": 3,
      "user_name": "Karim Benali",
      "avatar": "/uploads/avatars/karim.jpg",
      "liked_at": "2026-05-08T14:30:00"
    },
    ...
  ]
}
```

---

### 13. Récupérer mes likes
```http
GET /api/likes.php?user_id=me
```

**Authentification**: Requise

**Réponse** (Succès 200):
```json
{
  "success": true,
  "data": [
    { "actualite_id": 1, "titre": "Article 1" },
    { "actualite_id": 5, "titre": "Article 5" },
    ...
  ]
}
```

---

## 💾 FAVORIS (Saves) - ENDPOINTS

### 14. Ajouter aux favoris
```http
POST /api/saves.php?article_id=45
```

**Authentification**: Requise

**Réponse** (Succès 201):
```json
{
  "success": true,
  "message": "Article sauvegardé",
  "saved": true
}
```

---

### 15. Retirer des favoris
```http
DELETE /api/saves.php?article_id=45
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Retiré des favoris",
  "saved": false
}
```

---

### 16. Lister mes favoris
```http
GET /api/saves.php?limit=20&page=1
```

**Authentification**: Requise

**Réponse** (Succès 200):
```json
{
  "success": true,
  "total": 12,
  "data": [
    {
      "id": 1,
      "titre": "Article 1",
      "slug": "article-1",
      "image": "...",
      "saved_at": "2026-05-07T09:15:00"
    },
    ...
  ]
}
```

---

## 📸 UPLOAD IMAGES

### 17. Upload une image
```http
POST /api/upload-image.php
Content-Type: multipart/form-data

Form-Data:
{
  "image": [File object],
  "type": "actualite"  // optionnel: actualite|profile|etc
}
```

**Authentification**: Coach+

**Traitement automatique**:
- ✅ Validation format (JPG, PNG, WebP)
- ✅ Compression (max 100KB)
- ✅ Redimensionnement (1200×630px)
- ✅ Générer thumbnail (600×400px)
- ✅ Convertir en WebP

**Réponse** (Succès 200):
```json
{
  "success": true,
  "data": {
    "image": "/uploads/actualites/2026-05-08-abc123.jpg",
    "image_webp": "/uploads/actualites/2026-05-08-abc123.webp",
    "thumbnail": "/uploads/actualites/2026-05-08-abc123-thumb.jpg",
    "width": 1200,
    "height": 630,
    "size_original": 1524000,
    "size_compressed": 89000,
    "compression_ratio": 94.2
  }
}
```

**Erreur 400**:
```json
{
  "error": "Format non supporté",
  "code": "INVALID_FORMAT",
  "allowed": ["jpg", "png", "webp"]
}
```

---

## 🔔 NOTIFICATIONS PUSH

### 18. Envoyer notification push
```http
POST /api/notifications/send-push.php
Content-Type: application/json

Body: {
  "article_id": 45,
  "title": "🏆 Nouvelle actualité",
  "message": "Victoire en finale!",
  "target": "all"  // all|followers|role:coach
}
```

**Authentification**: Admin+

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Notification envoyée",
  "sent_to": 234,
  "article_id": 45,
  "sent_at": "2026-05-08T10:30:00"
}
```

---

### 19. Statut envoi notifications
```http
GET /api/notifications.php?article_id=45
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "data": {
    "article_id": 45,
    "notif_sent": true,
    "notif_sent_at": "2026-05-08T10:30:00",
    "sent_to_count": 234,
    "delivered": 198,
    "failed": 36
  }
}
```

---

## 📊 STATISTIQUES & ANALYTICS

### 20. Dashboard stats
```http
GET /api/actualites/stats.php
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "data": {
    "stats_by_status": {
      "brouillon": 5,
      "publie": 23,
      "archive": 12
    },
    "stats_by_category": {
      "resultats": 8,
      "evenements": 6,
      "vie-du-club": 7,
      "honneurs": 2
    },
    "engagement": {
      "total_views": 3452,
      "total_likes": 854,
      "total_shares": 324,
      "total_comments": 156
    },
    "recent_articles": [
      { "id": 1, "titre": "...", "vues": 342, "likes": 45 },
      ...
    ],
    "trending": [
      { "id": 1, "titre": "...", "likes": 45 },
      ...
    ],
    "scheduled": [
      {
        "id": 46,
        "titre": "Article programmé",
        "scheduled_at": "2026-05-10T14:00:00"
      }
    ]
  }
}
```

---

## 🚀 PROGRAMMATION - CRON

### 21. Exécuter publications programmées
```http
GET /api/cron/publish-scheduled.php?token=SECRET_TOKEN
```

**Parameters**:
```
token : Token sécurisé (stocker en env pour sécurité)
```

**À appeler toutes les heures**:
```bash
0 * * * * curl "https://votre-site.com/api/cron/publish-scheduled.php?token=YOUR_SECRET"
```

**Réponse** (Succès 200):
```json
{
  "success": true,
  "message": "Publications exécutées",
  "articles_published": 3,
  "published_ids": [43, 44, 46]
}
```

---

## ⚠️ CODES ERREURS

| Code | HTTP | Meaning |
|------|------|---------|
| SUCCESS | 200 | ✅ Succès |
| CREATED | 201 | ✅ Créé |
| BAD_REQUEST | 400 | ❌ Requête invalide |
| UNAUTHORIZED | 401 | ❌ Non authentifié |
| FORBIDDEN | 403 | ❌ Permission refusée |
| NOT_FOUND | 404 | ❌ Non trouvé |
| CONFLICT | 409 | ❌ Conflit (ex: déjà aimé) |
| VALIDATION_ERROR | 422 | ❌ Validation échouée |
| SERVER_ERROR | 500 | ❌ Erreur serveur |

---

## 📝 EXEMPLE WORKFLOW COMPLET

### Utilisateur lit un article
```
1. GET /api/actualites.php?id=1
   → Récupère détails article

2. POST /api/likes.php?article_id=1
   → Aime l'article (si connecté)

3. GET /api/likes.php?article_id=1
   → Voit qui a aimé

4. POST /api/saves.php?article_id=1
   → Ajoute aux favoris
```

### Admin crée article
```
1. POST /api/upload-image.php
   → Upload image (compression auto)

2. POST /api/actualites.php
   → Créer article (brouillon)

3. PUT /api/actualites.php?action=publish&id=45
   → Publier

4. PUT /api/actualites.php?action=feature&id=45
   → Épingler

5. POST /api/notifications/send-push.php
   → Envoyer notification aux followers
```

---

## 🔒 SÉCURITÉ

- ✅ **CSRF Protection**: Tokens sur formulaires
- ✅ **SQL Injection**: Requêtes préparées (PDO)
- ✅ **XSS Protection**: Sanitisation HTML (HTML Purifier)
- ✅ **Rate Limiting**: 100 requêtes/minute par IP
- ✅ **Image Validation**: Type MIME vérification
- ✅ **File Size Limit**: Max 5MB par image
- ✅ **File Extension Whitelist**: Seulement jpg, png, webp
- ✅ **Upload Directory**: Hors racine web

---

## 🧪 TESTER LES ENDPOINTS

### Avec cURL
```bash
# Lister articles
curl "http://localhost/api/actualites.php"

# Créer article (nécessite auth)
curl -X POST "http://localhost/api/actualites.php" \
  -H "Cookie: PHPSESSID=xxx" \
  -F "titre=Test" \
  -F "extrait=Test" \
  -F "contenu=Test"
```

### Avec Postman
1. Importer [postman_collection.json](postman_collection.json) (à créer)
2. Configurer variables d'environnement
3. Lancer les tests

### Avec JavaScript
```javascript
// Lister articles
fetch('/api/actualites.php')
  .then(r => r.json())
  .then(d => console.log(d.data));

// Aimer article
fetch('/api/likes.php?article_id=1', { method: 'POST' })
  .then(r => r.json())
  .then(d => console.log(d.likes_count));
```

---

**Documentation API v1.0 | Mai 2026**

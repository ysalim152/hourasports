# 📊 GUIDE IMPLÉMENTATION : INTERFACE ADMIN CRUD ACTUALITÉS

## 1. STRUCTURE DE LA PAGE ADMIN

### 1.1 Hiérarchie des Routes
```
/public/admin/
├─ dashboard.html          (Vue d'ensemble)
├─ actualites/
│  ├─ index.html          (Tableau listage)
│  ├─ creer.html          (Créer nouvel article)
│  ├─ editer.html         (Éditer article)
│  ├─ voir.html           (Aperçu avant publication)
│  └─ programmees.html    (Articles programmés)
```

### 1.2 Éléments Technologiques Clés

```html
<!-- Éditeur WYSIWYG - TinyMCE 7 -->
<script src="https://cdn.tiny.cloud/1/YOUR_TINY_KEY/tinymce/7/tinymce.min.js"></script>

<!-- Gestion dates/heures - Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Upload d'images - Filepond -->
<link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
<script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>

<!-- Notifications - Toastr -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
```

---

## 2. FORMULAIRE DE CRÉATION/ÉDITION

### 2.1 Structure HTML Complète

```html
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Créer Actualité — Admin</title>
  <!-- Styles inclus ici -->
</head>
<body>

<div class="admin-container">
  
  <!-- Sidebar Admin -->
  <aside class="admin-sidebar">
    <!-- Menu navigation admin -->
  </aside>

  <!-- Contenu Principal -->
  <main class="admin-main">
    
    <!-- En-tête -->
    <div class="admin-header">
      <h1>✏️ Créer une Actualité</h1>
      <nav class="breadcrumb">
        <a href="dashboard.html">Dashboard</a>
        <span>/</span>
        <a href="index.html">Actualités</a>
        <span>/</span>
        <span>Créer</span>
      </nav>
    </div>

    <!-- Formulaire -->
    <form id="articleForm" class="article-form">
      
      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           1. IMAGE EN VEDETTE
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>📸 Image en Vedette</h2>
        
        <div class="image-upload-zone" id="uploadZone">
          <div class="upload-placeholder">
            <div class="upload-icon">📤</div>
            <p>Glissez-déposez une image ou <span class="btn-link">cliquez ici</span></p>
            <small>JPG, PNG ou WebP • Max 5MB • 1200×630px recommandé</small>
          </div>
          <input type="file" id="imageInput" accept="image/*" hidden>
        </div>

        <div id="imagePreview" class="image-preview" style="display:none;">
          <img id="previewImg" src="" alt="Aperçu">
          <div class="preview-actions">
            <button type="button" class="btn-small" id="editImageBtn">
              Modifier l'image
            </button>
            <button type="button" class="btn-small btn-danger" id="removeImageBtn">
              Supprimer
            </button>
          </div>
          <div class="image-info">
            <p>Dimensions: <span id="imageDimensions">-</span></p>
            <p>Taille: <span id="imageSize">-</span></p>
          </div>
        </div>

        <input type="hidden" id="imageUrl" name="image">
      </section>

      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           2. CONTENU DE L'ARTICLE
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>📝 Contenu</h2>

        <!-- Titre -->
        <div class="form-group">
          <label for="titre">
            Titre <span class="required">*</span>
            <span class="char-counter">
              <span id="titleLength">0</span>/<span id="titleMax">200</span>
            </span>
          </label>
          <input 
            type="text" 
            id="titre" 
            name="titre" 
            class="form-control"
            placeholder="Ex: Victoire éclatante en finale régionale"
            maxlength="200"
            required
          >
          <small>Maximum 200 caractères (pour le SEO: 70 caractères idéal)</small>
        </div>

        <!-- Slug (auto-généré) -->
        <div class="form-group">
          <label for="slug">Slug (URL)</label>
          <div class="input-group">
            <span class="input-prefix">/actualite/</span>
            <input 
              type="text" 
              id="slug" 
              name="slug" 
              class="form-control"
              placeholder="victoire-finale-regionale"
            >
          </div>
          <small>Auto-généré à partir du titre. Éditable manuellement.</small>
        </div>

        <!-- Extrait -->
        <div class="form-group">
          <label for="extrait">
            Extrait <span class="required">*</span>
            <span class="char-counter">
              <span id="excerptLength">0</span>/<span id="excerptMax">150</span>
            </span>
          </label>
          <textarea 
            id="extrait" 
            name="extrait" 
            class="form-control"
            rows="3"
            placeholder="Résumé de l'article (150 caractères max)"
            maxlength="150"
            required
          ></textarea>
          <small>Affiche en avant-première. Suggestion auto: premiers 150 caract. du contenu</small>
        </div>

        <!-- Contenu riche (WYSIWYG) -->
        <div class="form-group">
          <label for="contenu">
            Contenu Riche <span class="required">*</span>
            <span class="word-counter">
              <span id="wordCount">0</span> mots | 
              <span id="readingTime">5</span> min
            </span>
          </label>
          <textarea 
            id="contenu" 
            name="contenu" 
            class="form-control rich-editor"
            rows="15"
            required
          ></textarea>
          <small>
            Vous pouvez utiliser: titres, listes, images, liens, mise en forme. 
            Les images seront compressées automatiquement.
          </small>
        </div>
      </section>

      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           3. MÉTADONNÉES & CATÉGORISATION
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>🏷️ Métadonnées & Catégorisation</h2>

        <!-- Catégorie -->
        <div class="form-row">
          <div class="form-group">
            <label for="categorie">
              Catégorie <span class="required">*</span>
            </label>
            <select id="categorie" name="categorie" class="form-control" required>
              <option value="">-- Sélectionner --</option>
              <option value="resultats">🏆 Résultats</option>
              <option value="evenements">📅 Événements</option>
              <option value="vie-du-club">🏢 Vie du Club</option>
              <option value="honneurs">👥 Honneurs</option>
            </select>
          </div>

          <!-- Sport -->
          <div class="form-group">
            <label for="sport_id">Sport</label>
            <select id="sport_id" name="sport_id" class="form-control">
              <option value="">-- Tous les sports --</option>
              <option value="1">⚽ Football</option>
              <option value="2">🏀 Basketball</option>
              <option value="3">🏐 Volleyball</option>
              <option value="4">🤾 Handball</option>
              <option value="5">🏊 Natation</option>
              <option value="6">🏃 Athlétisme</option>
              <option value="7">🥋 Judo</option>
              <option value="8">🎾 Tennis</option>
            </select>
          </div>
        </div>

        <!-- Groupes d'âge -->
        <div class="form-group">
          <label>Groupes d'Âge Cibles</label>
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" name="age_group" value="enfants">
              👶 Enfants
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="age_group" value="juniors">
              ⚡ Juniors
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="age_group" value="seniors">
              🏋️ Séniors
            </label>
          </div>
        </div>

        <!-- Tags -->
        <div class="form-group">
          <label for="tagsInput">Tags</label>
          <div class="tags-input-wrapper">
            <input 
              type="text" 
              id="tagsInput" 
              class="form-control"
              placeholder="Ajouter un tag et appuyer sur Entrée"
            >
            <div class="tags-list" id="tagsList"></div>
          </div>
          <small>Tags pour meilleure catégorisation et recherche</small>
        </div>

        <!-- SEO Meta -->
        <div class="form-group">
          <label for="metaDescription">Meta Description (SEO)</label>
          <textarea 
            id="metaDescription" 
            name="meta_description" 
            class="form-control"
            rows="2"
            placeholder="Description pour les moteurs de recherche (160 char max)"
            maxlength="160"
          ></textarea>
          <small>Important pour le référencement Google</small>
        </div>
      </section>

      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           4. PROGRAMMATION & PUBLICATION
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>📅 Programmation & Publication</h2>

        <!-- Statut -->
        <div class="form-group">
          <label for="statut">Statut <span class="required">*</span></label>
          <select id="statut" name="statut" class="form-control" required>
            <option value="brouillon">📝 Brouillon</option>
            <option value="publie">✅ Publié</option>
            <option value="archive">🗂️ Archivé</option>
          </select>
        </div>

        <!-- Programmation -->
        <div class="form-group">
          <label class="checkbox-label large">
            <input type="checkbox" id="isProgrammed" name="is_programmed">
            📅 Programmer la publication
          </label>
        </div>

        <div id="programmationFields" class="form-row" style="display:none;">
          <div class="form-group">
            <label for="scheduledDate">Date de publication</label>
            <input type="date" id="scheduledDate" class="form-control">
          </div>
          <div class="form-group">
            <label for="scheduledTime">Heure de publication</label>
            <input type="time" id="scheduledTime" class="form-control">
          </div>
        </div>

        <div class="form-group">
          <label class="checkbox-label large">
            <input type="checkbox" id="isFeatured" name="is_featured">
            📌 Épingler cet article (vedette)
          </label>
        </div>

        <div id="featureFields" class="form-row" style="display:none;">
          <div class="form-group">
            <label for="featuredUntil">Épinglé jusqu'au</label>
            <input type="date" id="featuredUntil" class="form-control">
          </div>
        </div>
      </section>

      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           5. NOTIFICATIONS PUSH
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>🔔 Notifications Push</h2>

        <div class="form-group">
          <label class="checkbox-label large">
            <input type="checkbox" id="sendNotification" name="send_notification">
            Envoyer notification aux followers
          </label>
        </div>

        <div id="notificationFields" style="display:none;">
          <div class="form-group">
            <label for="notificationText">Texte de la notification</label>
            <textarea 
              id="notificationText" 
              class="form-control"
              rows="2"
              placeholder="Ex: Nouvelle actualité : Victoire en finale!"
            ></textarea>
            <small>Max 100 caractères. Laissez vide pour utiliser le titre de l'article.</small>
          </div>

          <div class="notification-preview">
            <p class="preview-title">Aperçu notification</p>
            <div class="notification-mock">
              <div class="notification-icon">🏆 ASClub</div>
              <div class="notification-text" id="notificationPreview">
                Nouvelle actualité : Victoire en finale!
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
           6. ACTIONS & ZONE DANGER
         ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
      <section class="form-section">
        <h2>⚙️ Actions</h2>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary btn-large" id="submitBtn">
            💾 Enregistrer l'article
          </button>
          <button type="button" class="btn btn-secondary btn-large" id="previewBtn">
            👁️ Aperçu
          </button>
          <button type="button" class="btn btn-outline" id="saveDraftBtn">
            📝 Enregistrer comme brouillon
          </button>
          <a href="index.html" class="btn btn-ghost">
            ← Retour
          </a>
        </div>

        <div class="danger-zone" id="dangerZone" style="display:none;">
          <h3>⚠️ Zone Danger</h3>
          <button type="button" class="btn btn-danger" id="deleteBtn">
            🗑️ Supprimer cet article
          </button>
        </div>
      </section>

    </form>

  </main>

</div>

<!-- Modal Aperçu -->
<div id="previewModal" class="modal" style="display:none;">
  <div class="modal-content preview-modal">
    <button class="modal-close">&times;</button>
    <div id="previewContent"></div>
  </div>
</div>

<!-- Modal Confirmation Suppression -->
<div id="deleteConfirmModal" class="modal" style="display:none;">
  <div class="modal-content small">
    <h3>Confirmer la suppression</h3>
    <p>Êtes-vous sûr de vouloir supprimer cet article? Cette action est irréversible.</p>
    <div class="modal-actions">
      <button class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
      <button class="btn btn-secondary" id="cancelDeleteBtn">Annuler</button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.tiny.cloud/1/YOUR_TINY_KEY/tinymce/7/tinymce.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script src="admin-article-form.js"></script>

</body>
</html>
```

---

## 3. TABLEAU LISTAGE - TABLEAU DE BORD

### 3.1 Structure HTML

```html
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion Actualités — Admin</title>
</head>
<body>

<div class="admin-container">
  <aside class="admin-sidebar"><!-- Menu nav --></aside>
  <main class="admin-main">

    <!-- En-tête -->
    <div class="admin-header">
      <h1>📰 Gestion des Actualités</h1>
      <div class="header-actions">
        <a href="creer.html" class="btn btn-primary">+ Nouvel Article</a>
        <button id="exportBtn" class="btn btn-secondary">⬇️ Exporter CSV</button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value" id="statBrouillons">0</div>
        <div class="stat-label">📝 Brouillons</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statPublies">0</div>
        <div class="stat-label">✅ Publiés</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statProgrammes">0</div>
        <div class="stat-label">📅 Programmés</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statArchives">0</div>
        <div class="stat-label">🗂️ Archivés</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statVues">0</div>
        <div class="stat-label">👁️ Vues</div>
      </div>
      <div class="stat-card">
        <div class="stat-value" id="statLikes">0</div>
        <div class="stat-label">❤️ Likes</div>
      </div>
    </div>

    <!-- Filtres & Recherche -->
    <div class="table-toolbar">
      <div class="search-wrapper">
        <input 
          type="text" 
          id="searchInput" 
          class="form-control"
          placeholder="🔍 Rechercher article..."
        >
      </div>

      <div class="filter-group">
        <select id="filterStatus" class="form-control">
          <option value="">Tous les statuts</option>
          <option value="brouillon">📝 Brouillon</option>
          <option value="publie">✅ Publié</option>
          <option value="archive">🗂️ Archivé</option>
        </select>

        <select id="filterSport" class="form-control">
          <option value="">Tous les sports</option>
          <option value="football">⚽ Football</option>
          <option value="basketball">🏀 Basketball</option>
          <!-- ... -->
        </select>

        <select id="filterAuthor" class="form-control">
          <option value="">Tous les auteurs</option>
          <!-- Peuplé dynamiquement -->
        </select>
      </div>

      <button id="resetFiltersBtn" class="btn btn-ghost">↻ Réinitialiser</button>
    </div>

    <!-- Tableau -->
    <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>
              <input type="checkbox" id="selectAllCheckbox">
            </th>
            <th>Titre</th>
            <th>Catégorie</th>
            <th>Auteur</th>
            <th>Statut</th>
            <th>Vues</th>
            <th>Likes</th>
            <th>Créé</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="articlesTableBody">
          <!-- Les lignes seront injectées par JS -->
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="table-pagination">
      <span id="paginationInfo">Page 1 de 5</span>
      <div class="pagination-buttons">
        <button id="prevPageBtn" class="btn btn-small">← Précédent</button>
        <button id="nextPageBtn" class="btn btn-small">Suivant →</button>
      </div>
    </div>

  </main>
</div>

<!-- Scripts -->
<script src="admin-articles-list.js"></script>

</body>
</html>
```

---

## 4. FONCTIONNALITÉS JAVASCRIPT CLÉS

### 4.1 Initialisation Éditeur WYSIWYG (TinyMCE)

```javascript
// Dans admin-article-form.js
tinymce.init({
  selector: '#contenu',
  language: 'fr_FR',
  plugins: [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
    'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
    'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'
  ],
  toolbar: 'undo redo | formatselect | bold italic backcolor | ' +
    'alignleft aligncenter alignright alignjustify | ' +
    'bullist numlist outdent indent | link image media | code help',
  height: 400,
  
  // Callbacks
  setup: (editor) => {
    editor.on('change', () => {
      updateReadingTime();
      updateSlug();
    });
  },
  
  // Images
  image_caption: true,
  image_title: true,
  image_dimensions: false,
  automatic_uploads: true,
  file_picker_types: 'image',
  file_picker_callback: function(callback, value, meta) {
    if (meta.filetype === 'image') {
      handleImageUpload(callback);
    }
  }
});

// Upload d'image optimisé
async function handleImageUpload(callback) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  
  input.onchange = async (e) => {
    const file = e.target.files[0];
    const compressedImage = await compressImage(file);
    const formData = new FormData();
    formData.append('image', compressedImage);
    
    const response = await fetch('/api/upload-image.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    callback(data.url, { alt: file.name });
  };
  
  input.click();
}

// Compression d'image côté client
async function compressImage(file) {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (e) => {
      const img = new Image();
      img.src = e.target.result;
      img.onload = () => {
        const canvas = document.createElement('canvas');
        const maxWidth = 1200;
        const maxHeight = 630;
        let width = img.width;
        let height = img.height;
        
        if (width > height) {
          if (width > maxWidth) {
            height *= maxWidth / width;
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width *= maxHeight / height;
            height = maxHeight;
          }
        }
        
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
        
        canvas.toBlob((blob) => {
          resolve(new File([blob], 'compressed.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.8);
      };
    };
  });
}
```

### 4.2 Calcul du Temps de Lecture

```javascript
function updateReadingTime() {
  const editor = tinymce.get('contenu');
  const text = editor.getContent({ format: 'text' });
  const wordCount = text.split(/\s+/).length;
  const readingTime = Math.ceil(wordCount / 200); // 200 mots/min
  
  document.getElementById('wordCount').textContent = wordCount;
  document.getElementById('readingTime').textContent = readingTime;
}
```

### 4.3 Génération Automatique du Slug

```javascript
function updateSlug() {
  const title = document.getElementById('titre').value;
  const slug = title
    .toLowerCase()
    .replace(/[àâä]/g, 'a')
    .replace(/[èéêë]/g, 'e')
    .replace(/[îï]/g, 'i')
    .replace(/[ôö]/g, 'o')
    .replace(/[ûü]/g, 'u')
    .replace(/ç/g, 'c')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
  
  document.getElementById('slug').value = slug;
}
```

### 4.4 Gestion des Tags

```javascript
const tags = [];

document.getElementById('tagsInput').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    const input = e.target;
    const tag = input.value.trim().toLowerCase();
    
    if (tag && !tags.includes(tag) && tags.length < 10) {
      tags.push(tag);
      renderTags();
      input.value = '';
    }
  }
});

function renderTags() {
  const container = document.getElementById('tagsList');
  container.innerHTML = tags.map(tag => `
    <span class="tag">
      ${tag}
      <button type="button" onclick="removeTag('${tag}')">×</button>
    </span>
  `).join('');
}

function removeTag(tag) {
  tags.splice(tags.indexOf(tag), 1);
  renderTags();
}
```

### 4.5 Soumission du Formulaire

```javascript
document.getElementById('articleForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  // Ajouter éditeur contenu
  formData.set('contenu', tinymce.get('contenu').getContent());
  
  // Ajouter tags
  formData.set('tags', JSON.stringify(tags));
  
  // Ajouter groupes d'âge
  const ageGroups = Array.from(document.querySelectorAll('input[name="age_group"]:checked'))
    .map(cb => cb.value);
  formData.set('age_group', JSON.stringify(ageGroups));
  
  // Programmation
  if (document.getElementById('isProgrammed').checked) {
    const date = document.getElementById('scheduledDate').value;
    const time = document.getElementById('scheduledTime').value;
    formData.set('scheduled_at', `${date}T${time}`);
  }
  
  try {
    const response = await fetch('/api/actualites.php', {
      method: 'POST',
      body: formData
    });
    
    if (response.ok) {
      toastr.success('Article enregistré avec succès!');
      setTimeout(() => window.location.href = 'index.html', 1500);
    } else {
      throw new Error('Erreur serveur');
    }
  } catch (error) {
    console.error('Erreur:', error);
    toastr.error('Erreur lors de l\'enregistrement');
  }
});
```

---

## 5. API ENDPOINTS NÉCESSAIRES

### 5.1 Endpoints Admin

```
POST   /api/actualites.php                    → Créer article
PUT    /api/actualites.php?id=X              → Modifier article  
DELETE /api/actualites.php?id=X              → Supprimer article

POST   /api/upload-image.php                 → Upload + compression
POST   /api/notifications/send-push.php      → Envoyer notification push

GET    /api/actualites.php?status=brouillon  → Filtrer par statut
GET    /api/actualites/stats.php             → Statistiques dashboard
```

---

## 6. CHECKLIST IMPLÉMENTATION

- [ ] Base de données : Migration SQL exécutée
- [ ] Éditeur WYSIWYG : TinyMCE intégré avec compression images
- [ ] Upload images : Validation taille/format + redimensionnement automatique
- [ ] Formulaire : Validation côté client + serveur
- [ ] Programmation : Cronjob configuré pour publications programmées
- [ ] Dashboard : Statistiques en temps réel
- [ ] Tableau listage : Filtres, recherche, actions contextuelles
- [ ] Notifications push : Intégrées et testées
- [ ] Tests : Chrome, Firefox, Safari, Mobile
- [ ] Accessibilité : WCAG 2.1 AA

---

**Version: 1.0 | Mai 2026**

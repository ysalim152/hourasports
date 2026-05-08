/**
 * ACTUALITÉS - GESTION CÔTÉ CLIENT
 * Filtrage, recherche, engagement (likes), partage
 */

// Configuration
const API_BASE = '/api';
const ARTICLES_PER_PAGE = 12;

// État global
const state = {
  articles: [],
  filteredArticles: [],
  currentPage: 1,
  filters: {
    search: '',
    category: '',
    sport: '',
    ageGroup: '',
    sortBy: 'recent'
  },
  viewType: 'grid',
  userLikes: new Set()
};

// ============================================================
// INITIALISATION
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  initializeEventListeners();
  loadArticles();
  loadUserLikes();
});

// ============================================================
// EVENT LISTENERS
// ============================================================

function initializeEventListeners() {
  // Filtres
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', handleFilterClick);
  });

  // Filtres avancés
  document.getElementById('searchInput')?.addEventListener('input', handleSearch);
  document.getElementById('sportFilter')?.addEventListener('change', handleAdvancedFilter);
  document.getElementById('ageFilter')?.addEventListener('change', handleAdvancedFilter);
  document.getElementById('sortBy')?.addEventListener('change', handleSort);

  // Vue
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', handleViewToggle);
  });

  // Pagination
  document.getElementById('prevBtn')?.addEventListener('click', () => prevPage());
  document.getElementById('nextBtn')?.addEventListener('click', () => nextPage());

  // Modal partage
  document.getElementById('shareModal')?.addEventListener('click', handleModalClick);
}

// ============================================================
// CHARGEMENT ARTICLES
// ============================================================

async function loadArticles() {
  try {
    showLoading(true);
    
    const response = await fetch(`${API_BASE}/actualites.php?limit=100`);
    if (!response.ok) throw new Error('Erreur réseau');
    
    state.articles = await response.json();
    applyFilters();
    renderArticles();
    
  } catch (error) {
    console.error('Erreur chargement:', error);
    showEmptyState('Impossible de charger les actualités');
  } finally {
    showLoading(false);
  }
}

// ============================================================
// FILTRAGE & RECHERCHE
// ============================================================

function handleFilterClick(e) {
  const btn = e.target.closest('.filter-btn');
  if (!btn) return;

  // Désactiver les anciens filtres
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Appliquer le filtre
  state.filters.category = btn.dataset.category || '';
  state.currentPage = 1;
  applyFilters();
  renderArticles();
}

function handleSearch(e) {
  state.filters.search = e.target.value.toLowerCase();
  state.currentPage = 1;
  applyFilters();
  renderArticles();
}

function handleAdvancedFilter(e) {
  const select = e.target;
  if (select.id === 'sportFilter') {
    state.filters.sport = select.value;
  } else if (select.id === 'ageFilter') {
    state.filters.ageGroup = select.value;
  }
  state.currentPage = 1;
  applyFilters();
  renderArticles();
}

function handleSort(e) {
  state.filters.sortBy = e.target.value;
  state.currentPage = 1;
  applyFilters();
  renderArticles();
}

function applyFilters() {
  let filtered = [...state.articles];

  // Filtre catégorie
  if (state.filters.category) {
    filtered = filtered.filter(a => a.categorie?.toLowerCase() === state.filters.category);
  }

  // Filtre sport
  if (state.filters.sport) {
    filtered = filtered.filter(a => a.sport_id && a.sport_id.toString() === state.filters.sport);
  }

  // Filtre groupe d'âge
  if (state.filters.ageGroup) {
    filtered = filtered.filter(a => {
      const ageGroups = a.age_group ? JSON.parse(a.age_group) : [];
      return ageGroups.includes(state.filters.ageGroup);
    });
  }

  // Recherche textuelle
  if (state.filters.search) {
    const search = state.filters.search;
    filtered = filtered.filter(a =>
      a.titre.toLowerCase().includes(search) ||
      a.extrait.toLowerCase().includes(search) ||
      (a.tags && a.tags.toLowerCase().includes(search))
    );
  }

  // Tri
  switch (state.filters.sortBy) {
    case 'recent':
      filtered.sort((a, b) => new Date(b.published_at) - new Date(a.published_at));
      break;
    case 'trending':
      filtered.sort((a, b) => b.likes_count - a.likes_count);
      break;
    case 'views':
      filtered.sort((a, b) => b.vues - a.vues);
      break;
    case 'likes':
      filtered.sort((a, b) => (b.likes_count || 0) - (a.likes_count || 0));
      break;
  }

  state.filteredArticles = filtered;
  updateResultsCount();
}

function updateResultsCount() {
  const total = state.filteredArticles.length;
  const element = document.getElementById('resultsCount');
  if (element) {
    element.innerHTML = `Affichage de <strong>${total}</strong> article${total > 1 ? 's' : ''}`;
  }
}

// ============================================================
// AFFICHAGE ARTICLES
// ============================================================

function renderArticles() {
  const container = document.getElementById('articlesContainer');
  if (!container) return;

  // Pagination
  const start = (state.currentPage - 1) * ARTICLES_PER_PAGE;
  const end = start + ARTICLES_PER_PAGE;
  const paginated = state.filteredArticles.slice(start, end);

  // Vider conteneur
  container.innerHTML = '';

  if (paginated.length === 0) {
    showEmptyState('Aucun article ne correspond à votre recherche');
    return;
  }

  // Rendre chaque article
  paginated.forEach(article => {
    const card = createArticleCard(article);
    container.appendChild(card);
  });

  // Mettre à jour pagination
  updatePagination();
  attachArticleEventListeners();
}

function createArticleCard(article) {
  const template = document.getElementById('articleTemplate');
  const card = template.content.cloneNode(true);

  // Données
  card.querySelector('.article-card').dataset.id = article.id;
  card.querySelector('.article-thumbnail').src = article.image || 'assets/images/placeholder.png';
  card.querySelector('.article-thumbnail').alt = article.titre;
  card.querySelector('.article-title').textContent = article.titre;
  card.querySelector('.article-excerpt').textContent = article.extrait;
  card.querySelector('.author-name').textContent = article.auteur || 'Admin';
  card.querySelector('.author-avatar').src = 'assets/images/avatar-default.png';
  card.querySelector('.reading-time span').textContent = article.reading_time || 5;
  card.querySelector('.article-link').href = `actualite-detail.html?id=${article.id}`;

  // Date relative
  const dateElement = card.querySelector('.article-date');
  dateElement.textContent = getRelativeDate(article.published_at);
  dateElement.title = new Date(article.published_at).toLocaleDateString('fr-FR');

  // Catégorie badge
  const categoryBadge = card.querySelector('.category-badge');
  categoryBadge.textContent = getCategoryLabel(article.categorie);
  categoryBadge.style.background = getCategoryColor(article.categorie);

  // Featured badge
  if (article.is_featured) {
    card.querySelector('.featured-badge').style.display = 'inline-block';
  }

  // Compteurs engagement
  const likeCount = card.querySelector('.like-btn .engagement-count');
  likeCount.textContent = article.likes_count || 0;

  const viewCount = card.querySelector('.view-btn .engagement-count');
  viewCount.textContent = formatNumber(article.vues || 0);

  const shareCount = card.querySelector('.share-btn .engagement-count');
  shareCount.textContent = formatNumber(article.shares_count || 0);

  // État like
  if (state.userLikes.has(article.id)) {
    card.querySelector('.like-btn').classList.add('liked');
  }

  return card;
}

function attachArticleEventListeners() {
  document.querySelectorAll('.article-card').forEach(card => {
    const likeBtn = card.querySelector('.like-btn');
    const shareBtn = card.querySelector('.share-btn');

    likeBtn?.addEventListener('click', (e) => handleLike(e, card));
    shareBtn?.addEventListener('click', (e) => handleShare(e, card));
  });
}

// ============================================================
// ENGAGEMENT : LIKES
// ============================================================

async function handleLike(e, card) {
  e.preventDefault();
  const btn = e.currentTarget;
  const articleId = card.dataset.id;

  if (!isUserLoggedIn()) {
    redirectToLogin();
    return;
  }

  try {
    const isLiked = btn.classList.contains('liked');
    const method = isLiked ? 'DELETE' : 'POST';

    const response = await fetch(`${API_BASE}/likes.php?article_id=${articleId}`, {
      method,
      headers: { 'Content-Type': 'application/json' }
    });

    if (!response.ok) throw new Error('Erreur like');

    // Toggle UI
    btn.classList.toggle('liked');
    const count = parseInt(btn.querySelector('.engagement-count').textContent) || 0;
    btn.querySelector('.engagement-count').textContent = isLiked ? count - 1 : count + 1;

    // Mettre à jour state
    if (isLiked) {
      state.userLikes.delete(articleId);
    } else {
      state.userLikes.add(articleId);
    }

  } catch (error) {
    console.error('Erreur like:', error);
    alert('Erreur lors du like. Réessayez.');
  }
}

async function loadUserLikes() {
  if (!isUserLoggedIn()) return;

  try {
    const response = await fetch(`${API_BASE}/likes.php?user_id=me`);
    if (response.ok) {
      const data = await response.json();
      data.forEach(like => state.userLikes.add(like.actualite_id));
    }
  } catch (error) {
    console.error('Erreur chargement likes:', error);
  }
}

// ============================================================
// ENGAGEMENT : PARTAGE
// ============================================================

function handleShare(e, card) {
  e.preventDefault();
  const articleId = card.dataset.id;
  const articleTitle = card.querySelector('.article-title').textContent;
  const articleUrl = `${window.location.origin}/public/actualite-detail.html?id=${articleId}`;

  // Stocker les données pour la modal
  window.currentShareData = {
    id: articleId,
    title: articleTitle,
    url: articleUrl
  };

  // Afficher modal
  const modal = document.getElementById('shareModal');
  modal.classList.add('active');

  // Remplir le lien
  document.getElementById('shareLink').value = articleUrl;
}

function handleModalClick(e) {
  const modal = e.currentTarget;

  // Fermer modal
  if (e.target === modal || e.target.closest('.modal-close')) {
    modal.classList.remove('active');
    return;
  }

  // Actions partage
  const shareOption = e.target.closest('.share-option');
  if (shareOption) {
    const platform = shareOption.dataset.share;
    shareToSocial(platform);
  }

  // Copier lien
  if (e.target.id === 'copyLinkBtn') {
    copyShareLink();
  }
}

function shareToSocial(platform) {
  const data = window.currentShareData;
  const text = encodeURIComponent(data.title);
  const url = encodeURIComponent(data.url);

  let shareUrl = '';
  switch (platform) {
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${text} ${url}`;
      break;
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
      break;
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?text=${text}&url=${url}`;
      break;
    case 'email':
      shareUrl = `mailto:?subject=${text}&body=${url}`;
      break;
    case 'copy':
      copyShareLink();
      return;
  }

  if (shareUrl) {
    window.open(shareUrl, '_blank', 'width=600,height=400');
  }
}

function copyShareLink() {
  const input = document.getElementById('shareLink');
  input.select();
  document.execCommand('copy');

  const btn = document.getElementById('copyLinkBtn');
  const original = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-check"></i> Lien copié !';
  setTimeout(() => btn.innerHTML = original, 2000);
}

// ============================================================
// VUE (GRILLE / LISTE)
// ============================================================

function handleViewToggle(e) {
  const btn = e.currentTarget;
  const viewType = btn.dataset.view;

  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const grid = document.getElementById('articlesContainer');
  if (viewType === 'list') {
    grid.classList.add('list-view');
    grid.classList.remove('grid-view');
  } else {
    grid.classList.remove('list-view');
    grid.classList.add('grid-view');
  }
}

// ============================================================
// PAGINATION
// ============================================================

function updatePagination() {
  const total = state.filteredArticles.length;
  const pages = Math.ceil(total / ARTICLES_PER_PAGE);

  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  const pageNumbers = document.getElementById('pageNumbers');

  if (prevBtn) prevBtn.disabled = state.currentPage === 1;
  if (nextBtn) nextBtn.disabled = state.currentPage === pages;

  // Pages numbers
  if (pageNumbers) {
    pageNumbers.innerHTML = '';
    for (let i = 1; i <= pages; i++) {
      if (pages <= 7 || Math.abs(i - state.currentPage) <= 2 || i === 1 || i === pages) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i === state.currentPage) btn.classList.add('active');
        btn.addEventListener('click', () => goToPage(i));
        pageNumbers.appendChild(btn);
      } else if (Math.abs(i - state.currentPage) === 3) {
        pageNumbers.innerHTML += '<span>...</span>';
      }
    }
  }
}

function prevPage() {
  if (state.currentPage > 1) goToPage(state.currentPage - 1);
}

function nextPage() {
  const pages = Math.ceil(state.filteredArticles.length / ARTICLES_PER_PAGE);
  if (state.currentPage < pages) goToPage(state.currentPage + 1);
}

function goToPage(page) {
  state.currentPage = page;
  renderArticles();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================================
// UTILITAIRES
// ============================================================

function showLoading(show) {
  const indicator = document.getElementById('loadingIndicator');
  if (indicator) indicator.style.display = show ? 'inline-flex' : 'none';
}

function showEmptyState(message) {
  const container = document.getElementById('articlesContainer');
  if (container) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">📭</div>
        <div class="empty-state-title">${message}</div>
      </div>
    `;
  }
}

function getRelativeDate(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const seconds = Math.floor((now - date) / 1000);

  if (seconds < 60) return 'À l\'instant';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `il y a ${minutes}m`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `il y a ${hours}h`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `il y a ${days}j`;
  if (days < 30) return `il y a ${Math.floor(days / 7)}sem`;
  if (days < 365) return `il y a ${Math.floor(days / 30)}mois`;
  return `il y a ${Math.floor(days / 365)}ans`;
}

function getCategoryLabel(category) {
  const labels = {
    'resultats': '🏆 Résultats',
    'evenements': '📅 Événements',
    'vie-du-club': '🏢 Vie du Club',
    'honneurs': '👥 Honneurs'
  };
  return labels[category?.toLowerCase()] || category || 'Info';
}

function getCategoryColor(category) {
  const colors = {
    'resultats': '#e63946',
    'evenements': '#f4a261',
    'vie-du-club': '#2ecc71',
    'honneurs': '#3498db'
  };
  return colors[category?.toLowerCase()] || '#8d99ae';
}

function formatNumber(num) {
  if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
  return num.toString();
}

function isUserLoggedIn() {
  return !!localStorage.getItem('auth_token');
}

function redirectToLogin() {
  window.location.href = '/public/auth/login.html?redirect=' + window.location.pathname;
}

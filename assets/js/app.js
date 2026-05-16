/* ============================================================
   app.js — Association Sportive — JS Global
   ============================================================ */

"use strict";

/* ===== TOAST NOTIFICATIONS ===== */
const Toast = (() => {
  let container = null;
  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }
  function show(message, type = 'info', duration = 3500) {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const c = getContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <span class="toast-msg">${message}</span>
      <span class="toast-close" onclick="this.parentElement.remove()">✕</span>`;
    c.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s';
      setTimeout(() => toast.remove(), 400);
    }, duration);
  }
  return { success: m => show(m,'success'), error: m => show(m,'error'), warning: m => show(m,'warning'), info: m => show(m,'info') };
})();

/* ===== MODAL ===== */
const Modal = (() => {
  function open(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function close(id) {
    const el = id ? document.getElementById(id) : document.querySelector('.modal-overlay.active');
    if (el) el.classList.remove('active');
    document.body.style.overflow = '';
  }
  // Close on overlay click
  document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) close();
  });
  // Close on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') close();
  });
  return { open, close };
})();

/* ===== NAVBAR ===== */
(function initNavbar() {
  const ham = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');
  if (ham && navLinks) {
    ham.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      ham.classList.toggle('open');
    });
    // Close on link click
    navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      navLinks.classList.remove('open');
      ham.classList.remove('open');
    }));
  }
  // Highlight current page
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') && a.getAttribute('href').endsWith(path)) a.classList.add('active');
  });
})();

/* ===== SIDEBAR (Admin) ===== */
(function initSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }
  // Active link
  const path = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar-link').forEach(a => {
    if (a.getAttribute('href') && a.getAttribute('href').endsWith(path)) a.classList.add('active');
  });
})();

/* ===== FORM VALIDATION ===== */
const Validator = {
  rules: {
    required: v => v.trim() !== '' || 'Ce champ est obligatoire',
    email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Email invalide',
    minlen: (v, n) => v.trim().length >= n || `Minimum ${n} caractères`,
    maxlen: (v, n) => v.trim().length <= n || `Maximum ${n} caractères`,
    phone: v => /^[\d\s\+\-]{8,15}$/.test(v) || 'Numéro de téléphone invalide',
    match: (v, ref) => v === document.getElementById(ref)?.value || 'Les mots de passe ne correspondent pas',
    min: (v, n) => parseFloat(v) >= n || `Valeur minimale: ${n}`,
  },
  validate(form) {
    let valid = true;
    form.querySelectorAll('[data-rules]').forEach(input => {
      const rules = input.dataset.rules.split('|');
      let error = '';
      for (let rule of rules) {
        const [name, param] = rule.split(':');
        const result = this.rules[name]?.(input.value, param);
        if (result !== true && result !== undefined) { error = result; break; }
      }
      const errEl = form.querySelector(`[data-error="${input.name}"]`);
      if (errEl) errEl.textContent = error;
      input.classList.toggle('error', !!error);
      if (error) valid = false;
    });
    return valid;
  }
};

/* ===== API HELPER (AJAX → PHP) ===== */
const API = {
  async request(url, method = 'GET', data = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (data && method !== 'GET') opts.body = JSON.stringify(data);
    try {
      const res = await fetch(url, opts);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (e) {
      console.error('API Error:', e);
      throw e;
    }
  },
  get: (url) => API.request(url),
  post: (url, data) => API.request(url, 'POST', data),
  put: (url, data) => API.request(url, 'PUT', data),
  delete: (url) => API.request(url, 'DELETE'),
};

/* ===== DATA TABLE ===== */
class DataTable {
  constructor(tableId, options = {}) {
    this.table = document.getElementById(tableId);
    if (!this.table) return;
    this.tbody = this.table.querySelector('tbody');
    this.options = { searchable: true, sortable: true, pageSize: 10, ...options };
    this.data = [];
    this.filtered = [];
    this.currentPage = 1;
    this.sortCol = null;
    this.sortDir = 'asc';
    this._init();
  }
  _init() {
    if (this.options.searchable) {
      const searchInput = document.getElementById(`${this.table.id}-search`);
      if (searchInput) searchInput.addEventListener('input', e => this.search(e.target.value));
    }
  }
  loadFromTable() {
    this.data = Array.from(this.tbody?.querySelectorAll('tr') || []).map(row => ({
      el: row, text: row.textContent.toLowerCase()
    }));
    this.filtered = [...this.data];
    this.render();
  }
  search(query) {
    const q = query.toLowerCase();
    this.filtered = q ? this.data.filter(r => r.text.includes(q)) : [...this.data];
    this.currentPage = 1;
    this.render();
  }
  render() {
    const start = (this.currentPage - 1) * this.options.pageSize;
    const end = start + this.options.pageSize;
    const page = this.filtered.slice(start, end);
    if (this.tbody) {
      this.tbody.innerHTML = '';
      page.forEach(r => this.tbody.appendChild(r.el));
    }
    this._renderPagination();
    const info = document.getElementById(`${this.table.id}-info`);
    if (info) info.textContent = `${this.filtered.length} résultat(s)`;
  }
  _renderPagination() {
    const pag = document.getElementById(`${this.table.id}-pagination`);
    if (!pag) return;
    const pages = Math.ceil(this.filtered.length / this.options.pageSize);
    pag.innerHTML = '';
    if (pages <= 1) return;
    const addBtn = (label, page, disabled = false) => {
      const btn = document.createElement('button');
      btn.className = `page-btn${page === this.currentPage ? ' active' : ''}`;
      btn.textContent = label;
      btn.disabled = disabled;
      btn.addEventListener('click', () => { this.currentPage = page; this.render(); });
      pag.appendChild(btn);
    };
    addBtn('‹', this.currentPage - 1, this.currentPage === 1);
    for (let i = 1; i <= pages; i++) addBtn(i, i);
    addBtn('›', this.currentPage + 1, this.currentPage === pages);
  }
  goto(page) { this.currentPage = page; this.render(); }
}

/* ===== CONFIRM DIALOG ===== */
function confirmAction(message, onConfirm) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay active';
  overlay.innerHTML = `
    <div class="modal" style="max-width:400px">
      <div class="modal-header"><span class="modal-title">Confirmation</span></div>
      <div class="modal-body"><p style="color:var(--light)">${message}</p></div>
      <div class="modal-footer">
        <button class="btn btn-outline btn-sm" id="confirmNo">Annuler</button>
        <button class="btn btn-danger btn-sm" id="confirmYes">Confirmer</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  overlay.querySelector('#confirmNo').addEventListener('click', () => { overlay.remove(); document.body.style.overflow = ''; });
  overlay.querySelector('#confirmYes').addEventListener('click', () => { overlay.remove(); document.body.style.overflow = ''; onConfirm(); });
  document.body.style.overflow = 'hidden';
}

/* ===== SCROLL ANIMATIONS ===== */
(function initScrollAnimations() {
  if (!('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('animate-up'); observer.unobserve(e.target); }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.card, .kpi-card, .activity-card, .news-card, .stat-card').forEach(el => observer.observe(el));
})();

/* ===== COUNTER ANIMATION ===== */
function animateCounter(el, target, duration = 2000) {
  const start = performance.now();
  const update = (time) => {
    const progress = Math.min((time - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 4);
    el.textContent = Math.floor(ease * target);
    if (progress < 1) requestAnimationFrame(update);
    else el.textContent = target;
  };
  requestAnimationFrame(update);
}

(function initCounters() {
  const counters = document.querySelectorAll('[data-counter]');
  if (!counters.length) return;
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        animateCounter(e.target, parseInt(e.target.dataset.counter));
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.5 });
  counters.forEach(el => io.observe(el));
})();

/* ===== FORM SUBMIT HANDLER ===== */
document.querySelectorAll('form[data-ajax]').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (!Validator.validate(form)) return;
    const btn = form.querySelector('[type=submit]');
    const origText = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;display:inline-block"></span>'; }
    const fd = new FormData(form);
    const data = Object.fromEntries(fd.entries());
    try {
      const res = await API.post(form.action || form.dataset.ajax, data);
      if (res.success) {
        Toast.success(res.message || 'Opération réussie');
        if (res.redirect) setTimeout(() => window.location.href = res.redirect, 1000);
        if (form.dataset.reset !== 'false') form.reset();
      } else {
        Toast.error(res.message || 'Une erreur est survenue');
      }
    } catch {
      Toast.error('Erreur de connexion au serveur');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    }
  });
});

/* ===== ACTIVE NAV AUTO-DETECT ===== */
(function() {
  const current = location.href;
  document.querySelectorAll('nav a').forEach(a => {
    if (a.href === current || a.href === current.replace(/\/$/, '')) a.classList.add('active');
  });
})();

/* ===== DROPDOWN ===== */
document.addEventListener('click', function (e) {
    // Utilise .closest() pour vérifier si le clic est sur un bouton de dropdown ou à l'intérieur d'un dropdown
    const dropdownToggle = e.target.closest('.nav-link-dropdown-toggle');
    const dropdownContainer = e.target.closest('.nav-item-dropdown');

    // Ferme tous les autres dropdowns ouverts
    document.querySelectorAll('.nav-item-dropdown.open').forEach(openDropdown => {
        if (!dropdownContainer || openDropdown !== dropdownContainer) {
            openDropdown.classList.remove('open');
        }
    });

    // Si un bouton de dropdown a été cliqué, ouvre/ferme son parent
    if (dropdownToggle) {
        e.preventDefault();
        dropdownToggle.closest('.nav-item-dropdown').classList.toggle('open');
    }
});


/* ===== EXPORT / PRINT HELPERS ===== */
const Export = {
  csv(data, filename = 'export.csv') {
    if (!data.length) return;
    const headers = Object.keys(data[0]);
    const rows = [headers, ...data.map(r => headers.map(h => `"${(r[h] ?? '').toString().replace(/"/g, '""')}"`))];
    const blob = new Blob([rows.map(r => r.join(',')).join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = filename;
    a.click(); URL.revokeObjectURL(url);
  },
  print(selector) {
    const content = document.querySelector(selector);
    if (!content) return;
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>Print</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:8px}th{background:#f4f4f4}</style></head><body>${content.innerHTML}</body></html>`);
    w.document.close(); w.print(); w.close();
  }
};

/* ===== GLOBAL EXPOSE ===== */
window.Toast = Toast;
window.Modal = Modal;
window.Validator = Validator;
window.API = API;
window.DataTable = DataTable;
window.confirmAction = confirmAction;
window.Export = Export;
window.animateCounter = animateCounter;

/* ===== ADMIN NOTIFICATIONS ===== */
const AdminNotif = (() => {
  const DEMO_NOTIFS = [
    { id:1, type:'message_nouveau', titre:'Nouveau message', message:'Karim Benali vous a écrit.', lien:'messages.html', lu:0, created_at:'Il y a 5 min' },
    { id:2, type:'inscription',     titre:'Nouvelle inscription', message:'Omar Hamidi (participant) vient de s\'inscrire.', lien:'membres.html', lu:0, created_at:'Il y a 1h' },
    { id:3, type:'session_rappel',  titre:'Session demain', message:'Entraînement Football prévu demain 09h.', lien:'sessions.html', lu:1, created_at:'Hier' },
  ];
  const ICONS = { message_nouveau:'📬', inscription:'👤', session_rappel:'📅', cotisation:'💰', default:'🔔' };

  function render() {
    const list = document.getElementById('notifList');
    if (!list) return;
    const unread = DEMO_NOTIFS.filter(n => !n.lu).length;
    const badge = document.getElementById('notifCount');
    if (badge) { badge.textContent = unread; badge.style.display = unread ? '' : 'none'; }
    list.innerHTML = DEMO_NOTIFS.map(n => `
      <div style="padding:.8rem 1rem;border-bottom:1px solid rgba(255,255,255,.05);cursor:pointer;background:${n.lu?'transparent':'rgba(230,57,70,.04)'};transition:var(--transition)" onclick="AdminNotif.read(${n.id})">
        <div style="display:flex;gap:.7rem;align-items:flex-start">
          <span style="font-size:1.1rem;flex-shrink:0">${ICONS[n.type]||ICONS.default}</span>
          <div style="flex:1">
            <div style="font-size:.82rem;font-weight:${n.lu?'600':'700'};color:var(--white)">${n.titre}</div>
            <div style="font-size:.75rem;color:var(--gray);margin-top:.1rem">${n.message}</div>
            <div style="font-size:.7rem;color:rgba(255,255,255,.3);margin-top:.2rem">${n.created_at}</div>
          </div>
          ${!n.lu ? '<div style="width:7px;height:7px;border-radius:50%;background:var(--primary);margin-top:4px;flex-shrink:0"></div>' : ''}
        </div>
      </div>`).join('') || '<div style="padding:1.5rem;text-align:center;color:var(--gray);font-size:.85rem">Aucune notification</div>';
  }

  function read(id) {
    const n = DEMO_NOTIFS.find(x => x.id === id);
    if (n) { n.lu = 1; render(); if(n.lien) window.location.href = n.lien; }
  }

  function markAllRead() {
    DEMO_NOTIFS.forEach(n => n.lu = 1);
    render();
    const dd = document.getElementById('notifDropdown');
    if (dd) dd.style.display = 'none';
  }

  return { render, read, markAllRead };
})();

window.AdminNotif = AdminNotif;
window.markAllRead = () => AdminNotif.markAllRead();

function toggleNotif() {
  const dd = document.getElementById('notifDropdown');
  if (!dd) return;
  const open = dd.style.display !== 'none';
  dd.style.display = open ? 'none' : 'block';
  if (!open) AdminNotif.render();
}

// Close notif on outside click
document.addEventListener('click', e => {
  const wrap = document.getElementById('notifWrap');
  if (wrap && !wrap.contains(e.target)) {
    const dd = document.getElementById('notifDropdown');
    if (dd) dd.style.display = 'none';
  }
});

// Auto-init admin notifications on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('notifCount')) AdminNotif.render();
  // Show admin name from sessionStorage
  const name = sessionStorage.getItem('user_prenom');
  const nameEl = document.getElementById('adminName');
  if (name && nameEl) nameEl.textContent = name;
});

/* ===== SESSION STORAGE AUTH HELPER ===== */
const Auth = {
  save(user) { Object.entries(user).forEach(([k,v]) => sessionStorage.setItem('user_'+k, v)); },
  get(key) { return sessionStorage.getItem('user_'+key); },
  clear() { Object.keys(sessionStorage).filter(k=>k.startsWith('user_')).forEach(k=>sessionStorage.removeItem(k)); },
  isLoggedIn() { return !!sessionStorage.getItem('user_id'); },
  role() { return sessionStorage.getItem('user_role') || 'visiteur'; },
  niveau() { return parseInt(sessionStorage.getItem('user_niveau_acces')||'0'); },
};
window.Auth = Auth;

/* ===== DYNAMIC AUTH NAVBAR (CLIENT-SIDE) ===== */
(function initAuthNavbar() {
    const authSection = document.getElementById('nav-auth-section');
    if (!authSection) return;

    if (Auth.isLoggedIn()) {
        const prenom = Auth.get('prenom') || 'Membre';
        const role = Auth.get('role');

        const profileUrl = (role === 'admin' || role === 'coach')
            ? '/public/admin/profil.html'
            : '/public/espace-membre.html';

        const dashboardLink = (role === 'admin' || role === 'coach')
            ? `<li><a href="/public/admin/dashboard.html"><i class="fas fa-tachometer-alt" style="width:14px"></i> Tableau de bord</a></li>`
            : '';

        const dropdownHTML = `
            <a href="#" class="nav-link-dropdown-toggle">
                👤 Bonjour, ${prenom} <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 0.3rem;"></i>
            </a>
            <ul class="dropdown-menu">
                <li><a href="${profileUrl}"><i class="fas fa-user-circle" style="width:14px"></i> Mon Profil</a></li>
                ${dashboardLink}
                <li><hr class="dropdown-divider"></li>
                <li><a href="/public/auth/logout.php" style="color: var(--danger);"><i class="fas fa-sign-out-alt" style="width:14px"></i> Déconnexion</a></li>
            </ul>
        `;

        // Ajoute la classe au li parent et injecte le HTML
        authSection.classList.add('nav-item-dropdown');
        authSection.innerHTML = dropdownHTML;

    } else {
        // L'utilisateur n'est pas connecté, s'assurer que le bouton de connexion est présent
        authSection.innerHTML = `<a href="/public/auth/login.html" class="nav-cta">Connexion</a>`;
        authSection.classList.remove('nav-item-dropdown', 'open');
    }
})();

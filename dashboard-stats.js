/**
 * public/admin/actualites/dashboard-stats.js
 * Gère l'affichage des statistiques sur le tableau de bord des actualités.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Cibles des éléments HTML pour les statistiques
    const statTargets = {
        brouillons: document.getElementById('statBrouillons'),
        publies: document.getElementById('statPublies'),
        programmes: document.getElementById('statProgrammes'),
        archives: document.getElementById('statArchives'),
        vues: document.getElementById('statVues'),
        likes: document.getElementById('statLikes'),
    };

    // Cibles pour les listes d'articles
    const listTargets = {
        recent: document.getElementById('recentArticlesList'),
        trending: document.getElementById('trendingArticlesList'),
        mostViewed: document.getElementById('mostViewedArticlesList'),
        scheduled: document.getElementById('scheduledArticlesList'),
    };

    /**
     * Fonction pour animer un compteur de 0 à la valeur cible.
     * @param {HTMLElement} el - L'élément HTML à animer.
     * @param {number} targetValue - La valeur finale.
     */
    function animateCounter(el, targetValue) {
        if (!el) return;
        const duration = 1500; // en ms
        let start = 0;
        const stepTime = Math.abs(Math.floor(duration / targetValue));

        const timer = setInterval(() => {
            start += 1;
            el.textContent = start;
            if (start === targetValue) {
                clearInterval(timer);
            }
        }, stepTime);

        if (targetValue === 0) {
            el.textContent = 0;
        }
    }

    /**
     * Crée et retourne un élément de liste pour un article.
     * @param {object} article - L'objet article.
     * @param {string[]} fields - Les champs à afficher.
     * @returns {HTMLLIElement}
     */
    function createArticleListItem(article, fields = ['vues', 'likes_count']) {
        const li = document.createElement('li');
        li.className = 'dashboard-list-item';

        let statsHtml = '';
        if (fields.includes('vues') && article.vues !== undefined) {
            statsHtml += `<span class="stat-item">👁️ ${article.vues.toLocaleString()}</span>`;
        }
        if (fields.includes('likes_count') && article.likes_count !== undefined) {
            statsHtml += `<span class="stat-item">❤️ ${article.likes_count.toLocaleString()}</span>`;
        }
        if (fields.includes('scheduled_at') && article.scheduled_at) {
            const date = new Date(article.scheduled_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
            statsHtml += `<span class="stat-item">📅 ${date}</span>`;
        }

        li.innerHTML = `
            <a href="editer.html?id=${article.id}">${article.titre}</a>
            <div class="item-stats">${statsHtml}</div>
        `;
        return li;
    }

    /**
     * Récupère les statistiques depuis l'API et met à jour le DOM.
     */
    async function fetchAndDisplayStats() {
        try {
            const response = await fetch('../../api/actualites/stats.php');
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            const result = await response.json();

            if (result.success) {
                const { data } = result;

                // Mettre à jour les cartes de statistiques
                animateCounter(statTargets.brouillons, data.stats_by_status.brouillon);
                animateCounter(statTargets.publies, data.stats_by_status.publie);
                animateCounter(statTargets.archives, data.stats_by_status.archive);
                animateCounter(statTargets.programmes, data.scheduled.length);
                animateCounter(statTargets.vues, data.engagement.total_views);
                animateCounter(statTargets.likes, data.engagement.total_likes);

                // Mettre à jour les listes d'articles
                if (listTargets.recent) {
                    listTargets.recent.innerHTML = '';
                    data.recent_articles.forEach(article => {
                        listTargets.recent.appendChild(createArticleListItem(article, ['vues', 'likes_count']));
                    });
                }
                if (listTargets.trending) {
                    listTargets.trending.innerHTML = '';
                    data.trending.forEach(article => {
                        listTargets.trending.appendChild(createArticleListItem(article, ['likes_count', 'vues']));
                    });
                }
                if (listTargets.mostViewed) {
                    listTargets.mostViewed.innerHTML = '';
                    data.most_viewed.forEach(article => {
                        listTargets.mostViewed.appendChild(createArticleListItem(article, ['vues']));
                    });
                }
                if (listTargets.scheduled) {
                    listTargets.scheduled.innerHTML = '';
                    data.scheduled.forEach(article => {
                        listTargets.scheduled.appendChild(createArticleListItem(article, ['scheduled_at']));
                    });
                }
            } else {
                console.error('L\'API a retourné une erreur:', result.message);
            }
        } catch (error) {
            console.error('Impossible de charger les statistiques:', error);
            // Afficher un message d'erreur à l'utilisateur si nécessaire
        }
    }

    // Lancer la récupération des données au chargement de la page
    fetchAndDisplayStats();
});
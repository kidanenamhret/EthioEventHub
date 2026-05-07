/**
 * EthioEvent Hub - Main JavaScript File
 * Version: 2.2 (Advanced Features Bundle)
 */

// ==================== Utility Functions ====================

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getBasePath() {
    const path = window.location.pathname;
    const depth = (path.match(/\//g) || []).length - 1;
    if (path.includes('/pages/')) return '../';
    if (path.includes('/api/')) return '../';
    return '';
}

function showToast(message, type = 'success') {
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    const bgMap = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning', info: 'bg-info' };
    
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
            <div class="toast-header ${bgMap[type]} text-white border-0">
                <i class="fas ${iconMap[type]} me-2"></i>
                <strong class="me-auto">EthioEvent Hub</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body bg-dark text-white border-top border-secondary">
                ${escapeHtml(message)}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function checkPasswordStrength(password) {
    if (!password) return { score: 0, text: '', color: '#6c757d', checks: {} };
    let strength = 0;
    const checks = {
        length: password.length >= 8,
        hasLower: /[a-z]/.test(password),
        hasUpper: /[A-Z]/.test(password),
        hasNumber: /\d/.test(password),
        hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    strength += (checks.length + checks.hasLower + checks.hasUpper + checks.hasNumber + checks.hasSpecial);
    const strengthMap = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const colorMap = ['#dc3545', '#dc3545', '#ffc107', '#17a2b8', '#28a745', '#20c997'];
    return { score: strength, text: strengthMap[strength], color: colorMap[strength], width: (strength / 5) * 100, checks: checks };
}

// ==================== Feature Initializers ====================

function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }
}

function initLiveSearch() {
    const searchInput = document.getElementById('liveSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const resultsContainer = document.getElementById('searchResults');
    if (!searchInput || !resultsContainer) return;
    
    let searchTimeout;
    const basePath = getBasePath();
    
    function performSearch() {
        const searchTerm = searchInput.value;
        const category = categoryFilter ? categoryFilter.value : 0;
        resultsContainer.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';
        
        fetch(`${basePath}api/live_search.php?search=${encodeURIComponent(searchTerm)}&category=${category}`)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    resultsContainer.innerHTML = '<div class="col-12 text-center py-5 text-white-50">No events found</div>';
                    return;
                }
                let html = '';
                data.forEach(event => {
                    const priceDisplay = event.price > 0 ? `ETB ${parseInt(event.price).toLocaleString()}` : 'Free';
                    html += `<div class="col-md-4 mb-4"><div class="card h-100 border-0 event-card"><div class="card-body p-4"><h4 class="text-white h5 mb-3">${escapeHtml(event.title)}</h4><div class="small text-white-50 mb-3"><i class="far fa-calendar-alt me-2"></i> ${event.event_date}</div><div class="d-flex justify-content-between align-items-center"><span class="fw-bold text-white">${priceDisplay}</span><a href="event_details.php?id=${event.id}" class="btn btn-primary btn-sm">Details</a></div></div></div></div>`;
                });
                resultsContainer.innerHTML = html;
            });
    }
    searchInput.addEventListener('input', () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(performSearch, 300); });
}

function initFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) { input.classList.add('is-invalid'); valid = false; }
                else input.classList.remove('is-invalid');
            });
            if (!valid) { e.preventDefault(); showToast('Check required fields', 'error'); }
        });
    });
}

function initScrollToTop() {
    const btn = document.createElement('button');
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    btn.className = 'scroll-to-top';
    btn.style.cssText = 'position:fixed; bottom:30px; right:30px; width:50px; height:50px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#9333ea); color:white; border:none; display:none; z-index:1000;';
    document.body.appendChild(btn);
    window.addEventListener('scroll', () => { btn.style.display = window.scrollY > 300 ? 'block' : 'none'; });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

async function toggleWishlist(eventId, btn) {
    const basePath = getBasePath();
    try {
        const res = await fetch(`${basePath}api/toggle_wishlist.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId })
        });
        const data = await res.json();
        if (data.success) {
            btn.classList.toggle('active');
            showToast(data.action === 'added' ? 'Saved! ❤️' : 'Removed', 'info');
        } else showToast('Login required', 'warning');
    } catch (e) { showToast('Error', 'error'); }
}

function initNotifications() {
    const badge = document.getElementById('notification-badge');
    if (!badge) return;
    const check = () => {
        fetch(`${getBasePath()}api/get_notifications.php`)
            .then(res => res.json())
            .then(data => {
                const unread = (data.notifications || []).filter(n => n.is_read == 0).length;
                badge.textContent = unread;
                badge.classList.toggle('d-none', unread === 0);
            });
    };
    check(); setInterval(check, 30000);
}

function initCounters() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute('data-target');
                let count = 0;
                const timer = setInterval(() => {
                    count += Math.ceil(target / 50);
                    if (count >= target) { counter.innerText = target; clearInterval(timer); }
                    else counter.innerText = count;
                }, 30);
                observer.unobserve(counter);
            }
        });
    });
    document.querySelectorAll('.counter').forEach(c => observer.observe(c));
}

function initDynamicCategories() {
    const filters = document.querySelectorAll('.category-filter-btn');
    const container = document.getElementById('featured-events-container');
    if (!filters.length || !container) return;
    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            const catId = btn.getAttribute('data-id');
            filters.forEach(f => f.classList.remove('active', 'btn-primary'));
            btn.classList.add('active', 'btn-primary');
            container.style.opacity = '0.5';
            fetch(`${getBasePath()}api/get_featured_events.php?category_id=${catId}`)
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = data.length ? '' : '<div class="col-12 text-center py-5">No events</div>';
                    data.forEach(e => {
                        container.insertAdjacentHTML('beforeend', `<div class="col-md-4 mb-4"><div class="featured-card p-4"><h5 class="text-white">${escapeHtml(e.title)}</h5><a href="pages/event_details.php?id=${e.id}" class="btn btn-primary btn-sm mt-3">Details</a></div></div>`);
                    });
                    container.style.opacity = '1';
                });
        });
    });
}

// ==================== Advanced Systems ====================

async function voteReview(reviewId) {
    try {
        const res = await fetch(`${getBasePath()}api/vote_review.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `review_id=${reviewId}`
        });
        const data = await res.json();
        if (data.success) location.reload();
        else showToast(data.message || 'Error', 'error');
    } catch (e) { console.error(e); }
}

function shareOnFacebook() { window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank', 'width=600,height=400'); }
function shareOnTwitter(title) { window.open(`https://twitter.com/intent/tweet?text=${title}&url=${encodeURIComponent(window.location.href)}`, '_blank', 'width=600,height=400'); }
function shareOnWhatsApp(title) { window.open(`https://api.whatsapp.com/send?text=${title}%20${encodeURIComponent(window.location.href)}`, '_blank'); }
function copyEventLink() { navigator.clipboard.writeText(window.location.href).then(() => showToast('Link copied!')); }

// ==================== Global Exports ====================

window.toggleWishlist = toggleWishlist;
window.showToast = showToast;
window.voteReview = voteReview;
window.shareOnFacebook = shareOnFacebook;
window.shareOnTwitter = shareOnTwitter;
window.shareOnWhatsApp = shareOnWhatsApp;
window.copyEventLink = copyEventLink;

/**
 * Theme Management System
 */
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    const icon = document.querySelector('.theme-toggle i');
    if (icon) icon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
}

function toggleDarkMode() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update toggle icon if it exists
    const icon = document.querySelector('.theme-toggle i');
    if (icon) {
        icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
    }
}

window.toggleDarkMode = toggleDarkMode;

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initNavbarScroll();
    initLiveSearch();
    initFormValidation();
    initScrollToTop();
    initNotifications();
    initCounters();
    initDynamicCategories();
});
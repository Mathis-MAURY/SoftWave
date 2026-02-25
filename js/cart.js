// ============================================
// SoftWave – Panier (cart.js)
// Stockage : sessionStorage (session en cours)
// ============================================

'use strict';

// ── État du panier ──────────────────────────
let cart = loadCart();

// ── Éléments DOM ───────────────────────────
const cartBtn      = document.getElementById('cartBtn');
const cartCount    = document.getElementById('cartCount');
const cartOverlay  = document.getElementById('cartOverlay');
const cartDrawer   = document.getElementById('cartDrawer');
const cartClose    = document.getElementById('cartClose');
const cartBody     = document.getElementById('cartBody');
const cartEmpty    = document.getElementById('cartEmpty');
const cartFooter   = document.getElementById('cartFooter');
const cartSubtitle = document.getElementById('cartSubtitle');
const cartSubtotal = document.getElementById('cartSubtotal');
const cartTVA      = document.getElementById('cartTVA');
const cartTotal    = document.getElementById('cartTotal');
const checkoutBtn  = document.getElementById('checkoutBtn');

// ── Initialisation ──────────────────────────
renderCart();
bindAddToCartButtons();

// ── Ouvrir / Fermer le drawer ───────────────
cartBtn?.addEventListener('click', openCart);
cartClose?.addEventListener('click', closeCart);
cartOverlay?.addEventListener('click', closeCart);

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCart();
});

function openCart() {
    cartDrawer.classList.add('open');
    cartOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCart() {
    cartDrawer.classList.remove('open');
    cartOverlay.classList.remove('open');
    document.body.style.overflow = '';
}

// ── Boutons "Ajouter au panier" ─────────────
function bindAddToCartButtons() {
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('[data-id]');
            if (!card) return;

            const product = {
                id:    card.dataset.id,
                name:  card.dataset.name,
                price: parseFloat(card.dataset.price),
                icon:  card.dataset.icon,
            };

            addToCart(product);
            animateAddButton(btn);
            openCart();
        });
    });
}

function animateAddButton(btn) {
    const original = btn.innerHTML;
    btn.classList.add('added');
    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Ajouté !`;
    btn.disabled = true;

    setTimeout(() => {
        btn.classList.remove('added');
        btn.innerHTML = original;
        btn.disabled = false;
    }, 1800);
}

// ── Logique panier ──────────────────────────
function addToCart(product) {
    const existing = cart.find(i => i.id === product.id);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ ...product, qty: 1 });
    }
    saveCart();
    renderCart();
}

function removeFromCart(id) {
    const item = cartBody.querySelector(`[data-cart-id="${id}"]`);
    if (item) {
        item.style.animation = 'none';
        item.style.transition = 'opacity .2s, transform .2s';
        item.style.opacity = '0';
        item.style.transform = 'translateX(30px)';
        setTimeout(() => {
            cart = cart.filter(i => i.id !== id);
            saveCart();
            renderCart();
        }, 200);
    } else {
        cart = cart.filter(i => i.id !== id);
        saveCart();
        renderCart();
    }
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty = Math.max(1, item.qty + delta);
    saveCart();
    renderCart();
}

// ── Rendu du panier ─────────────────────────
function renderCart() {
    const total     = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const totalItems = cart.reduce((s, i) => s + i.qty, 0);
    const tva       = total * 0.20;
    const ttc       = total + tva;

    // Badge compteur
    if (cartCount) {
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }

    // Sous-titre
    if (cartSubtitle) {
        cartSubtitle.textContent = totalItems === 0
            ? '0 article'
            : `${totalItems} article${totalItems > 1 ? 's' : ''}`;
    }

    // Vider le body (sauf cart-empty)
    Array.from(cartBody.children).forEach(child => {
        if (!child.classList.contains('cart-empty')) child.remove();
    });

    if (cart.length === 0) {
        cartEmpty.style.display = 'flex';
        cartFooter.style.display = 'none';
        return;
    }

    cartEmpty.style.display = 'none';
    cartFooter.style.display = 'block';

    // Rendre les items
    cart.forEach(item => {
        const el = document.createElement('div');
        el.className = 'cart-item';
        el.dataset.cartId = item.id;
        el.innerHTML = `
            <div class="cart-item__icon">${item.icon}</div>
            <div class="cart-item__info">
                <div class="cart-item__name">${escHtml(item.name)}</div>
                <div class="cart-item__meta">Licence perpétuelle</div>
                <div class="cart-item__controls">
                    <button class="qty-btn" data-action="dec" data-id="${item.id}">−</button>
                    <span class="qty-display">${item.qty}</span>
                    <button class="qty-btn" data-action="inc" data-id="${item.id}">+</button>
                    <button class="cart-item__remove" data-id="${item.id}">Retirer</button>
                </div>
            </div>
            <div class="cart-item__price">${formatPrice(item.price * item.qty)}</div>
        `;
        cartBody.appendChild(el);
    });

    // Événements sur les nouveaux boutons
    cartBody.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const delta = btn.dataset.action === 'inc' ? 1 : -1;
            updateQty(btn.dataset.id, delta);
        });
    });

    cartBody.querySelectorAll('.cart-item__remove').forEach(btn => {
        btn.addEventListener('click', () => removeFromCart(btn.dataset.id));
    });

    // Totaux
    if (cartSubtotal) cartSubtotal.textContent = formatPrice(total);
    if (cartTVA)      cartTVA.textContent      = formatPrice(tva);
    if (cartTotal)    cartTotal.textContent    = formatPrice(ttc);
}

// ── Checkout ────────────────────────────────
checkoutBtn?.addEventListener('click', () => {
    if (cart.length === 0) return;
    // Rediriger vers la page de paiement
    window.location.href = 'checkout.php';
});

// ── Persistance sessionStorage ──────────────
function saveCart() {
    try { sessionStorage.setItem('sw_cart', JSON.stringify(cart)); } catch {}
}

function loadCart() {
    try { return JSON.parse(sessionStorage.getItem('sw_cart')) || []; } catch { return []; }
}

// ── Utilitaires ─────────────────────────────
function formatPrice(n) {
    return n.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
}

function escHtml(str) {
    return str.replace(/[&<>"']/g, m =>
        ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])
    );
}
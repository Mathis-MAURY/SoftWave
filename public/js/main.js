// ============================================
// SoftWave – JavaScript principal
// ============================================

'use strict';

// ── Header scroll ──────────────────────────
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 50);
}, { passive: true });

// ── Menu burger mobile ─────────────────────
const burgerBtn = document.getElementById('burgerBtn');
const navLinks = document.querySelector('.nav__links');

burgerBtn?.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    burgerBtn.classList.toggle('active');
});

// Fermer le menu au clic sur un lien
document.querySelectorAll('.nav__link').forEach(link => {
    link.addEventListener('click', () => navLinks.classList.remove('open'));
});

// ── Scroll reveal ──────────────────────────
const revealElements = document.querySelectorAll(
    '.product-card, .feature-card, .testimonial, .contact__info, .contact__form, .section__header'
);

revealElements.forEach(el => el.classList.add('reveal'));

const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
            setTimeout(() => entry.target.classList.add('visible'), i * 60);
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

revealElements.forEach(el => revealObserver.observe(el));

// ── Filtre produits ─────────────────────────
const filterBtns = document.querySelectorAll('.filter-btn');
const productCards = document.querySelectorAll('.product-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;

        productCards.forEach(card => {
            const show = filter === 'all' || card.dataset.category === filter;
            card.style.opacity = '0';
            card.style.transform = 'scale(.95)';

            setTimeout(() => {
                card.style.display = show ? 'flex' : 'none';
                if (show) {
                    requestAnimationFrame(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'scale(1)';
                        card.style.transition = 'opacity .3s ease, transform .3s ease';
                    });
                }
            }, 150);
        });
    });
});

// ── Compteur caractères textarea ────────────
const messageArea = document.getElementById('message');
const charCount = document.getElementById('charCount');

messageArea?.addEventListener('input', () => {
    const len = messageArea.value.length;
    charCount.textContent = len;
    charCount.style.color = len > 4500 ? 'var(--c-red)' : 'var(--c-muted)';
});

// ── Validation formulaire contact ──────────
const contactForm = document.getElementById('contactForm');

function validateField(input, errorEl) {
    const val = input.value.trim();
    let msg = '';

    if (input.type === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!val) msg = 'Email requis';
        else if (!emailRegex.test(val)) msg = 'Email invalide';
    } else if (input.tagName === 'SELECT') {
        if (!val) msg = 'Veuillez choisir un sujet';
    } else if (input.id === 'message') {
        if (!val || val.length < 20) msg = 'Message trop court (20 car. min)';
        else if (val.length > 5000) msg = 'Message trop long (5000 car. max)';
    } else {
        if (!val || val.length < 2) msg = `${input.placeholder || 'Ce champ'} est requis`;
    }

    errorEl.textContent = msg;
    input.classList.toggle('error', !!msg);
    input.classList.toggle('valid', !msg && val.length > 0);
    return !msg;
}

['name', 'email', 'subject', 'message'].forEach(id => {
    const input = document.getElementById(id);
    const error = document.getElementById(`${id}Error`);
    if (input && error) {
        input.addEventListener('blur', () => validateField(input, error));
        input.addEventListener('input', () => {
            if (input.classList.contains('error')) validateField(input, error);
        });
    }
});

contactForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fields = [
        { input: document.getElementById('name'), error: document.getElementById('nameError') },
        { input: document.getElementById('email'), error: document.getElementById('emailError') },
        { input: document.getElementById('subject'), error: document.getElementById('subjectError') },
        { input: document.getElementById('message'), error: document.getElementById('messageError') },
    ];

    const isValid = fields.every(f => validateField(f.input, f.error));

    const privacyCheck = document.getElementById('privacy');
    if (!privacyCheck.checked) {
        showFeedback('Veuillez accepter la politique de confidentialité.', 'error');
        return;
    }

    if (!isValid) {
        fields[0].input.focus();
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn__text');
    const btnLoader = submitBtn.querySelector('.btn__loader');

    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';

    try {
        const formData = new FormData(contactForm);

        // En mode demo sans PHP, simuler une réponse positive
        await new Promise(r => setTimeout(r, 1200));

        // En production, décommenter ce bloc et commenter la ligne ci-dessus :
        /*
        const response = await fetch('/softwave/contact/process.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Erreur serveur');
        */

        contactForm.reset();
        charCount.textContent = '0';
        document.querySelectorAll('.form__input').forEach(i => {
            i.classList.remove('valid', 'error');
        });
        showFeedback('✓ Message envoyé avec succès ! Nous vous répondrons sous 24h.', 'success');

    } catch (err) {
        showFeedback('Une erreur est survenue. Veuillez réessayer.', 'error');
    } finally {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

function showFeedback(msg, type) {
    const fb = document.getElementById('formFeedback');
    fb.textContent = msg;
    fb.className = `form__feedback ${type}`;
    fb.scrollIntoView({ behavior: 'smooth', block: 'center' });
    if (type === 'success') {
        setTimeout(() => { fb.className = 'form__feedback'; }, 6000);
    }
}

// ── Smooth scroll nav ──────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
        const target = document.querySelector(anchor.getAttribute('href'));
        if (target) {
            e.preventDefault();
            const offset = 80;
            window.scrollTo({
                top: target.getBoundingClientRect().top + window.scrollY - offset,
                behavior: 'smooth'
            });
        }
    });
});

console.log('%cSoftWave ⬡', 'color:#3d7fff;font-size:1.5rem;font-weight:800');
console.log('%cSite vitrine v1.0 | PHP + MySQL + HTML/CSS/JS', 'color:#7a8599');

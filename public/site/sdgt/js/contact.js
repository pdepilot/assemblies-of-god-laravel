/**
 * SDTG Contact Page
 */
(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initPageLoad() {
        document.body.classList.add('contact-page');
        requestAnimationFrame(() => document.body.classList.add('is-loaded'));
    }

    function initNavbar() {
        const navbar = document.getElementById('navbar');
        const toggle = document.getElementById('navToggle');
        const menu = document.getElementById('navMenu');
        const backdrop = document.getElementById('navBackdrop');
        if (!navbar || !toggle || !menu) return;

        const closeMenu = () => {
            menu.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('no-scroll');
            if (backdrop) backdrop.classList.remove('active');
        };

        const openMenu = () => {
            menu.classList.add('open');
            toggle.classList.add('active');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('no-scroll');
            if (backdrop) backdrop.classList.add('active');
        };

        window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 60), { passive: true });
        navbar.classList.toggle('scrolled', window.scrollY > 60);
        toggle.addEventListener('click', () => menu.classList.contains('open') ? closeMenu() : openMenu());
        if (backdrop) backdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
        document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', closeMenu));
    }

    function initVideoLogos() {
        document.querySelectorAll('.logo-video__el').forEach(video => {
            video.muted = true;
            video.playsInline = true;
            const play = () => video.play().catch(() => {});
            if (video.readyState >= 2) play();
            else video.addEventListener('loadeddata', play, { once: true });
        });
    }

    function initParticles(id) {
        const canvas = document.getElementById(id);
        if (!canvas || prefersReducedMotion) return;
        const ctx = canvas.getContext('2d');
        let particles = [];

        const resize = () => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            particles = Array.from({ length: Math.min(40, Math.floor(canvas.width / 30)) }, () => ({
                x: Math.random() * canvas.width, y: Math.random() * canvas.height,
                r: Math.random() * 2 + 0.5, dx: (Math.random() - 0.5) * 0.3, dy: (Math.random() - 0.5) * 0.3,
                alpha: Math.random() * 0.4 + 0.15
            }));
        };

        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(251, 254, 6, ${p.alpha})`;
                ctx.fill();
                p.x += p.dx; p.y += p.dy;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            });
            requestAnimationFrame(draw);
        };

        resize(); draw();
        window.addEventListener('resize', resize);
    }

    function initHeroReveal() {
        document.querySelectorAll('.contact-hero .reveal').forEach((el, i) => {
            setTimeout(() => el.classList.add('is-visible'), 200 + (parseInt(el.dataset.delay, 10) || i * 100));
        });
    }

    function initInquiryCards() {
        const subjectField = document.getElementById('contactSubject');
        document.querySelectorAll('.inquiry-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.inquiry-card').forEach(c => c.classList.remove('is-active'));
                card.classList.add('is-active');
                if (subjectField) subjectField.value = card.dataset.subject || '';
                document.getElementById('contactForm')?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
            });
        });
    }

    function syncFloatFields(form) {
        form.querySelectorAll('select').forEach(field => {
            const sync = () => field.closest('.ct-float')?.classList.toggle('has-value', !!field.value);
            field.addEventListener('change', sync);
            sync();
        });
    }

    function validateForm(form) {
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            const wrap = field.closest('.ct-float');
            if (!wrap) return;
            wrap.classList.remove('is-error');
            const err = wrap.querySelector('.ct-float__error');
            if (err) err.textContent = '';

            if (!field.value.trim()) {
                valid = false;
                wrap.classList.add('is-error');
                if (err) err.textContent = 'This field is required';
            }
            if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                valid = false;
                wrap.classList.add('is-error');
                if (err) err.textContent = 'Enter a valid email';
            }
        });
        return valid;
    }

    function initContactForm() {
        const form = document.getElementById('sdtgContactForm');
        const success = document.getElementById('contactFormSuccess');
        if (!form) return;

        syncFloatFields(form);

        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', () => {
                const wrap = field.closest('.ct-float');
                if (wrap) {
                    wrap.classList.remove('is-error');
                    const err = wrap.querySelector('.ct-float__error');
                    if (err) err.textContent = '';
                }
            });
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (!validateForm(form)) return;

            const payload = Object.fromEntries(new FormData(form));
            form.dataset.lastPayload = JSON.stringify({ ...payload, submitted_at: new Date().toISOString(), type: 'contact' });

            form.hidden = true;
            if (success) {
                success.classList.add('is-visible');
                success.setAttribute('aria-live', 'polite');
            }
        });
    }

    function initPrayerForm() {
        const form = document.getElementById('sdtgPrayerForm');
        const success = document.getElementById('prayerFormSuccess');
        const anonCheck = document.getElementById('prayerAnonymous');
        const nameField = document.getElementById('prayerName');
        if (!form) return;

        syncFloatFields(form);

        anonCheck?.addEventListener('change', () => {
            if (nameField) {
                nameField.disabled = anonCheck.checked;
                nameField.required = !anonCheck.checked;
                if (anonCheck.checked) nameField.value = 'Anonymous';
            }
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (!validateForm(form)) return;

            const payload = Object.fromEntries(new FormData(form));
            form.dataset.lastPayload = JSON.stringify({ ...payload, submitted_at: new Date().toISOString(), type: 'prayer' });

            form.hidden = true;
            if (success) {
                success.classList.add('is-visible');
                success.setAttribute('aria-live', 'polite');
            }
        });
    }

    function initTestimonialSlider() {
        const track = document.getElementById('ctSliderTrack');
        const dotsEl = document.getElementById('ctSliderDots');
        if (!track) return;

        const slides = track.querySelectorAll('.ct-slide');
        let current = 0;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', `Testimonial ${i + 1}`);
            dot.addEventListener('click', () => goTo(i));
            dotsEl?.appendChild(dot);
        });

        const dots = dotsEl?.querySelectorAll('button') || [];

        const goTo = i => {
            slides[current]?.classList.remove('is-active');
            dots[current]?.classList.remove('is-active');
            current = ((i % slides.length) + slides.length) % slides.length;
            slides[current]?.classList.add('is-active');
            dots[current]?.classList.add('is-active');
        };

        document.getElementById('ctSliderPrev')?.addEventListener('click', () => goTo(current - 1));
        document.getElementById('ctSliderNext')?.addEventListener('click', () => goTo(current + 1));
        goTo(0);

        if (!prefersReducedMotion) {
            setInterval(() => goTo(current + 1), 6500);
        }
    }

    function initScrollReveal() {
        if (prefersReducedMotion) {
            document.querySelectorAll('.reveal:not(.contact-hero .reveal)').forEach(el => el.classList.add('is-visible'));
            return;
        }
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('is-visible'), parseInt(entry.target.dataset.delay, 10) || 0);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal:not(.contact-hero .reveal)').forEach(el => observer.observe(el));
    }

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                }
            });
        });
    }

    function initNewsletter() {
        document.getElementById('newsletterForm')?.addEventListener('submit', e => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            form.querySelector('input').value = '';
            setTimeout(() => { btn.textContent = 'Subscribe'; }, 2000);
        });
    }

    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;
        window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 400), { passive: true });
        btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' }));
    }

    document.addEventListener('DOMContentLoaded', () => {
        initPageLoad();
        initNavbar();
        initVideoLogos();
        initParticles('contactHeroParticles');
        initParticles('contactCtaParticles');
        initHeroReveal();
        initInquiryCards();
        initContactForm();
        initPrayerForm();
        initTestimonialSlider();
        initScrollReveal();
        initSmoothScroll();
        initNewsletter();
        initScrollTop();
    });
})();

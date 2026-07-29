/**
 * SDTG Speakers Page — synced with CMS via /api/get-sdtg-speakers.php
 */
(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const API_URL = '../api/get-sdtg-speakers.php';

    const EMPTY_SPEAKERS_DATA = {
        previous: [],
        upcoming: [],
        keynote: null,
        worship: [],
        hosts: [],
        videos: [],
        timeline: [],
        highlights: [],
        stats: null
    };

    let SPEAKERS_DATA = window.SDTG_SPEAKERS_BOOTSTRAP || null;

    function normalizeSpeakersData(raw) {
        if (!raw || typeof raw !== 'object') return null;
        return {
            previous: Array.isArray(raw.previous) ? raw.previous : [],
            upcoming: Array.isArray(raw.upcoming) ? raw.upcoming : [],
            keynote: raw.keynote && typeof raw.keynote === 'object' ? raw.keynote : null,
            worship: Array.isArray(raw.worship) ? raw.worship : [],
            hosts: Array.isArray(raw.hosts) ? raw.hosts : [],
            videos: Array.isArray(raw.videos) ? raw.videos : [],
            timeline: Array.isArray(raw.timeline) ? raw.timeline : [],
            highlights: Array.isArray(raw.highlights) ? raw.highlights : [],
            stats: raw.stats && typeof raw.stats === 'object' ? raw.stats : null
        };
    }

    function loadSpeakersData() {
        if (SPEAKERS_DATA) {
            return Promise.resolve(SPEAKERS_DATA);
        }

        return fetch(API_URL, { credentials: 'same-origin' })
            .then(res => res.json().then(body => {
                if (!res.ok || !body.success) throw new Error(body.message || 'Unable to load speakers.');
                return normalizeSpeakersData(body.data);
            }))
            .then(data => {
                SPEAKERS_DATA = data || EMPTY_SPEAKERS_DATA;
                return SPEAKERS_DATA;
            })
            .catch(() => {
                SPEAKERS_DATA = EMPTY_SPEAKERS_DATA;
                return SPEAKERS_DATA;
            });
    }

    function initPageLoad() {
        document.body.classList.add('speakers-page');
        requestAnimationFrame(() => document.body.classList.add('is-loaded'));
    }

    function initNavbar() {
        const navbar = document.getElementById('navbar');
        const toggle = document.getElementById('navToggle');
        const menu = document.getElementById('navMenu');
        const backdrop = document.getElementById('navBackdrop');
        if (!navbar || !toggle || !menu) return;

        const close = () => {
            menu.classList.remove('open');
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('no-scroll');
            backdrop?.classList.remove('active');
        };

        window.addEventListener('scroll', () => navbar.classList.toggle('scrolled', window.scrollY > 60), { passive: true });
        navbar.classList.toggle('scrolled', window.scrollY > 60);
        toggle.addEventListener('click', () => menu.classList.contains('open') ? close() : (menu.classList.add('open'), toggle.classList.add('active'), toggle.setAttribute('aria-expanded', 'true'), backdrop?.classList.add('active'), document.body.classList.add('no-scroll')));
        backdrop?.addEventListener('click', close);
        document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', close));
    }

    function initVideoLogos() {
        document.querySelectorAll('.logo-video__el').forEach(v => {
            v.muted = true;
            v.playsInline = true;
            const play = () => v.play().catch(() => {});
            v.readyState >= 2 ? play() : v.addEventListener('loadeddata', play, { once: true });
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
            particles = Array.from({ length: 40 }, () => ({
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
        document.querySelectorAll('.spk-hero .reveal').forEach((el, i) => {
            setTimeout(() => el.classList.add('is-visible'), 200 + (parseInt(el.dataset.delay, 10) || i * 100));
        });
    }

    function initCounters() {
        const stats = SPEAKERS_DATA?.stats;
        const mapping = stats ? [
            ['[data-count="180"]', stats.guest_speakers, '+'],
            ['[data-count="45"]', stats.nations, '+'],
            ['[data-count="12"]', stats.crusade_years, ''],
            ['[data-count="95"]', stats.worship_leaders, '+']
        ] : null;

        if (mapping) {
            mapping.forEach(([selector, value, suffix]) => {
                const el = document.querySelector(selector);
                if (el) {
                    el.dataset.count = String(value);
                    if (suffix) el.dataset.suffix = suffix;
                }
            });
        }

        document.querySelectorAll('[data-count]').forEach(el => {
            const observer = new IntersectionObserver(entries => {
                if (entries[0].isIntersecting && !el.dataset.counted) {
                    el.dataset.counted = '1';
                    const target = parseInt(el.dataset.count, 10);
                    const suffix = el.dataset.suffix || '';
                    const start = performance.now();
                    const step = now => {
                        const p = Math.min((now - start) / 2200, 1);
                        el.textContent = Math.floor(target * (1 - Math.pow(1 - p, 3))).toLocaleString() + suffix;
                        if (p < 1) requestAnimationFrame(step);
                        else el.textContent = target.toLocaleString() + suffix;
                    };
                    requestAnimationFrame(step);
                }
            }, { threshold: 0.35 });
            observer.observe(el);
        });
    }

    function buildMarqueeCard(speaker) {
        return `<article class="spk-marquee-card" data-speaker-id="${speaker.id}" tabindex="0" role="button" aria-label="View ${speaker.name}">
            <div class="spk-marquee-card__photo"><img src="${speaker.photo}" alt="${speaker.name}" loading="lazy" width="260" height="260"></div>
            <div class="spk-marquee-card__body">
                <h3 class="spk-marquee-card__name">${speaker.name}</h3>
                <span class="spk-marquee-card__ministry">${speaker.ministry}</span>
                <div class="spk-marquee-card__meta"><span>${speaker.country}</span><span>${speaker.years}</span></div>
            </div>
        </article>`;
    }

    function renderMarquee() {
        const track = document.getElementById('speakerMarquee');
        if (!track) return;
        if (!SPEAKERS_DATA.previous.length) {
            track.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Previous guest ministers will appear here once added in the CMS.</p>';
            return;
        }
        const cards = SPEAKERS_DATA.previous.map(buildMarqueeCard).join('');
        track.innerHTML = cards + cards;

        track.querySelectorAll('.spk-marquee-card').forEach(card => {
            const open = () => {
                const id = card.dataset.speakerId;
                const speaker = SPEAKERS_DATA.previous.find(s => s.id === id);
                if (speaker) openSpeakerModal(speaker);
            };
            card.addEventListener('click', open);
            card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
            });
        });
    }

    function openSpeakerModal(speaker) {
        const modal = document.getElementById('speakerModal');
        const body = document.getElementById('speakerModalBody');
        if (!modal || !body) return;

        const galleryHtml = speaker.gallery?.length
            ? `<div class="spk-modal__section"><h4>Gallery</h4><div class="spk-modal__gallery">${speaker.gallery.map(g => `<img src="${g}" alt="" loading="lazy">`).join('')}</div></div>`
            : '';

        const socialHtml = speaker.social
            ? `<div class="spk-modal__social">${Object.entries(speaker.social).map(([k, url]) => `<a href="${url}" aria-label="${k}"><i class="fab fa-${k === 'x' ? 'x-twitter' : k}"></i></a>`).join('')}</div>`
            : '';

        body.innerHTML = `
            <div class="spk-modal__grid">
                <div class="spk-modal__photo"><img src="${speaker.photo}" alt="${speaker.name}"></div>
                <div class="spk-modal__body">
                    <h2 class="spk-modal__name">${speaker.name}</h2>
                    <span class="spk-modal__ministry">${speaker.ministry} · ${speaker.country}</span>
                    <p class="spk-modal__bio">${speaker.bio}</p>
                    <div class="spk-modal__section"><h4>SDTG Appearances</h4><p>${speaker.appearances}</p></div>
                    ${galleryHtml}
                    ${socialHtml}
                </div>
            </div>`;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        modal.querySelector('.spk-modal__close')?.focus();
    }

    function closeSpeakerModal() {
        const modal = document.getElementById('speakerModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    function renderTimeline() {
        const track = document.getElementById('speakerTimeline');
        if (!track) return;
        if (!SPEAKERS_DATA.timeline.length) {
            track.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center;width:100%">Crusade editions will appear here once added in the CMS.</p>';
            return;
        }
        track.innerHTML = SPEAKERS_DATA.timeline.map(t => `
            <article class="spk-year-card reveal">
                <p class="spk-year-card__year">SDTG ${t.year}</p>
                <h3 class="spk-year-card__theme">${t.theme}</h3>
                <div class="spk-year-card__speakers"><strong>Featured Speakers</strong>${t.speakers}</div>
                <p class="spk-year-card__highlights">${t.highlights}</p>
            </article>`).join('');
    }

    function initUpcomingScroll() {
        const track = document.getElementById('upcomingSpeakers');
        if (!track) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        track.addEventListener('mousedown', e => {
            isDown = true;
            track.classList.add('is-dragging');
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
        });

        track.addEventListener('mouseleave', () => {
            isDown = false;
            track.classList.remove('is-dragging');
        });

        track.addEventListener('mouseup', () => {
            isDown = false;
            track.classList.remove('is-dragging');
        });

        track.addEventListener('mousemove', e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            track.scrollLeft = scrollLeft - (x - startX) * 1.5;
        });
    }

    function renderUpcoming() {
        const grid = document.getElementById('upcomingSpeakers');
        if (!grid) return;
        if (!SPEAKERS_DATA.upcoming.length) {
            grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Upcoming ministers will be announced soon.</p>';
            return;
        }
        grid.innerHTML = SPEAKERS_DATA.upcoming.map(s => `
            <article class="spk-featured-card reveal" role="listitem">
                <div class="spk-featured-card__img">
                    <img src="${s.photo}" alt="${s.name}" loading="lazy" width="400" height="500">
                    ${s.featured ? '<span class="spk-featured-card__badge">Featured</span>' : ''}
                </div>
                <div class="spk-featured-card__body">
                    <h3 class="spk-featured-card__name">${s.name}</h3>
                    <span class="spk-featured-card__ministry">${s.ministry}</span>
                    <p class="spk-featured-card__country"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> ${s.country}</p>
                    <p class="spk-featured-card__topic">${s.topic}</p>
                    <p class="spk-featured-card__bio">${s.bio}</p>
                </div>
            </article>`).join('');
    }

    function renderKeynote() {
        const k = SPEAKERS_DATA.keynote;
        const el = document.getElementById('keynoteContent');
        if (!el) return;
        if (!k) {
            el.innerHTML = '<p class="section-desc">Keynote speaker details will be announced soon.</p>';
            return;
        }
        el.innerHTML = `
            <div class="spk-keynote__visual reveal">
                <img src="${k.photo}" alt="${k.name}" loading="lazy" width="420" height="560">
                <span class="spk-keynote__frame" aria-hidden="true"></span>
            </div>
            <div class="reveal" data-delay="150">
                <span class="spk-keynote__label">Keynote Speaker · SDTG 2026</span>
                <h2 class="spk-keynote__name">${k.name}</h2>
                <p class="spk-keynote__ministry">${k.ministry}</p>
                <p class="spk-keynote__bio">${k.bio}</p>
                <div class="spk-keynote__session"><h4>Speaking Session</h4><p>${k.session}</p></div>
            </div>`;
    }

    function renderWorship() {
        const grid = document.getElementById('worshipMinisters');
        if (!grid) return;
        if (!SPEAKERS_DATA.worship.length) {
            grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Worship ministers will be announced soon.</p>';
            return;
        }
        grid.innerHTML = SPEAKERS_DATA.worship.map(w => `
            <article class="spk-worship-card reveal">
                <div class="spk-worship-card__ring"><img src="${w.photo}" alt="${w.name}" loading="lazy" width="140" height="140"></div>
                <h3 class="spk-worship-card__name">${w.name}</h3>
                <span class="spk-worship-card__ministry">${w.ministry}</span>
                <span class="spk-worship-card__country">${w.country}</span>
            </article>`).join('');
    }

    function renderHosts() {
        const grid = document.getElementById('spkHosts');
        if (!grid) return;
        if (!SPEAKERS_DATA.hosts.length) {
            grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Host details will be announced soon.</p>';
            return;
        }
        grid.innerHTML = SPEAKERS_DATA.hosts.map(h => `
            <article class="spk-host-card reveal">
                <div class="spk-host-card__photo"><img src="${h.photo}" alt="${h.name}" loading="lazy" width="120" height="120"></div>
                <p class="spk-host-card__role">${h.role}</p>
                <h3 class="spk-host-card__name">${h.name}</h3>
                <p class="spk-host-card__bio">${h.bio}</p>
            </article>`).join('');
    }

    function renderVideos() {
        const grid = document.getElementById('speakerVideos');
        if (!grid) return;
        if (!SPEAKERS_DATA.videos.length) {
            grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Speaker video messages will appear here once published.</p>';
            return;
        }
        grid.innerHTML = SPEAKERS_DATA.videos.map((v, i) => `
            <article class="spk-video-card reveal" data-delay="${i * 80}" tabindex="0" data-video-type="${v.type}" data-video-src="${v.src}" data-video-title="${v.label}">
                <div class="spk-video-card__thumb">
                    <img src="${v.thumb}" alt="" loading="lazy" width="640" height="400">
                    <div class="spk-video-card__play"><span><i class="fas fa-play" aria-hidden="true"></i></span></div>
                </div>
                <div class="spk-video-card__body">
                    <p class="spk-video-card__name">${v.name}</p>
                    <p class="spk-video-card__label">${v.label}</p>
                </div>
            </article>`).join('');

        grid.querySelectorAll('.spk-video-card').forEach(card => {
            const open = () => openVideoModal(card.dataset.videoType, card.dataset.videoSrc, card.dataset.videoTitle);
            card.addEventListener('click', open);
            card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
        });
    }

    function openVideoModal(type, src, title) {
        const modal = document.getElementById('speakerVideoModal');
        const frame = document.getElementById('speakerVideoFrame');
        const titleEl = document.getElementById('speakerVideoTitle');
        if (!modal || !frame) return;

        frame.innerHTML = '';
        if (type === 'youtube') {
            const iframe = document.createElement('iframe');
            iframe.src = `https://www.youtube.com/embed/${src}?autoplay=1&rel=0`;
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            frame.appendChild(iframe);
        } else {
            const video = document.createElement('video');
            video.src = src;
            video.controls = true;
            video.autoplay = true;
            video.playsInline = true;
            frame.appendChild(video);
        }

        if (titleEl) titleEl.textContent = title;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
    }

    function closeVideoModal() {
        const modal = document.getElementById('speakerVideoModal');
        const frame = document.getElementById('speakerVideoFrame');
        if (frame) frame.innerHTML = '';
        modal?.classList.remove('is-open');
        modal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    function initTimelineDrag() {
        document.querySelectorAll('.spk-timeline__track').forEach(track => {
            let isDown = false, startX, scrollLeft;
            track.addEventListener('mousedown', e => { isDown = true; track.classList.add('is-dragging'); startX = e.pageX - track.offsetLeft; scrollLeft = track.scrollLeft; });
            track.addEventListener('mouseleave', () => { isDown = false; track.classList.remove('is-dragging'); });
            track.addEventListener('mouseup', () => { isDown = false; track.classList.remove('is-dragging'); });
            track.addEventListener('mousemove', e => {
                if (!isDown) return;
                e.preventDefault();
                track.scrollLeft = scrollLeft - (e.pageX - track.offsetLeft - startX) * 1.5;
            });
        });
    }

    function initTestimonialSlider() {
        const track = document.getElementById('spkSliderTrack');
        const dotsEl = document.getElementById('spkSliderDots');
        if (!track) return;

        const slides = track.querySelectorAll('.spk-slide');
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

        document.getElementById('spkSliderPrev')?.addEventListener('click', () => goTo(current - 1));
        document.getElementById('spkSliderNext')?.addEventListener('click', () => goTo(current + 1));
        goTo(0);
        if (!prefersReducedMotion) setInterval(() => goTo(current + 1), 7000);
    }

    function initScrollReveal() {
        if (prefersReducedMotion) {
            document.querySelectorAll('.reveal:not(.spk-hero .reveal)').forEach(el => el.classList.add('is-visible'));
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
        document.querySelectorAll('.reveal:not(.spk-hero .reveal)').forEach(el => observer.observe(el));
    }

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' }); }
            });
        });
    }

    function initNewsletter() {
        document.getElementById('newsletterForm')?.addEventListener('submit', e => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            e.target.querySelector('input').value = '';
            setTimeout(() => { btn.textContent = 'Subscribe'; }, 2000);
        });
    }

    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;
        window.addEventListener('scroll', () => btn.classList.toggle('visible', window.scrollY > 400), { passive: true });
        btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' }));
    }

    function initModals() {
        document.getElementById('speakerModal')?.addEventListener('click', e => {
            if (e.target.id === 'speakerModal' || e.target.closest('.spk-modal__close')) closeSpeakerModal();
        });
        document.getElementById('speakerVideoModal')?.addEventListener('click', e => {
            if (e.target.id === 'speakerVideoModal' || e.target.closest('.spk-video-modal__close')) closeVideoModal();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { closeSpeakerModal(); closeVideoModal(); }
        });
    }

    function renderHighlights() {
        const grid = document.getElementById('speakersHighlights');
        if (!grid) return;
        const items = SPEAKERS_DATA.highlights || [];
        if (!items.length) {
            grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center;grid-column:1/-1">Ministry highlights will appear here once marked in the gallery CMS.</p>';
            return;
        }
        grid.innerHTML = items.map((h, i) => `
            <a href="${h.link || 'gallery'}" class="spk-highlight-card reveal" ${i ? 'data-delay="' + (i * 80) + '"' : ''}>
                <img src="${h.image || 'img/lifted_hands.jpeg'}" alt="${h.title}" loading="lazy" width="400" height="533">
                <div class="spk-highlight-card__overlay">
                    <span class="spk-highlight-card__tag">${h.tag || 'Highlights'}</span>
                    <span class="spk-highlight-card__title">${h.title}</span>
                </div>
            </a>`).join('');
    }

    function renderAll() {
        renderMarquee();
        renderTimeline();
        renderUpcoming();
        renderKeynote();
        renderWorship();
        renderHosts();
        renderVideos();
        renderHighlights();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initPageLoad();
        initNavbar();
        initVideoLogos();
        initParticles('spkHeroParticles');
        initParticles('spkCtaParticles');
        initHeroReveal();
        loadSpeakersData().then(() => {
            renderAll();
            initUpcomingScroll();
            initCounters();
        });
        initTimelineDrag();
        initTestimonialSlider();
        initModals();
        initScrollReveal();
        initSmoothScroll();
        initNewsletter();
        initScrollTop();
    });

    window.SDTG_SPEAKERS = SPEAKERS_DATA;
})();

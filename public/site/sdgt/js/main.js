/**
 * Send Down Thy Glory — Homepage Interactions
 */
(function () {
    'use strict';

    let crusadeTargetMs = new Date('2026-08-15T09:00:00+01:00').getTime();
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function getPublicBootstrap() {
        return window.SDTG_PUBLIC_BOOTSTRAP || {};
    }

    function getCsrfToken() {
        return getPublicBootstrap().csrf_token || '';
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    const HOME_LAYOUT_CLASSES = {
        sm: '',
        md: '',
        lg: 'gallery__item--tall',
        wide: 'gallery__item--wide'
    };

    function buildHomeGalleryItem(item, index) {
        const isVideo = item.media_type === 'video';
        const layoutClass = HOME_LAYOUT_CLASSES[item.layout_size] || (index % 5 === 0 ? 'gallery__item--tall' : index === 3 ? 'gallery__item--wide' : '');
        const videoClass = isVideo ? ' gallery__item--video' : '';
        const img = escapeHtml(item.image || item.thumbnail || 'img/lifted_hands.jpeg');
        const label = escapeHtml(item.title || (isVideo ? 'Watch Video' : 'Gallery'));
        const delay = ` data-delay="${(index % 6) * 100}"`;

        if (isVideo) {
            return `<figure class="gallery__item${videoClass}${layoutClass ? ' ' + layoutClass : ''} reveal-up"${delay}>
                <a href="gallery#videos" class="gallery__item-link">
                    <img src="${img}" alt="${label}" loading="lazy">
                    <span class="gallery__play" aria-hidden="true"><i class="fas fa-play"></i></span>
                    <figcaption class="gallery__overlay"><span>${label}</span></figcaption>
                </a>
            </figure>`;
        }

        return `<figure class="gallery__item${layoutClass ? ' ' + layoutClass : ''} reveal-up"${delay}>
            <a href="gallery#photos" class="gallery__item-link">
                <img src="${img}" alt="${label}" loading="lazy">
                <figcaption class="gallery__overlay"><span>${label}</span></figcaption>
            </a>
        </figure>`;
    }

    function loadHomeGallery() {
        const masonry = document.getElementById('homeGalleryMasonry');
        if (!masonry) return Promise.resolve();

        const gallery = getPublicBootstrap().gallery;
        const render = (data) => {
            const photos = (data && data.photos) || [];
            const videos = (data && data.videos) || [];
            const preview = [];
            photos.slice(0, 4).forEach(item => preview.push(item));
            videos.slice(0, 2).forEach(item => preview.push(item));

            if (!preview.length) {
                masonry.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center;grid-column:1/-1">Gallery photos and videos will appear here once added in the CMS.</p>';
                return;
            }

            masonry.innerHTML = preview.slice(0, 6).map(buildHomeGalleryItem).join('');
        };

        if (gallery) {
            render(gallery);
            return Promise.resolve();
        }

        return fetch('../api/get-sdtg-gallery.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(body => render(body.success ? body.data : null))
            .catch(() => render(null));
    }

    function buildTestimonialSlide(item, index) {
        const stars = Array.from({ length: item.rating || 5 }, () => '<i class="fas fa-star"></i>').join('');
        const active = index === 0 ? ' active' : '';
        const location = item.location ? `<span>${escapeHtml(item.location)}</span>` : (item.year_label ? `<span>${escapeHtml(item.year_label)}</span>` : '');
        const avatar = item.photo_url
            ? `<img src="${escapeHtml(item.photo_url)}" alt="" loading="lazy" width="56" height="56">`
            : `<span class="testimonial-slide__avatar" aria-hidden="true">${escapeHtml(item.initials || 'SD')}</span>`;

        return `<blockquote class="testimonial-slide${active}">
            <div class="testimonial-slide__content">
                <div class="testimonial-slide__stars" aria-label="${item.rating || 5} out of 5 stars">${stars}</div>
                <p>&ldquo;${escapeHtml(item.text)}&rdquo;</p>
                <footer class="testimonial-slide__author">
                    ${avatar}
                    <div>
                        <cite>${escapeHtml(item.name)}</cite>
                        ${location}
                    </div>
                </footer>
            </div>
        </blockquote>`;
    }

    function loadTestimonials() {
        const track = document.getElementById('testimonialsTrack');
        const slider = document.getElementById('testimonialsSlider');
        if (!track) return Promise.resolve();

        const bootstrapItems = getPublicBootstrap().testimonials;
        const render = (items) => {
            if (!items || !items.length) {
                track.innerHTML = '<p class="section-desc testimonials-track__empty">Approved testimonies will appear here. Be the first to share your SDTG story below.</p>';
                const nav = slider && slider.querySelector('.testimonials-slider__nav');
                if (nav) nav.hidden = true;
                return;
            }

            track.innerHTML = items.map(buildTestimonialSlide).join('');
            initTestimonials();
        };

        if (Array.isArray(bootstrapItems)) {
            render(bootstrapItems);
            return Promise.resolve();
        }

        return fetch('../api/get-sdtg-testimonials.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(body => render(body.success ? body.data.items : []))
            .catch(() => render([]));
    }

    function populateEditionSelect(selectId) {
        const select = document.getElementById(selectId);
        if (!select || select.options.length > 1) return;

        const editions = (getPublicBootstrap().gallery && getPublicBootstrap().gallery.editions) || [];
        editions.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            select.appendChild(opt);
        });
    }

    function initTestimonyModal() {
        const modal = document.getElementById('testimonyModal');
        const openBtn = document.getElementById('openTestimonyModal');
        const form = document.getElementById('testimonyForm');
        const success = document.getElementById('testimonyFormSuccess');
        const error = document.getElementById('testimonyFormError');
        if (!modal || !form) return;

        populateEditionSelect('testimonyEdition');

        let lastFocused = null;

        const open = () => {
            lastFocused = document.activeElement;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            document.body.classList.add('no-scroll');
            const firstField = document.getElementById('testimonyName');
            if (firstField) {
                window.setTimeout(() => firstField.focus(), 50);
            }
        };

        const close = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('no-scroll');
            if (lastFocused && typeof lastFocused.focus === 'function') {
                lastFocused.focus();
            }
        };

        if (openBtn) {
            openBtn.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                open();
            });
        }

        modal.querySelectorAll('[data-close-testimony]').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                close();
            });
        });

        modal.addEventListener('click', e => {
            if (e.target === modal.querySelector('.testimonials-modal__backdrop')) {
                close();
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                e.preventDefault();
                close();
            }
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (success) success.classList.add('hidden');
            if (error) error.classList.add('hidden');

            const submitBtn = form.querySelector('button[type="submit"]');
            const payload = {
                csrf_token: getCsrfToken(),
                name: form.querySelector('#testimonyName')?.value.trim(),
                email: form.querySelector('#testimonyEmail')?.value.trim(),
                location: form.querySelector('#testimonyLocation')?.value.trim(),
                edition: form.querySelector('#testimonyEdition')?.value,
                testimony: form.querySelector('#testimonyText')?.value.trim(),
                source: 'home'
            };

            if (submitBtn) submitBtn.disabled = true;

            fetch('../api/submit-sdtg-testimony.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(res => res.json().then(body => ({ ok: res.ok, body })))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) {
                        throw new Error(body.message || 'Unable to submit testimony.');
                    }
                    form.reset();
                    if (success) {
                        success.textContent = body.message || 'Thank you! Your testimony has been submitted for review.';
                        success.classList.remove('hidden');
                    }
                    setTimeout(close, 2500);
                })
                .catch(err => {
                    if (error) {
                        error.textContent = err.message || 'Unable to submit testimony.';
                        error.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    function applyCountdownConfig(data) {
        if (!data) return;

        if (data.target_ms) crusadeTargetMs = Number(data.target_ms);

        const titleEl = document.getElementById('countdownTitle');
        const themeEl = document.getElementById('countdownTheme');
        const dateEl = document.getElementById('countdownDateLabel');

        if (titleEl && data.title) titleEl.textContent = data.title;
        if (themeEl && data.theme) themeEl.textContent = 'SDTG ' + data.year + ' — ' + data.theme;
        if (dateEl && data.date_label) {
            dateEl.innerHTML = '<i class="far fa-calendar-alt" aria-hidden="true"></i> ' + escapeHtml(data.date_label);
        }
    }

    function buildNewsCard(item, index) {
        const img = escapeHtml(item.image || 'img/lifted_hands.jpeg');
        const link = escapeHtml(item.link || '#news');
        const delay = ` data-delay="${(index % 3) * 150}"`;

        return `<article class="news-card reveal-up"${delay}>
            <div class="news-card__image">
                <img src="${img}" alt="" loading="lazy">
                <span class="news-card__tag">${escapeHtml(item.category || 'Announcement')}</span>
            </div>
            <div class="news-card__body">
                <time datetime="${escapeHtml(item.published_iso || '')}">${escapeHtml(item.published_date || '')}</time>
                <h3>${escapeHtml(item.title)}</h3>
                <p>${escapeHtml(item.excerpt || '')}</p>
                <a href="${link}" class="news-card__link">Read More <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </article>`;
    }

    function loadHomeNews() {
        const grid = document.getElementById('homeNewsGrid');
        if (!grid) return Promise.resolve();

        const render = (items) => {
            if (!items || !items.length) {
                grid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center;grid-column:1/-1">Announcements will appear here once published in the CMS.</p>';
                return;
            }
            grid.innerHTML = items.map(buildNewsCard).join('');
        };

        const home = getPublicBootstrap().home;
        if (home && Array.isArray(home.announcements)) {
            render(home.announcements);
            return Promise.resolve();
        }

        return fetch('../api/get-sdtg-home.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(body => render(body.success ? body.data.announcements : []))
            .catch(() => render([]));
    }

    function loadHomePageData() {
        const home = getPublicBootstrap().home;
        if (home) {
            if (home.countdown) applyCountdownConfig(home.countdown);
            return Promise.resolve(home);
        }

        return fetch('../api/get-sdtg-home.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(body => {
                if (body.success && body.data) {
                    if (body.data.countdown) applyCountdownConfig(body.data.countdown);
                    return body.data;
                }
                return null;
            })
            .catch(() => null);
    }

    /* ---- Preloader (3D logo video) ---- */
    function initPreloader() {
        const preloader = document.getElementById('sdtg-preloader');
        const video = document.getElementById('preloaderVideo');
        if (!preloader) return;

        document.body.classList.add('no-scroll');

        let hidden = false;
        const maxWaitMs = 120000;

        const hide = () => {
            if (hidden) return;
            hidden = true;

            try { if (video) video.pause(); } catch (e) { /* ignore */ }

            preloader.classList.add('hidden', 'is-done');
            preloader.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');

            setTimeout(() => preloader.remove(), 900);
        };

        if (prefersReducedMotion) {
            hide();
            return;
        }

        const startPlayback = () => {
            if (!video) {
                hide();
                return;
            }
            video.muted = true;
            const playPromise = video.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(() => hide());
            }
        };

        if (video) {
            video.addEventListener('ended', hide);
            video.addEventListener('error', hide);

            if (video.readyState >= 2) {
                startPlayback();
            } else {
                video.addEventListener('loadeddata', startPlayback, { once: true });
            }
        } else {
            setTimeout(hide, 2200);
        }

        setTimeout(hide, maxWaitMs);
    }

    /* ---- Video logos (navbar, footer) ---- */
    function initVideoLogos() {
        document.querySelectorAll('.logo-video__el').forEach(video => {
            video.muted = true;
            video.playsInline = true;
            const play = () => {
                video.play().catch(() => {});
            };
            if (video.readyState >= 2) {
                play();
            } else {
                video.addEventListener('loadeddata', play, { once: true });
            }
        });
    }

    /* ---- Navbar ---- */
    function initNavbar() {
        const navbar = document.getElementById('navbar');
        const toggle = document.getElementById('navToggle');
        const menu = document.getElementById('navMenu');
        const backdrop = document.getElementById('navBackdrop');
        const links = document.querySelectorAll('.nav-link');

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

        const onScroll = () => {
            navbar.classList.toggle('scrolled', window.scrollY > 60);
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        toggle.addEventListener('click', () => {
            if (menu.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeMenu);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && menu.classList.contains('open')) {
                closeMenu();
            }
        });

        links.forEach(link => link.addEventListener('click', closeMenu));

        /* Active nav — scroll spy for in-page hash links */
        const hashLinks = Array.from(links).filter(link => {
            const href = link.getAttribute('href');
            return href && href.startsWith('#') && document.querySelector(href);
        });

        if (hashLinks.length) {
            const sections = hashLinks
                .map(link => ({
                    id: link.getAttribute('href').slice(1),
                    el: document.querySelector(link.getAttribute('href'))
                }))
                .filter(section => section.el)
                .sort((a, b) => a.el.offsetTop - b.el.offsetTop);

            const setActiveLink = (activeId) => {
                links.forEach(link => {
                    const href = link.getAttribute('href');
                    if (!href || !href.startsWith('#')) {
                        link.classList.remove('active');
                        link.removeAttribute('aria-current');
                        return;
                    }

                    const isActive = href === `#${activeId}`;
                    link.classList.toggle('active', isActive);
                    if (isActive) {
                        link.setAttribute('aria-current', 'true');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            };

            const updateActiveNav = () => {
                const scrollPos = window.scrollY + window.innerHeight * 0.35;
                let currentId = sections[0].id;

                sections.forEach(({ id, el }) => {
                    const top = el.getBoundingClientRect().top + window.scrollY;
                    if (scrollPos >= top) {
                        currentId = id;
                    }
                });

                setActiveLink(currentId);
            };

            window.addEventListener('scroll', updateActiveNav, { passive: true });
            updateActiveNav();

            hashLinks.forEach(link => {
                link.addEventListener('click', () => {
                    setActiveLink(link.getAttribute('href').slice(1));
                });
            });
        }
    }

    /* ---- Hero video playlist ---- */
    function initHeroVideo() {
        const video = document.getElementById('heroVideo');
        const media = document.getElementById('heroMedia');
        if (!video || !media) return;

        const PLAYLIST = [
            '/site/videos/glory2.mp4',
            '/site/videos/glory1.mp4'
        ];
        const IMAGE_DURATION = 10000;

        let currentIndex = 0;
        let imageTimer = null;
        let isShowingImage = false;

        const showVideoPhase = () => {
            isShowingImage = false;
            clearTimeout(imageTimer);
            media.classList.remove('hero__media--show-image');
            media.classList.add('hero__media--video-ready');
            playVideoAt(currentIndex);
        };

        const showImagePhase = () => {
            isShowingImage = true;
            video.pause();
            video.classList.remove('is-ready');
            media.classList.remove('hero__media--video-ready');
            media.classList.add('hero__media--show-image');

            if (prefersReducedMotion) return;

            imageTimer = setTimeout(() => {
                currentIndex = 0;
                showVideoPhase();
            }, IMAGE_DURATION);
        };

        const skipToNext = () => {
            currentIndex += 1;
            if (currentIndex < PLAYLIST.length) {
                playVideoAt(currentIndex);
            } else {
                showImagePhase();
            }
        };

        const playVideoAt = (index) => {
            if (index >= PLAYLIST.length) {
                showImagePhase();
                return;
            }

            currentIndex = index;
            video.classList.remove('is-ready');
            video.muted = true;
            video.playsInline = true;

            const onReady = () => {
                video.classList.add('is-ready');
                video.play().catch(skipToNext);
            };

            video.onloadeddata = onReady;
            video.onended = skipToNext;
            video.onerror = skipToNext;
            video.src = PLAYLIST[index];
            video.load();

            if (video.readyState >= 2) {
                onReady();
            }
        };

        if (prefersReducedMotion) {
            media.classList.add('hero__media--show-image');
            return;
        }

        showVideoPhase();

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) return;
            if (isShowingImage) return;
            if (video.paused && video.classList.contains('is-ready')) {
                video.play().catch(() => {});
            }
        });
    }

    /* ---- Hero Particles ---- */
    function initParticles() {
        if (prefersReducedMotion) return;

        const canvas = document.getElementById('heroParticles');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let particles = [];
        let animationId;

        function resize() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }

        function createParticles() {
            const count = Math.min(60, Math.floor(canvas.width * canvas.height / 15000));
            particles = Array.from({ length: count }, () => ({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                size: Math.random() * 2.5 + 0.5,
                speedY: Math.random() * 0.5 + 0.2,
                speedX: (Math.random() - 0.5) * 0.3,
                opacity: Math.random() * 0.5 + 0.2,
                pulse: Math.random() * Math.PI * 2
            }));
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach(p => {
                p.y -= p.speedY;
                p.x += p.speedX;
                p.pulse += 0.02;

                if (p.y < -10) {
                    p.y = canvas.height + 10;
                    p.x = Math.random() * canvas.width;
                }

                const alpha = p.opacity * (0.5 + 0.5 * Math.sin(p.pulse));
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(251, 254, 6, ${alpha})`;
                ctx.fill();
            });

            animationId = requestAnimationFrame(animate);
        }

        resize();
        createParticles();
        animate();

        window.addEventListener('resize', () => {
            cancelAnimationFrame(animationId);
            resize();
            createParticles();
            animate();
        });
    }

    /* ---- Confetti (countdown celebration) ---- */
    function launchConfetti() {
        if (prefersReducedMotion || document.querySelector('.confetti-canvas')) return;

        const canvas = document.createElement('canvas');
        canvas.className = 'confetti-canvas';
        canvas.style.cssText = 'position:fixed;inset:0;z-index:9999;pointer-events:none;';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const colors = ['#fbfe06', '#fdff66', '#ffffff', '#4a2d7a'];
        const pieces = Array.from({ length: 120 }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            w: Math.random() * 10 + 5,
            h: Math.random() * 6 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            speed: Math.random() * 3 + 2,
            drift: (Math.random() - 0.5) * 2
        }));

        let frame = 0;
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                p.y += p.speed;
                p.x += p.drift;
                p.rotation += 3;
                if (p.y > canvas.height) p.y = -20;

                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });

            frame++;
            if (frame < 300) requestAnimationFrame(animate);
            else canvas.remove();
        }

        animate();
    }

    /* ---- Countdown ---- */
    function initCountdown() {
        const timer = document.getElementById('countdownTimer');
        const begun = document.getElementById('countdownBegun');
        const els = {
            days: document.getElementById('cdDays'),
            hours: document.getElementById('cdHours'),
            minutes: document.getElementById('cdMinutes'),
            seconds: document.getElementById('cdSeconds')
        };

        let prev = {};

        function pad(n) {
            return String(n).padStart(2, '0');
        }

        function flip(el, value) {
            if (prev[el.id] !== value) {
                el.classList.remove('flip');
                void el.offsetWidth;
                el.classList.add('flip');
                el.textContent = value;
                prev[el.id] = value;
            }
        }

        function tick() {
            const now = Date.now();
            const diff = crusadeTargetMs - now;

            if (diff <= 0) {
                timer.classList.add('hidden');
                begun.classList.remove('hidden');
                launchConfetti();
                return;
            }

            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff % 86400000) / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            flip(els.days, pad(days));
            flip(els.hours, pad(hours));
            flip(els.minutes, pad(minutes));
            flip(els.seconds, pad(seconds));
        }

        tick();
        setInterval(tick, 1000);
    }

    /* ---- Animated Counters ---- */
    function initCounters() {
        const counters = document.querySelectorAll('.stat-card__number');

        const animateCounter = (el) => {
            const target = parseInt(el.dataset.target, 10);
            const suffix = el.dataset.suffix || '';
            const duration = 2000;
            const start = performance.now();

            function easeOutQuart(t) {
                return 1 - Math.pow(1 - t, 4);
            }

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const value = Math.floor(easeOutQuart(progress) * target);

                el.textContent = value.toLocaleString() + suffix;

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    el.textContent = target.toLocaleString() + suffix;
                }
            }

            requestAnimationFrame(update);
        };

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => observer.observe(c));
    }

    /* ---- Scroll Reveal ---- */
    function initScrollReveal() {
        const elements = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');

        if (prefersReducedMotion) {
            elements.forEach(el => el.classList.add('revealed'));
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.dataset.delay || 0;
                    setTimeout(() => entry.target.classList.add('revealed'), parseInt(delay, 10));
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        elements.forEach(el => observer.observe(el));

        document.querySelectorAll('.hero .reveal-up').forEach((el, i) => {
            setTimeout(() => el.classList.add('revealed'), 400 + i * 200);
        });
    }

    /* ---- Ministers Carousel ---- */
    function buildMinisterCard(speaker) {
        const photo = speaker.photo || 'img/lifted_hands.jpeg';
        const ministry = speaker.ministry || '';
        const bio = speaker.bio || speaker.topic || '';
        return `<article class="minister-card">
            <div class="minister-card__image">
                <img src="${photo}" alt="${speaker.name}" loading="lazy">
                <div class="minister-card__overlay"></div>
            </div>
            <div class="minister-card__body">
                <h3>${speaker.name}</h3>
                <span class="minister-card__ministry">${ministry}</span>
                <p>${bio}</p>
            </div>
        </article>`;
    }

    function loadFeaturedMinisters() {
        const track = document.getElementById('ministersTrack');
        const empty = document.getElementById('ministersEmpty');
        if (!track) return Promise.resolve();

        const bootstrap = window.SDTG_SPEAKERS_BOOTSTRAP;
        const render = (data) => {
            const featured = (data?.upcoming || []).filter(s => s.featured);
            const list = featured.length ? featured : (data?.upcoming || []).slice(0, 6);
            if (!list.length) {
                if (empty) empty.style.display = '';
                return;
            }
            if (empty) empty.remove();
            track.innerHTML = list.map(buildMinisterCard).join('');
            initMinistersCarousel();
        };

        if (bootstrap) {
            render(bootstrap);
            return Promise.resolve();
        }

        return fetch('../api/get-sdtg-speakers.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(body => render(body.success ? body.data : null))
            .catch(() => render(null));
    }

    function initMinistersCarousel() {
        const track = document.getElementById('ministersTrack');
        const prev = document.getElementById('ministersPrev');
        const next = document.getElementById('ministersNext');
        const dotsContainer = document.getElementById('ministersDots');
        if (!track) return;

        const cards = track.querySelectorAll('.minister-card');
        if (!cards.length) return;
        let current = 0;
        let visible = 3;
        let autoplayTimer;

        function getVisible() {
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 1024) return 2;
            return 3;
        }

        function getMaxIndex() {
            return Math.max(0, cards.length - visible);
        }

        function buildDots() {
            dotsContainer.innerHTML = '';
            const total = getMaxIndex() + 1;
            for (let i = 0; i < total; i++) {
                const dot = document.createElement('button');
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                dot.classList.toggle('active', i === current);
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            }
        }

        function update() {
            visible = getVisible();
            current = Math.min(current, getMaxIndex());
            const card = cards[0];
            if (!card) return;

            const trackWidth = track.parentElement.offsetWidth - 120;
            const gap = 24;
            const cardWidth = (trackWidth - gap * (visible - 1)) / visible + gap;
            track.style.transform = `translateX(-${current * (cardWidth - gap)}px)`;

            dotsContainer.querySelectorAll('button').forEach((dot, i) => {
                dot.classList.toggle('active', i === current);
            });
        }

        function goTo(index) {
            current = Math.max(0, Math.min(index, getMaxIndex()));
            update();
            resetAutoplay();
        }

        function resetAutoplay() {
            clearInterval(autoplayTimer);
            if (!prefersReducedMotion) {
                autoplayTimer = setInterval(() => {
                    current = current >= getMaxIndex() ? 0 : current + 1;
                    update();
                }, 5000);
            }
        }

        prev.addEventListener('click', () => goTo(current - 1));
        next.addEventListener('click', () => goTo(current + 1));

        buildDots();
        update();
        resetAutoplay();

        window.addEventListener('resize', () => {
            buildDots();
            update();
        });

        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) goTo(diff > 0 ? current + 1 : current - 1);
        }, { passive: true });
    }

    /* ---- Testimonials Slider ---- */
    function initTestimonials() {
        const slides = document.querySelectorAll('.testimonial-slide');
        const prev = document.getElementById('testimonialPrev');
        const next = document.getElementById('testimonialNext');
        const dotsContainer = document.getElementById('testimonialsDots');
        if (!slides.length || !prev || !next || !dotsContainer) return;

        dotsContainer.innerHTML = '';
        let current = 0;
        let autoplayTimer;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.setAttribute('aria-label', `Testimonial ${i + 1}`);
            dot.classList.toggle('active', i === 0);
            dot.addEventListener('click', () => goTo(i));
            dotsContainer.appendChild(dot);
        });

        function goTo(index) {
            slides[current].classList.remove('active');
            current = (index + slides.length) % slides.length;
            slides[current].classList.add('active');
            dotsContainer.querySelectorAll('button').forEach((dot, i) => {
                dot.classList.toggle('active', i === current);
            });
            resetAutoplay();
        }

        function resetAutoplay() {
            clearInterval(autoplayTimer);
            if (!prefersReducedMotion) {
                autoplayTimer = setInterval(() => goTo(current + 1), 7000);
            }
        }

        prev.addEventListener('click', () => goTo(current - 1));
        next.addEventListener('click', () => goTo(current + 1));
        resetAutoplay();
    }

    /* ---- Prayer Form ---- */
    function initPrayerForm() {
        const form = document.getElementById('prayerForm');
        const success = document.getElementById('prayerSuccess');
        if (!form) return;

        form.addEventListener('submit', e => {
            e.preventDefault();

            const name = form.querySelector('#prayerName');
            const email = form.querySelector('#prayerEmail');
            const request = form.querySelector('#prayerRequest');
            let valid = true;

            [name, email, request].forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    valid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.style.borderColor = '#e74c3c';
                valid = false;
            }

            if (!valid) return;

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            success.classList.add('hidden');

            fetch('../api/submit-sdtg-prayer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    csrf_token: getCsrfToken(),
                    name: name.value.trim(),
                    email: email.value.trim(),
                    request: request.value.trim()
                })
            })
                .then(res => res.json().then(body => ({ ok: res.ok, body })))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) {
                        throw new Error(body.message || 'Unable to submit prayer request.');
                    }
                    success.textContent = body.message || 'Your prayer request has been received. We\'re standing with you in faith!';
                    success.classList.remove('hidden');
                    form.reset();
                    setTimeout(() => success.classList.add('hidden'), 6000);
                })
                .catch(err => {
                    alert(err.message || 'Unable to submit prayer request. Please try again.');
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    /* ---- Newsletter Form ---- */
    function initNewsletter() {
        const form = document.getElementById('newsletterForm');
        if (!form) return;

        form.addEventListener('submit', e => {
            e.preventDefault();
            const input = form.querySelector('input');
            const btn = form.querySelector('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            input.value = '';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }, 2000);
        });
    }

    /* ---- Legacy horizontal scroll (drag) ---- */
    function initLegacyScroll() {
        document.querySelectorAll('.legacy-hscroll__track').forEach(track => {
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
        });
    }

    /* ---- Scroll to Top ---- */
    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;

        const showThreshold = 400;

        const toggleVisibility = () => {
            btn.classList.toggle('visible', window.scrollY > showThreshold);
        };

        window.addEventListener('scroll', toggleVisibility, { passive: true });
        toggleVisibility();

        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion ? 'auto' : 'smooth'
            });
        });
    }

    /* ---- Parallax ---- */
    function initParallax() {
        if (prefersReducedMotion) return;

        const hero = document.querySelector('.hero__content');
        const aboutImg = document.querySelector('.about__image-wrap img');

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;

            if (hero && scrollY < window.innerHeight) {
                hero.style.transform = `translateY(${scrollY * 0.3}px)`;
                hero.style.opacity = 1 - scrollY / (window.innerHeight * 0.8);
            }

            if (aboutImg) {
                const rect = aboutImg.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    const offset = (rect.top - window.innerHeight / 2) * 0.05;
                    aboutImg.style.transform = `scale(1.05) translateY(${offset}px)`;
                }
            }
        }, { passive: true });
    }

    /* ---- Smooth anchor scroll offset ---- */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', e => {
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                }
            });
        });
    }

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded', () => {
        initPreloader();
        initNavbar();
        initVideoLogos();
        initHeroVideo();
        initParticles();
        loadHomePageData().then(() => initCountdown());
        initCounters();
        initScrollReveal();
        loadFeaturedMinisters();
        loadHomeGallery();
        loadTestimonials();
        initTestimonyModal();
        initPrayerForm();
        loadHomeNews();
        initNewsletter();
        initLegacyScroll();
        initScrollTop();
        initParallax();
        initSmoothScroll();
    });
})();

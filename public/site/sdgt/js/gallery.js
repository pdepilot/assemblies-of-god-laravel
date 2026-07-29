/**
 * SDTG Gallery Page — Interactions
 */
(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const API_URL = '/api/sdtg-gallery';

    const EMPTY_GALLERY_DATA = {
        photos: [], videos: [], featured: [], highlights: [], albums: [], timeline: [], stats: null, categories: [], editions: [], social: null, community: []
    };

    let GALLERY_DATA = window.SDTG_GALLERY_BOOTSTRAP || null;

    function normalizeGalleryData(raw) {
        if (!raw || typeof raw !== 'object') return EMPTY_GALLERY_DATA;
        return {
            photos: Array.isArray(raw.photos) ? raw.photos : [],
            videos: Array.isArray(raw.videos) ? raw.videos : [],
            featured: Array.isArray(raw.featured) ? raw.featured : [],
            highlights: Array.isArray(raw.highlights) ? raw.highlights : [],
            albums: Array.isArray(raw.albums) ? raw.albums : [],
            timeline: Array.isArray(raw.timeline) ? raw.timeline : [],
            stats: raw.stats && typeof raw.stats === 'object' ? raw.stats : null,
            categories: Array.isArray(raw.categories) ? raw.categories : [],
            editions: Array.isArray(raw.editions) ? raw.editions : [],
            social: raw.social && typeof raw.social === 'object' ? raw.social : null,
            community: Array.isArray(raw.community) ? raw.community : [],
            csrf_token: typeof raw.csrf_token === 'string' ? raw.csrf_token : ''
        };
    }

    function getCsrfToken() {
        const hidden = document.getElementById('shareCsrfToken');
        if (hidden && hidden.value) return hidden.value;
        return (GALLERY_DATA && GALLERY_DATA.csrf_token) || (window.SDTG_GALLERY_BOOTSTRAP && window.SDTG_GALLERY_BOOTSTRAP.csrf_token) || '';
    }

    const CATEGORY_LABELS = {
        worship: 'Worship',
        ministers: 'Ministry',
        choir: 'Choir',
        congregation: 'Congregation',
        prayer: 'Prayer',
        healing: 'Testimony',
        highlights: 'Highlights',
        behind: 'Behind The Scenes',
        videos: 'Video'
    };

    function categoryLabel(category) {
        return CATEGORY_LABELS[category] || 'Highlights';
    }

    function loadGalleryData() {
        if (GALLERY_DATA) {
            GALLERY_DATA = normalizeGalleryData(GALLERY_DATA);
            if (window.SDTG_GALLERY_BOOTSTRAP && window.SDTG_GALLERY_BOOTSTRAP.csrf_token) {
                GALLERY_DATA.csrf_token = window.SDTG_GALLERY_BOOTSTRAP.csrf_token;
            }
            return Promise.resolve(GALLERY_DATA);
        }
        return fetch(API_URL, { credentials: 'same-origin' })
            .then(res => res.json().then(body => {
                if (!res.ok || !body.success) throw new Error(body.message || 'Unable to load gallery.');
                return normalizeGalleryData(body.data);
            }))
            .then(data => {
                GALLERY_DATA = data;
                if (data.csrf_token) {
                    const hidden = document.getElementById('shareCsrfToken');
                    if (hidden) hidden.value = data.csrf_token;
                }
                return GALLERY_DATA;
            })
            .catch(() => {
                GALLERY_DATA = EMPTY_GALLERY_DATA;
                return GALLERY_DATA;
            });
    }

    function buildMasonryItem(photo, index) {
        const sizeClass = {
            sm: 'gallery-masonry__item--sm',
            md: 'gallery-masonry__item--md',
            lg: 'gallery-masonry__item--lg',
            wide: 'gallery-masonry__item--wide'
        }[photo.layout_size] || 'gallery-masonry__item--md';
        const img = photo.image || photo.thumbnail || 'img/lifted_hands.jpeg';
        const caption = photo.caption || photo.title || '';
        return `<article class="gallery-masonry__item ${sizeClass} reveal" data-delay="${(index % 6) * 60}"
            data-category="${photo.category}" data-album-id="${photo.album_id || ''}" data-src="${img}" data-title="${photo.title}"
            data-year="${photo.year_label || photo.year}" data-caption="${caption}">
            <div class="gallery-masonry__img-wrap">
                <img src="${img}" alt="${photo.title}" loading="lazy" width="600" height="400">
                <span class="gallery-masonry__shine" aria-hidden="true"></span>
                <span class="gallery-masonry__view" aria-hidden="true"><i class="fas fa-search-plus"></i></span>
            </div>
            <div class="gallery-masonry__overlay">
                <span class="gallery-masonry__year">${photo.year || ''}</span>
                <span class="gallery-masonry__caption">${photo.title}</span>
            </div>
        </article>`;
    }

    function buildVideoCard(video, index) {
        const thumb = video.thumbnail || video.image || 'img/lifted_hands.jpeg';
        return `<article class="video-card reveal" data-delay="${index * 100}" tabindex="0"
            data-video-type="${video.video_type || 'local'}" data-video-src="${video.video_src || ''}"
            data-video-title="${video.title}">
            <div class="video-card__thumb">
                <img src="${thumb}" alt="" loading="lazy" width="640" height="400">
                <div class="video-card__play"><span class="video-card__play-btn"><i class="fas fa-play" aria-hidden="true"></i></span></div>
            </div>
            <div class="video-card__body">
                <p class="video-card__tag">${video.category || 'Video'}</p>
                <h3 class="video-card__title">${video.title}</h3>
            </div>
        </article>`;
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildCollectionCard(album, index) {
        const cover = album.cover_url
            ? `<img src="${escapeHtml(album.cover_url)}" alt="${escapeHtml(album.title)}" loading="lazy" width="600" height="412">`
            : '<span class="collection-card__cover-fallback" aria-hidden="true"><i class="fas fa-folder-open"></i></span>';
        const desc = album.description
            ? `<p class="collection-card__desc">${escapeHtml(album.description)}</p>`
            : '';
        const count = Number(album.item_count || 0);
        const countLabel = count === 1 ? '1 photo' : `${count.toLocaleString()} photos`;

        return `<a href="#photos" class="collection-card reveal" data-delay="${(index % 3) * 100}" data-album-id="${album.id}">
            <div class="collection-card__cover">
                ${cover}
                <span class="collection-card__year">SDTG ${escapeHtml(String(album.crusade_year))}</span>
            </div>
            <div class="collection-card__body">
                <h3 class="collection-card__title">${escapeHtml(album.title)}</h3>
                ${desc}
                <div class="collection-card__meta">
                    <span><strong>${countLabel}</strong></span>
                    <span class="collection-card__link">View collection <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </div>
            </div>
        </a>`;
    }

    function renderAlbumCollections() {
        const grid = document.getElementById('galleryAlbumsGrid');
        if (!grid) return;

        const albums = (GALLERY_DATA.albums || []).filter(a => a.is_published !== false);
        if (!albums.length) {
            grid.innerHTML = '<p class="section-desc collections-grid__empty">Album collections will appear here once created in the gallery CMS.</p>';
            return;
        }

        grid.innerHTML = albums.map(buildCollectionCard).join('');

        grid.querySelectorAll('.collection-card').forEach(card => {
            card.addEventListener('click', e => {
                e.preventDefault();
                filterGalleryByAlbum(card.dataset.albumId || '');
                document.getElementById('photos')?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            });
        });

        observeReveals('#galleryAlbumsGrid .reveal');
    }

    function filterGalleryByAlbum(albumId) {
        const grid = document.getElementById('galleryMasonry');
        if (!grid) return;

        const items = grid.querySelectorAll('.gallery-masonry__item');
        const id = String(albumId || '');

        document.querySelectorAll('.gallery-filters__btn').forEach(btn => {
            const active = !id && btn.dataset.filter === 'all';
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        items.forEach(item => {
            const match = !id || String(item.dataset.albumId || '') === id;
            item.classList.toggle('is-hidden', !match);
        });
    }

    function renderGallery() {
        const masonry = document.getElementById('galleryMasonry');
        if (masonry) {
            if (!GALLERY_DATA.photos.length) {
                masonry.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Photos will appear here once added in the gallery CMS.</p>';
            } else {
                masonry.innerHTML = GALLERY_DATA.photos.map(buildMasonryItem).join('');
            }
        }

        const videoGrid = document.getElementById('galleryVideoGrid');
        if (videoGrid) {
            if (!GALLERY_DATA.videos.length) {
                videoGrid.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Videos will appear here once added in the gallery CMS.</p>';
            } else {
                videoGrid.innerHTML = GALLERY_DATA.videos.map(buildVideoCard).join('');
            }
        }

        const timeline = document.getElementById('galleryYearsTimeline');
        if (timeline) {
            if (!GALLERY_DATA.timeline.length) {
                timeline.innerHTML = '<p class="section-desc" style="padding:24px;text-align:center">Crusade editions will appear here once added.</p>';
            } else {
                timeline.innerHTML = GALLERY_DATA.timeline.map((t, i) => `
                    <article class="year-card reveal" ${i ? `data-delay="${i * 100}"` : ''} role="listitem">
                        <div class="year-card__img">
                            <img src="${t.image || 'img/lifted_hands.jpeg'}" alt="SDTG ${t.year}" loading="lazy" width="340" height="212">
                        </div>
                        <div class="year-card__body">
                            <p class="year-card__year">SDTG ${t.year}</p>
                            <h3 class="year-card__theme">${t.theme}</h3>
                            <p class="year-card__highlights">${t.highlights}</p>
                        </div>
                    </article>`).join('');
            }
        }

        if (GALLERY_DATA.stats) {
            const mapping = [
                ['[data-count="2840"]', GALLERY_DATA.stats.photos, '+'],
                ['[data-count="186"]', GALLERY_DATA.stats.videos, '+'],
                ['[data-count="12"]', GALLERY_DATA.stats.years, ''],
                ['[data-count="48"]', GALLERY_DATA.stats.albums, '+']
            ];
            mapping.forEach(([selector, value, suffix]) => {
                const el = document.querySelector(selector);
                if (el) {
                    el.dataset.count = String(value);
                    if (suffix) el.dataset.suffix = suffix;
                }
            });
        }

        renderAlbumCollections();
        renderFeaturedMoments();
        renderCommunityMemories();
        renderSocialWall();
    }

    function renderCommunityMemories() {
        const grid = document.getElementById('galleryCommunityGrid');
        if (!grid) return;

        const stories = GALLERY_DATA.community || [];
        if (!stories.length) {
            grid.innerHTML = '<p class="section-desc community-memories__empty">Community stories will appear here once memories are featured in the admin panel.</p>';
            return;
        }

        grid.innerHTML = stories.map((story, index) => {
            const photos = (story.photo_urls || []).slice(0, 4);
            const mediaHtml = photos.length
                ? `<div class="community-card__media">${photos.map((url, i) =>
                    `<img src="${escapeHtml(url)}" alt="" loading="lazy" class="community-card__photo${i === 0 ? ' community-card__photo--main' : ''}">`
                ).join('')}</div>`
                : (story.video_urls && story.video_urls[0]
                    ? `<div class="community-card__media community-card__media--video"><video src="${escapeHtml(story.video_urls[0])}" muted playsinline preload="metadata"></video><span class="community-card__video-badge"><i class="fas fa-play" aria-hidden="true"></i></span></div>`
                    : `<div class="community-card__media community-card__media--text" aria-hidden="true"><i class="fas fa-quote-left"></i></div>`);

            const testimony = story.testimony
                ? `<blockquote class="community-card__quote">&ldquo;${escapeHtml(story.testimony)}&rdquo;</blockquote>`
                : '';

            const meta = story.year_label
                ? `<span class="community-card__edition">${escapeHtml(story.year_label)}</span>`
                : '';

            return `<article class="community-card reveal" data-delay="${(index % 3) * 100}">
                ${mediaHtml}
                <div class="community-card__body">
                    <p class="community-card__name">${escapeHtml(story.name)}</p>
                    ${meta}
                    ${testimony}
                </div>
            </article>`;
        }).join('');

        observeReveals('#galleryCommunityGrid .reveal');
    }

    function renderFeaturedMoments() {
        const container = document.getElementById('galleryFeaturedMoments');
        if (!container) return;

        const featured = GALLERY_DATA.featured || [];
        if (!featured.length) {
            container.innerHTML = '<p class="section-desc" style="text-align:center;padding:24px 0">Featured moments will appear here once gallery items are marked as featured in the CMS.</p>';
            return;
        }

        container.innerHTML = featured.map((item, index) => {
            const reverse = index % 2 === 1 ? ' featured-block--reverse' : '';
            const img = escapeHtml(item.image || item.thumbnail || 'img/lifted_hands.jpeg');
            const title = escapeHtml(item.title || 'Featured Moment');
            const desc = escapeHtml(item.caption || '');
            const eyebrow = escapeHtml(categoryLabel(item.category));
            const year = escapeHtml(item.year_label || item.year || '');
            const delay = index ? ` data-delay="${Math.min(index * 100, 400)}"` : '';

            return `<article class="featured-block${reverse} reveal"${delay}>
                <div class="featured-block__visual">
                    <img src="${img}" alt="${title}" loading="lazy" width="900" height="675">
                    <span class="featured-block__glow" aria-hidden="true"></span>
                </div>
                <div class="featured-block__text">
                    <p class="featured-block__eyebrow">${eyebrow}</p>
                    <h3 class="featured-block__title">${title}</h3>
                    ${desc ? `<p class="featured-block__desc">${desc}</p>` : ''}
                    ${year ? `<span class="featured-block__year">${year}</span>` : ''}
                </div>
            </article>`;
        }).join('');

        observeReveals('#galleryFeaturedMoments .reveal');
    }

    function renderSocialWall() {
        const grid = document.getElementById('gallerySocialGrid');
        if (!grid) return;

        const cards = (GALLERY_DATA.social && GALLERY_DATA.social.cards) || [];
        if (!cards.length) {
            grid.innerHTML = '<p class="section-desc" style="text-align:center;padding:24px 0;grid-column:1/-1">Social highlights will appear once gallery photos or social links are configured.</p>';
            return;
        }

        grid.innerHTML = cards.map(card => {
            const previews = (card.preview || []).map(src =>
                `<img src="${escapeHtml(src)}" alt="" loading="lazy" width="80" height="80">`
            ).join('');

            return `<a href="${escapeHtml(card.url)}" class="social-card ${escapeHtml(card.class)} reveal" data-delay="${card.delay || 0}" target="_blank" rel="noopener noreferrer">
                <div class="social-card__icon"><i class="${escapeHtml(card.icon)}" aria-hidden="true"></i></div>
                <p class="social-card__platform">${escapeHtml(card.platform)}</p>
                <p class="social-card__stat"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></p>
                <p class="social-card__label">Follow us</p>
                <div class="social-card__preview">${previews}</div>
            </a>`;
        }).join('');

        observeReveals('#gallerySocialGrid .reveal');
    }

    function populateEditionSelect(selectId) {
        const select = document.getElementById(selectId);
        if (!select || select.options.length > 1) return;

        (GALLERY_DATA.editions || []).forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            select.appendChild(opt);
        });
    }

    function rebindGalleryInteractions() {
        initLightbox();
        initVideoModal();
        initLazyLoad();
    }

    let galleryFilterBound = false;

    function bindGalleryFiltersOnce() {
        if (galleryFilterBound) return;
        galleryFilterBound = true;
        initGalleryFilter();
    }

    /* ---- Navbar ---- */
    function initNavbar() {
        const navbar = document.getElementById('navbar');
        const toggle = document.getElementById('navToggle');
        const menu = document.getElementById('navMenu');
        const backdrop = document.getElementById('navBackdrop');
        const links = document.querySelectorAll('.nav-link');

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

        const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 60);

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        toggle.addEventListener('click', () => {
            menu.classList.contains('open') ? closeMenu() : openMenu();
        });

        if (backdrop) backdrop.addEventListener('click', closeMenu);

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && menu.classList.contains('open')) closeMenu();
        });

        links.forEach(link => link.addEventListener('click', closeMenu));
    }

    /* ---- Video logos ---- */
    function initVideoLogos() {
        document.querySelectorAll('.logo-video__el').forEach(video => {
            video.muted = true;
            video.playsInline = true;
            const play = () => video.play().catch(() => {});
            if (video.readyState >= 2) play();
            else video.addEventListener('loadeddata', play, { once: true });
        });
    }

    /* ---- Hero particles ---- */
    function initHeroParticles() {
        const canvas = document.getElementById('galleryParticles');
        if (!canvas || prefersReducedMotion) return;

        const ctx = canvas.getContext('2d');
        let particles = [];
        let animId;

        const resize = () => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        };

        const createParticles = () => {
            particles = [];
            const count = Math.min(50, Math.floor(canvas.width / 24));
            for (let i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 2 + 0.5,
                    dx: (Math.random() - 0.5) * 0.4,
                    dy: (Math.random() - 0.5) * 0.4,
                    alpha: Math.random() * 0.5 + 0.2
                });
            }
        };

        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(251, 254, 6, ${p.alpha})`;
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
            });
            animId = requestAnimationFrame(draw);
        };

        resize();
        createParticles();
        draw();

        window.addEventListener('resize', () => {
            resize();
            createParticles();
        });

        return () => cancelAnimationFrame(animId);
    }

    /* ---- Counters ---- */
    function initCounters() {
        const counters = document.querySelectorAll('[data-count]');
        if (!counters.length) return;

        const animate = el => {
            const target = parseInt(el.dataset.count, 10);
            const suffix = el.dataset.suffix || '';
            const duration = 2000;
            const start = performance.now();

            const step = now => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(target * eased).toLocaleString() + suffix;
                if (progress < 1) requestAnimationFrame(step);
                else el.textContent = target.toLocaleString() + suffix;
            };

            requestAnimationFrame(step);
        };

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.dataset.counted) {
                    entry.target.dataset.counted = '1';
                    animate(entry.target);
                }
            });
        }, { threshold: 0.4 });

        counters.forEach(c => observer.observe(c));
    }

    /* ---- Gallery filter ---- */
    function initGalleryFilter() {
        const filters = document.querySelectorAll('.gallery-filters__btn');
        const grid = document.getElementById('galleryMasonry');

        if (!filters.length || !grid) return;

        const applyFilter = category => {
            const items = grid.querySelectorAll('.gallery-masonry__item');
            grid.classList.add('is-filtering');

            filters.forEach(btn => {
                const isActive = btn.dataset.filter === category;
                btn.classList.toggle('is-active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            setTimeout(() => {
                items.forEach(item => {
                    const cats = item.dataset.category || '';
                    const match = category === 'all' || cats.split(' ').includes(category);
                    item.classList.toggle('is-hidden', !match);
                });
                grid.classList.remove('is-filtering');
            }, 180);
        };

        filters.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.filter === 'videos') {
                    document.getElementById('videos')?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                    return;
                }
                applyFilter(btn.dataset.filter);
            });
        });
    }

    /* ---- Lightbox ---- */
    function initLightbox() {
        const lightbox = document.getElementById('lightbox');
        if (!lightbox) return;

        const img = document.getElementById('lightboxImg');
        const title = document.getElementById('lightboxTitle');
        const year = document.getElementById('lightboxYear');
        const caption = document.getElementById('lightboxCaption');
        const counter = document.getElementById('lightboxCounter');
        const btnPrev = lightbox.querySelector('.lightbox__btn--prev');
        const btnNext = lightbox.querySelector('.lightbox__btn--next');
        const btnClose = lightbox.querySelector('.lightbox__close');

        let items = [];
        let currentIndex = 0;
        let touchStartX = 0;

        const getVisibleItems = () =>
            Array.from(document.querySelectorAll('.gallery-masonry__item:not(.is-hidden):not(.is-video)'));

        const show = index => {
            items = getVisibleItems();
            if (!items.length) return;

            currentIndex = ((index % items.length) + items.length) % items.length;
            const item = items[currentIndex];
            const src = item.dataset.src || item.querySelector('img')?.src;
            const alt = item.querySelector('img')?.alt || '';

            img.src = src;
            img.alt = alt;
            title.textContent = item.dataset.title || '';
            year.textContent = item.dataset.year || '';
            caption.textContent = item.dataset.caption || '';
            counter.textContent = `${currentIndex + 1} / ${items.length}`;

            lightbox.classList.add('is-open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
            btnClose.focus();
        };

        const hide = () => {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
            img.src = '';
        };

        const prev = () => show(currentIndex - 1);
        const next = () => show(currentIndex + 1);

        document.querySelectorAll('.gallery-masonry__item:not(.is-video)').forEach(item => {
            item.addEventListener('click', () => {
                const visible = getVisibleItems();
                const idx = visible.indexOf(item);
                if (idx >= 0) show(idx);
            });
            item.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const visible = getVisibleItems();
                    const idx = visible.indexOf(item);
                    if (idx >= 0) show(idx);
                }
            });
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'button');
        });

        btnPrev.addEventListener('click', prev);
        btnNext.addEventListener('click', next);
        btnClose.addEventListener('click', hide);

        lightbox.addEventListener('click', e => {
            if (e.target === lightbox) hide();
        });

        document.addEventListener('keydown', e => {
            if (!lightbox.classList.contains('is-open')) return;
            if (e.key === 'Escape') hide();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        });

        lightbox.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        lightbox.addEventListener('touchend', e => {
            const diff = e.changedTouches[0].screenX - touchStartX;
            if (Math.abs(diff) > 50) diff > 0 ? prev() : next();
        }, { passive: true });
    }

    /* ---- Video modal ---- */
    function initVideoModal() {
        const modal = document.getElementById('videoModal');
        if (!modal) return;

        const frame = document.getElementById('videoModalFrame');
        const titleEl = document.getElementById('videoModalTitle');
        const btnClose = modal.querySelector('.video-modal__close');
        let activeCard = null;

        const open = card => {
            activeCard = card;
            const type = card.dataset.videoType;
            const src = card.dataset.videoSrc;
            const title = card.dataset.videoTitle || '';

            frame.innerHTML = '';

            if (type === 'youtube') {
                const iframe = document.createElement('iframe');
                iframe.src = `https://www.youtube.com/embed/${src}?autoplay=1&rel=0`;
                iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                iframe.allowFullscreen = true;
                iframe.title = title;
                frame.appendChild(iframe);
            } else {
                const video = document.createElement('video');
                video.src = src;
                video.controls = true;
                video.autoplay = true;
                video.playsInline = true;
                frame.appendChild(video);
            }

            titleEl.textContent = title;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
            btnClose.focus();
        };

        const close = () => {
            frame.innerHTML = '';
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
            if (activeCard) activeCard.focus();
            activeCard = null;
        };

        document.querySelectorAll('.video-card, .gallery-masonry__item.is-video').forEach(card => {
            card.addEventListener('click', () => open(card));
            card.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open(card);
                }
            });
            if (card.classList.contains('gallery-masonry__item')) {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
            }
        });

        btnClose.addEventListener('click', close);
        modal.addEventListener('click', e => { if (e.target === modal) close(); });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
        });
    }

    /* ---- Years timeline drag scroll ---- */
    function initYearsTimeline() {
        document.querySelectorAll('.years-timeline__track').forEach(track => {
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

    /* ---- Scroll reveal ---- */
    function observeReveals(selector) {
        const nodes = document.querySelectorAll(selector || '.reveal:not(.is-visible)');
        if (!nodes.length) return;

        if (prefersReducedMotion) {
            nodes.forEach(el => el.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = parseInt(entry.target.dataset.delay, 10) || 0;
                    setTimeout(() => entry.target.classList.add('is-visible'), delay);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        nodes.forEach(el => observer.observe(el));
    }

    function initScrollReveal() {
        observeReveals('.reveal:not(.is-visible)');
    }

    /* ---- Parallax ---- */
    function initParallax() {
        if (prefersReducedMotion) return;

        const visuals = document.querySelectorAll('.featured-block__visual img');

        window.addEventListener('scroll', () => {
            visuals.forEach(img => {
                const rect = img.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    const offset = (rect.top - window.innerHeight / 2) * 0.04;
                    img.style.transform = `scale(1.04) translateY(${offset}px)`;
                }
            });
        }, { passive: true });
    }

    /* ---- Lazy load enhancement ---- */
    function initLazyLoad() {
        if (!('IntersectionObserver' in window)) return;

        const images = document.querySelectorAll('img[loading="lazy"]');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        delete img.dataset.src;
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });

        images.forEach(img => observer.observe(img));
    }

    /* ---- Share form ---- */
    function bindShareFileZone(zone, input) {
        if (!zone || !input) return;

        const label = zone.querySelector('p');
        const defaultText = label ? label.textContent : '';

        const setFiles = fileList => {
            if (!fileList || !fileList.length) return;
            const dt = new DataTransfer();
            Array.from(fileList).forEach(file => dt.items.add(file));
            input.files = dt.files;
            if (label) {
                const count = input.files.length;
                label.textContent = count === 1
                    ? `1 file selected: ${input.files[0].name}`
                    : `${count} files selected`;
            }
        };

        input.addEventListener('change', () => {
            if (!input.files || !input.files.length) {
                if (label) label.textContent = defaultText;
                return;
            }
            if (label) {
                const count = input.files.length;
                label.textContent = count === 1
                    ? `1 file selected: ${input.files[0].name}`
                    : `${count} files selected`;
            }
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', e => {
            const files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length) {
                setFiles(files);
            }
        });
    }

    function initShareForm() {
        const form = document.getElementById('shareForm');
        const success = document.getElementById('shareSuccess');
        if (!form) return;

        bindShareFileZone(
            form.querySelector('#sharePhotos')?.closest('.share-form__file'),
            form.querySelector('#sharePhotos')
        );
        bindShareFileZone(
            form.querySelector('#shareVideos')?.closest('.share-form__file'),
            form.querySelector('#shareVideos')
        );

        populateEditionSelect('shareEdition');

        form.addEventListener('submit', e => {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            const name = form.querySelector('#shareName');
            const email = form.querySelector('#shareEmail');
            let valid = true;

            [name, email].forEach(field => {
                if (!field || !field.value.trim()) {
                    if (field) field.style.borderColor = '#e74c3c';
                    valid = false;
                } else if (field) {
                    field.style.borderColor = '';
                }
            });

            if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.style.borderColor = '#e74c3c';
                valid = false;
            }

            const testimony = form.querySelector('#shareTestimony');
            const photos = form.querySelector('#sharePhotos');
            const videos = form.querySelector('#shareVideos');
            const hasContent = (testimony && testimony.value.trim())
                || (photos && photos.files && photos.files.length)
                || (videos && videos.files && videos.files.length);

            if (!hasContent) {
                if (testimony) testimony.style.borderColor = '#e74c3c';
                valid = false;
            } else if (testimony) {
                testimony.style.borderColor = '';
            }

            if (!valid) return;

            const formData = new FormData(form);
            const token = getCsrfToken();
            if (token) {
                formData.set('csrf_token', token);
            }

            if (submitBtn) submitBtn.disabled = true;

            fetch('/api/sdtg-memory', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(res => res.json().then(body => ({ ok: res.ok, body })))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) {
                        throw new Error(body.message || 'Unable to submit your memories.');
                    }
                    form.hidden = true;
                    if (success) {
                        success.classList.add('is-visible');
                        success.setAttribute('aria-live', 'polite');
                    }
                })
                .catch(err => {
                    alert(err.message || 'Unable to submit your memories. Please try again.');
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    /* ---- Newsletter ---- */
    function initNewsletter() {
        const form = document.getElementById('newsletterForm');
        if (!form) return;

        form.addEventListener('submit', e => {
            e.preventDefault();
            const input = form.querySelector('input');
            const btn = form.querySelector('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            input.value = '';
            setTimeout(() => { btn.innerHTML = 'Subscribe'; }, 2000);
        });
    }

    /* ---- Scroll to top ---- */
    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;

        const toggle = () => btn.classList.toggle('visible', window.scrollY > 400);
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
        });
    }

    /* ---- CTA particles ---- */
    function initCtaParticles() {
        const canvas = document.getElementById('ctaParticles');
        if (!canvas || prefersReducedMotion) return;

        const ctx = canvas.getContext('2d');
        let particles = [];

        const resize = () => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            particles = Array.from({ length: 30 }, () => ({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 1.5 + 0.5,
                dx: (Math.random() - 0.5) * 0.3,
                dy: -Math.random() * 0.5 - 0.1,
                alpha: Math.random() * 0.4 + 0.1
            }));
        };

        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(245, 208, 97, ${p.alpha})`;
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.y < 0) p.y = canvas.height;
                if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
            });
            requestAnimationFrame(draw);
        };

        resize();
        draw();
        window.addEventListener('resize', resize);
    }

    /* ---- Smooth anchor scroll ---- */
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

    /* ---- Hero reveal on load ---- */
    function initHeroReveal() {
        document.querySelectorAll('.gallery-hero .reveal').forEach((el, i) => {
            const delay = parseInt(el.dataset.delay, 10) || i * 120;
            setTimeout(() => el.classList.add('is-visible'), 200 + delay);
        });
    }

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded', () => {
        initNavbar();
        initVideoLogos();
        initHeroReveal();
        initHeroParticles();
        loadGalleryData().then(() => {
            renderGallery();
            rebindGalleryInteractions();
            initScrollReveal();
            initCounters();
            initShareForm();
        });
        bindGalleryFiltersOnce();
        initYearsTimeline();
        initParallax();
        initNewsletter();
        initScrollTop();
        initCtaParticles();
        initSmoothScroll();
    });
})();

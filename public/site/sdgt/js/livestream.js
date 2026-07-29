/**
 * SDTG Livestream — Digital Broadcast Center
 * Future: replace LIVESTREAM_CONFIG fetch with PHP API / WebSocket
 */
(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const CRUSADE_DATE = new Date('2026-08-15T18:00:00+01:00').getTime();

    /* ---- CMS / backend placeholder config ---- */
    const LIVESTREAM_CONFIG = {
        isLive: false,
        viewerCount: 0,
        peakViewers: 0,
        sessionStart: null,
        streams: {
            youtube: { id: 'LXb3EKWsInQ', label: 'YouTube Live' },
            facebook: { id: '', label: 'Facebook Live', embed: '' },
            vimeo: { id: '76979871', label: 'Vimeo' },
            custom: { url: 'videos/worship-crowd.mp4', label: 'SDTG Direct' }
        },
        defaultPlatform: 'youtube',
        currentSession: {
            title: 'Opening Worship & Praise',
            minister: 'To Be Announced',
            ministry: '',
            country: '',
            worshipTeam: 'SDTG Mass Choir',
            photo: 'img/lifted_hands.jpeg',
            topic: ''
        },
        upcomingSession: {
            title: 'Keynote Session',
            minister: 'To Be Announced',
            time: 'TBA'
        },
        schedule: [
            { time: '6:00 PM', session: 'Pre-Service Worship', speaker: 'SDTG Worship Team', worship: 'Mass Choir' },
            { time: '7:00 PM', session: 'Opening Address', speaker: 'To Be Announced', worship: '—' },
            { time: '7:30 PM', session: 'Worship Encounter', speaker: 'To Be Announced', worship: 'To Be Announced' },
            { time: '8:00 PM', session: 'Keynote Message', speaker: 'To Be Announced', worship: '—' },
            { time: '9:30 PM', session: 'Altar Call & Prayer', speaker: 'Revival Team', worship: 'To Be Announced' },
            { time: '10:30 PM', session: 'Closing Worship', speaker: '—', worship: 'To Be Announced' }
        ],
        pastBroadcasts: [
            { title: 'SDTG 2025 — Glory Without Limits', year: '2025', duration: '3h 42m', thumb: 'img/lifted_hands.jpeg', video: 'videos/worship-hero.mp4', type: 'local' },
            { title: 'SDTG 2024 — Heaven\'s Sound', year: '2024', duration: '3h 18m', thumb: 'img/main1.jpg', video: 'LXb3EKWsInQ', type: 'youtube' },
            { title: 'SDTG 2023 — Revival Fire', year: '2023', duration: '2h 55m', thumb: 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=640&q=80', video: 'LXb3EKWsInQ', type: 'youtube' },
            { title: 'SDTG 2022 — Return Of Glory', year: '2022', duration: '2h 40m', thumb: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=640&q=80', video: 'videos/worship-crowd.mp4', type: 'local' }
        ],
        viewerCountries: [
            { name: 'Nigeria', code: 'NG', x: 48, y: 52, viewers: 4200 },
            { name: 'United Kingdom', code: 'GB', x: 46, y: 28, viewers: 1850 },
            { name: 'United States', code: 'US', x: 22, y: 35, viewers: 2100 },
            { name: 'Ghana', code: 'GH', x: 44, y: 55, viewers: 980 },
            { name: 'Kenya', code: 'KE', x: 54, y: 58, viewers: 720 },
            { name: 'South Africa', code: 'ZA', x: 52, y: 78, viewers: 640 },
            { name: 'Canada', code: 'CA', x: 18, y: 28, viewers: 890 },
            { name: 'Germany', code: 'DE', x: 50, y: 26, viewers: 560 },
            { name: 'Australia', code: 'AU', x: 82, y: 72, viewers: 430 },
            { name: 'Brazil', code: 'BR', x: 32, y: 68, viewers: 380 }
        ],
        chatSamples: [
            { user: 'Adaeze O.', country: '🇳🇬', msg: 'Glory to God! This worship is shifting atmospheres!', type: 'worship' },
            { user: 'James M.', country: '🇬🇧', msg: 'Praying for everyone watching from London 🙏', type: 'prayer' },
            { user: 'Grace W.', country: '🇰🇪', msg: 'Healing is flowing right now!', type: 'encourage' },
            { user: 'David T.', country: '🇺🇸', msg: 'Hallelujah! The presence of God is thick!', type: 'worship' },
            { user: 'Sarah K.', country: '🇨🇦', msg: 'Lord, save my family tonight', type: 'prayer' },
            { user: 'Emmanuel A.', country: '🇬🇭', msg: 'SDTG never disappoints. Fire! 🔥', type: 'encourage' }
        ]
    };

    let activePlatform = LIVESTREAM_CONFIG.defaultPlatform;
    let durationInterval = null;
    const API_URL = '../api/get-sdtg-livestream.php';

    function applyBootstrap(data) {
        if (!data || typeof data !== 'object') return;

        LIVESTREAM_CONFIG.isLive = !!data.is_live;
        LIVESTREAM_CONFIG.viewerCount = data.is_live
            ? Math.max(0, parseInt(data.current_viewers, 10) || 0)
            : Math.max(0, parseInt(data.peak_viewers, 10) || 0);
        LIVESTREAM_CONFIG.peakViewers = Math.max(0, parseInt(data.peak_viewers, 10) || 0);
        LIVESTREAM_CONFIG.sessionStart = data.session_started_at ? Date.parse(data.session_started_at) : null;

        if (data.streams && typeof data.streams === 'object') {
            Object.keys(data.streams).forEach((key) => {
                if (LIVESTREAM_CONFIG.streams[key]) {
                    Object.assign(LIVESTREAM_CONFIG.streams[key], data.streams[key]);
                }
            });
        }

        if (data.default_platform) {
            LIVESTREAM_CONFIG.defaultPlatform = data.default_platform;
            activePlatform = data.default_platform;
        }

        if (data.current_session && typeof data.current_session === 'object') {
            const cs = data.current_session;
            Object.assign(LIVESTREAM_CONFIG.currentSession, {
                title: cs.title ?? LIVESTREAM_CONFIG.currentSession.title,
                minister: cs.minister ?? LIVESTREAM_CONFIG.currentSession.minister,
                ministry: cs.ministry ?? LIVESTREAM_CONFIG.currentSession.ministry,
                country: cs.country ?? LIVESTREAM_CONFIG.currentSession.country,
                worshipTeam: cs.worshipTeam ?? cs.worship_team ?? LIVESTREAM_CONFIG.currentSession.worshipTeam,
                photo: cs.photo ?? LIVESTREAM_CONFIG.currentSession.photo,
                topic: cs.topic ?? LIVESTREAM_CONFIG.currentSession.topic
            });
        }

        if (data.upcoming_session && typeof data.upcoming_session === 'object') {
            Object.assign(LIVESTREAM_CONFIG.upcomingSession, data.upcoming_session);
        }

        if (Array.isArray(data.schedule) && data.schedule.length) {
            LIVESTREAM_CONFIG.schedule = data.schedule;
        }

        if (Array.isArray(data.past_broadcasts) && data.past_broadcasts.length) {
            LIVESTREAM_CONFIG.pastBroadcasts = data.past_broadcasts;
        }
    }

    function loadLivestreamData() {
        if (window.SDTG_LIVESTREAM_BOOTSTRAP) {
            applyBootstrap(window.SDTG_LIVESTREAM_BOOTSTRAP);
            return Promise.resolve(window.SDTG_LIVESTREAM_BOOTSTRAP);
        }

        return fetch(API_URL, { credentials: 'same-origin' })
            .then((res) => res.json())
            .then((body) => {
                if (!body || !body.success) {
                    throw new Error((body && body.message) || 'Unable to load livestream data.');
                }
                applyBootstrap(body.data);
                return body.data;
            })
            .catch(() => null);
    }

    function initPageFade() {
        document.body.classList.add('livestream-page');
        requestAnimationFrame(() => document.body.classList.add('is-loaded'));
    }

    function getScrollRevealElements() {
        return Array.from(document.querySelectorAll('.reveal')).filter(el => !el.closest('.ls-hero'));
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
            particles = Array.from({ length: 35 }, () => ({
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
        document.querySelectorAll('.ls-hero .reveal').forEach((el, i) => {
            const delay = 200 + (parseInt(el.dataset.delay, 10) || i * 100);
            el.style.transitionDelay = `${delay}ms`;
            el.classList.add('is-visible');
        });
    }

    function revealElement(el) {
        const delay = parseInt(el.dataset.delay, 10) || 0;
        if (delay <= 0) {
            el.classList.add('is-visible');
            return;
        }
        setTimeout(() => el.classList.add('is-visible'), delay);
    }

    function revealBroadcastCards() {
        document.querySelectorAll(
            '#lsExperiencePanel .ls-exp-card, #lsMinisterNow .ls-minister-card, #lsSchedule .ls-schedule-item, #lsPastBroadcasts .ls-broadcast-card'
        ).forEach(el => el.classList.add('is-visible'));
    }

    function initScrollReveal() {
        const reveals = getScrollRevealElements();

        if (prefersReducedMotion) {
            reveals.forEach(el => el.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    revealElement(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px 0px 0px' });

        reveals.forEach(el => {
            observer.observe(el);
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                revealElement(el);
                observer.unobserve(el);
            }
        });
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function initLiveStatus() {
        const offline = document.getElementById('lsStatusOffline');
        const live = document.getElementById('lsStatusLive');
        const els = {
            days: document.getElementById('lsCdDays'),
            hours: document.getElementById('lsCdHours'),
            minutes: document.getElementById('lsCdMinutes'),
            seconds: document.getElementById('lsCdSeconds')
        };
        const viewerEl = document.getElementById('lsViewerCount');
        const durationEl = document.getElementById('lsSessionDuration');

        if (LIVESTREAM_CONFIG.isLive) {
            offline?.classList.add('hidden');
            live?.classList.remove('hidden');
            LIVESTREAM_CONFIG.sessionStart = LIVESTREAM_CONFIG.sessionStart || Date.now() - 3600000;

            if (viewerEl) {
                let count = LIVESTREAM_CONFIG.viewerCount;
                viewerEl.textContent = count.toLocaleString();
                if (!prefersReducedMotion) {
                    setInterval(() => {
                        count += Math.floor(Math.random() * 5) - 1;
                        count = Math.max(1000, count);
                        viewerEl.textContent = count.toLocaleString();
                    }, 4000);
                }
            }

            if (durationEl) {
                durationInterval = setInterval(() => {
                    const diff = Date.now() - LIVESTREAM_CONFIG.sessionStart;
                    const h = Math.floor(diff / 3600000);
                    const m = Math.floor((diff % 3600000) / 60000);
                    const s = Math.floor((diff % 60000) / 1000);
                    durationEl.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
                }, 1000);
            }
            return;
        }

        live?.classList.add('hidden');
        offline?.classList.remove('hidden');

        function tick() {
            const diff = CRUSADE_DATE - Date.now();
            if (diff <= 0) {
                if (els.days) els.days.textContent = '00';
                return;
            }
            if (els.days) els.days.textContent = pad(Math.floor(diff / 86400000));
            if (els.hours) els.hours.textContent = pad(Math.floor((diff % 86400000) / 3600000));
            if (els.minutes) els.minutes.textContent = pad(Math.floor((diff % 3600000) / 60000));
            if (els.seconds) els.seconds.textContent = pad(Math.floor((diff % 60000) / 1000));
        }
        tick();
        setInterval(tick, 1000);
    }

    function getOfflinePlayerHtml() {
        return `
            <div class="ls-player__offline">
                <video class="ls-player__offline-video" src="videos/worship-hero.mp4" autoplay muted loop playsinline poster="img/lifted_hands.jpeg" aria-hidden="true"></video>
                <div class="ls-player__placeholder ls-player__placeholder--offline">
                    <span class="ls-player__offline-badge"><i class="fas fa-clock" aria-hidden="true"></i> Broadcast Preview</span>
                    <i class="fas fa-satellite-dish" aria-hidden="true"></i>
                    <p class="ls-player__offline-title">Send Down Thy Glory Live</p>
                    <p>Global broadcast begins <strong>15 August 2026</strong>. The live feed will appear here automatically when we go on air.</p>
                    <div class="ls-player__offline-actions">
                        <a href="registration" class="btn btn--primary">Register Free</a>
                        <a href="gallery" class="btn btn--outline">Watch Highlights</a>
                    </div>
                </div>
            </div>`;
    }

    function getEmbedHtml(platform) {
        const s = LIVESTREAM_CONFIG.streams[platform];
        if (!s) return getOfflinePlayerHtml();

        if (!LIVESTREAM_CONFIG.isLive) {
            if (platform === 'custom' && s.url) {
                return `<video src="${s.url}" controls playsinline poster="img/lifted_hands.jpeg"></video>`;
            }
            return getOfflinePlayerHtml();
        }

        if (platform === 'youtube' && s.id) {
            return `<iframe src="https://www.youtube.com/embed/${s.id}?rel=0&autoplay=1" title="SDTG Livestream" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>`;
        }
        if (platform === 'vimeo' && s.id) {
            return `<iframe src="https://player.vimeo.com/video/${s.id}?title=0&byline=0&autoplay=1" title="SDTG Livestream" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
        }
        if (platform === 'facebook') {
            if (s.embed) {
                return s.embed;
            }
            return `<div class="ls-player__placeholder"><i class="fab fa-facebook" aria-hidden="true"></i><p>Facebook Live embed — connect your stream URL in admin</p></div>`;
        }
        if (platform === 'custom' && s.url) {
            return `<video src="${s.url}" controls playsinline poster="img/lifted_hands.jpeg"></video>`;
        }
        return `<div class="ls-player__placeholder"><i class="fas fa-broadcast-tower" aria-hidden="true"></i><p>Stream will appear here when live</p></div>`;
    }

    function initPlayer() {
        const frame = document.getElementById('lsPlayerFrame');
        const theatreBtn = document.getElementById('lsTheatreBtn');
        const fullscreenBtn = document.getElementById('lsFullscreenBtn');
        const section = document.getElementById('player');
        const tabs = document.querySelectorAll('.ls-platform-tab');
        const tabBar = document.querySelector('.ls-platform-tabs');

        function loadPlatform(platform) {
            activePlatform = platform;
            tabs.forEach(t => t.classList.toggle('is-active', t.dataset.platform === platform));
            if (frame) frame.innerHTML = getEmbedHtml(platform);
        }

        if (tabBar) {
            tabBar.classList.toggle('hidden', !LIVESTREAM_CONFIG.isLive);
        }

        tabs.forEach(tab => tab.addEventListener('click', () => loadPlatform(tab.dataset.platform)));
        loadPlatform(activePlatform);

        theatreBtn?.addEventListener('click', () => {
            section?.classList.toggle('is-theatre');
            theatreBtn.setAttribute('aria-pressed', section?.classList.contains('is-theatre') ? 'true' : 'false');
        });

        fullscreenBtn?.addEventListener('click', () => {
            const wrap = document.getElementById('lsPlayerWrap');
            if (wrap?.requestFullscreen) wrap.requestFullscreen();
            else if (wrap?.webkitRequestFullscreen) wrap.webkitRequestFullscreen();
        });

        document.getElementById('lsWatchNowBtn')?.addEventListener('click', e => {
            e.preventDefault();
            document.getElementById('player')?.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
        });
    }

    function renderExperiencePanel() {
        const c = LIVESTREAM_CONFIG.currentSession;
        const u = LIVESTREAM_CONFIG.upcomingSession;
        const el = document.getElementById('lsExperiencePanel');
        if (!el) return;
        el.innerHTML = `
            <div class="ls-exp-card ls-exp-card--current is-visible">
                <span class="ls-exp-card__label"><i class="fas fa-circle" aria-hidden="true"></i> Current Session</span>
                <h3>${c.title}</h3>
                <p class="ls-exp-card__minister">${c.minister}</p>
                <p class="ls-exp-card__meta">${c.ministry} · ${c.country}</p>
            </div>
            <div class="ls-exp-card is-visible">
                <span class="ls-exp-card__label"><i class="fas fa-microphone" aria-hidden="true"></i> Ministering Now</span>
                <h3>${c.minister}</h3>
                <p class="ls-exp-card__meta">${c.topic}</p>
            </div>
            <div class="ls-exp-card is-visible">
                <span class="ls-exp-card__label"><i class="fas fa-music" aria-hidden="true"></i> Worship Team</span>
                <h3>${c.worshipTeam}</h3>
                <p class="ls-exp-card__meta">Leading the congregation in praise</p>
            </div>
            <div class="ls-exp-card ls-exp-card--upcoming is-visible">
                <span class="ls-exp-card__label"><i class="fas fa-clock" aria-hidden="true"></i> Up Next · ${u.time}</span>
                <h3>${u.title}</h3>
                <p class="ls-exp-card__minister">${u.minister}</p>
            </div>`;
    }

    function renderMinisteringNow() {
        const c = LIVESTREAM_CONFIG.currentSession;
        const el = document.getElementById('lsMinisterNow');
        if (!el) return;
        el.innerHTML = `
            <div class="ls-minister-card is-visible">
                <div class="ls-minister-card__photo"><img src="${c.photo}" alt="${c.minister}" loading="lazy" width="400" height="500"></div>
                <div class="ls-minister-card__body">
                    <span class="ls-minister-card__live"><span class="ls-live-dot"></span> Ministering Now</span>
                    <h2>${c.minister}</h2>
                    <p class="ls-minister-card__ministry">${c.ministry}</p>
                    <p class="ls-minister-card__country"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> ${c.country}</p>
                    <p class="ls-minister-card__topic">${c.topic}</p>
                    <p class="ls-minister-card__session">${c.title}</p>
                </div>
            </div>`;
    }

    function renderSchedule() {
        const track = document.getElementById('lsSchedule');
        if (!track) return;
        track.innerHTML = LIVESTREAM_CONFIG.schedule.map((item, i) => `
            <article class="ls-schedule-item is-visible">
                <div class="ls-schedule-item__time">${item.time}</div>
                <div class="ls-schedule-item__line" aria-hidden="true"><span></span></div>
                <div class="ls-schedule-item__body">
                    <h3>${item.session}</h3>
                    <p><strong>Speaker:</strong> ${item.speaker}</p>
                    <p><strong>Worship:</strong> ${item.worship}</p>
                </div>
            </article>`).join('');
    }

    function renderPastBroadcasts() {
        const grid = document.getElementById('lsPastBroadcasts');
        if (!grid) return;
        grid.innerHTML = LIVESTREAM_CONFIG.pastBroadcasts.map((b, i) => `
            <article class="ls-broadcast-card is-visible" tabindex="0" role="button"
                data-video-type="${b.type}" data-video-src="${b.video}" data-video-title="${b.title}" aria-label="Play ${b.title}">
                <div class="ls-broadcast-card__thumb">
                    <img src="${b.thumb}" alt="" loading="lazy" width="640" height="360">
                    <div class="ls-broadcast-card__play"><i class="fas fa-play" aria-hidden="true"></i></div>
                    <span class="ls-broadcast-card__duration">${b.duration}</span>
                </div>
                <div class="ls-broadcast-card__body">
                    <span class="ls-broadcast-card__year">${b.year}</span>
                    <h3>${b.title}</h3>
                </div>
            </article>`).join('');

        grid.querySelectorAll('.ls-broadcast-card').forEach(card => {
            const open = () => openVideoModal(card.dataset.videoType, card.dataset.videoSrc, card.dataset.videoTitle);
            card.addEventListener('click', open);
            card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
        });
    }

    function renderViewerMap() {
        const map = document.getElementById('lsViewerMap');
        const list = document.getElementById('lsCountryList');
        if (!map) return;

        map.innerHTML = LIVESTREAM_CONFIG.viewerCountries.map(c => `
            <button type="button" class="ls-map-marker" style="left:${c.x}%;top:${c.y}%;" 
                aria-label="${c.name}: ${c.viewers.toLocaleString()} viewers" data-country="${c.name}">
                <span class="ls-map-marker__pulse"></span>
                <span class="ls-map-marker__dot"></span>
            </button>`).join('');

        if (list) {
            list.innerHTML = LIVESTREAM_CONFIG.viewerCountries.slice(0, 8).map(c => `
                <li><span>${c.name}</span><strong>${c.viewers.toLocaleString()}</strong></li>`).join('');
        }

        const total = LIVESTREAM_CONFIG.viewerCountries.reduce((s, c) => s + c.viewers, 0);
        document.getElementById('lsMapTotal')?.textContent = total.toLocaleString();
        document.getElementById('lsMapCountries')?.textContent = LIVESTREAM_CONFIG.viewerCountries.length;
    }

    function renderChat() {
        const feed = document.getElementById('lsChatFeed');
        if (!feed) return;
        feed.innerHTML = LIVESTREAM_CONFIG.chatSamples.map(m => `
            <div class="ls-chat-msg ls-chat-msg--${m.type}">
                <div class="ls-chat-msg__header"><strong>${m.user}</strong> <span>${m.country}</span></div>
                <p>${m.msg}</p>
            </div>`).join('');

        if (!prefersReducedMotion) {
            let idx = 0;
            setInterval(() => {
                const msgs = LIVESTREAM_CONFIG.chatSamples;
                const m = msgs[idx % msgs.length];
                const el = document.createElement('div');
                el.className = `ls-chat-msg ls-chat-msg--${m.type} ls-chat-msg--new`;
                el.innerHTML = `<div class="ls-chat-msg__header"><strong>${m.user}</strong> <span>${m.country}</span></div><p>${m.msg}</p>`;
                feed.appendChild(el);
                feed.scrollTop = feed.scrollHeight;
                if (feed.children.length > 12) feed.removeChild(feed.firstChild);
                idx++;
            }, 6000);
        }
    }

    function initForms() {
        const prayerForm = document.getElementById('lsPrayerForm');
        const testimonyForm = document.getElementById('lsTestimonyForm');

        prayerForm?.addEventListener('submit', e => {
            e.preventDefault();
            const success = document.getElementById('lsPrayerSuccess');
            prayerForm.classList.add('hidden');
            success?.classList.remove('hidden');
            success?.classList.add('is-visible');
        });

        testimonyForm?.addEventListener('submit', e => {
            e.preventDefault();
            const success = document.getElementById('lsTestimonySuccess');
            testimonyForm.classList.add('hidden');
            success?.classList.remove('hidden');
            success?.classList.add('is-visible');
            if (!prefersReducedMotion) spawnConfetti(document.getElementById('lsTestimonySection'));
        });
    }

    function spawnConfetti(container) {
        if (!container || prefersReducedMotion) return;
        for (let i = 0; i < 24; i++) {
            const p = document.createElement('span');
            p.className = 'ls-confetti';
            p.style.left = `${Math.random() * 100}%`;
            p.style.background = ['#fbfe06', '#fdff66', '#4a2d7a', '#ffffff'][Math.floor(Math.random() * 4)];
            p.style.animationDelay = `${Math.random() * 0.5}s`;
            container.appendChild(p);
            setTimeout(() => p.remove(), 3000);
        }
    }

    function openVideoModal(type, src, title) {
        const modal = document.getElementById('lsVideoModal');
        const frame = document.getElementById('lsVideoFrame');
        const titleEl = document.getElementById('lsVideoTitle');
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
        const modal = document.getElementById('lsVideoModal');
        const frame = document.getElementById('lsVideoFrame');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
        if (frame) frame.innerHTML = '';
    }

    function initModals() {
        document.getElementById('lsVideoModal')?.addEventListener('click', e => {
            if (e.target.id === 'lsVideoModal' || e.target.closest('.ls-video-modal__close')) closeVideoModal();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVideoModal(); });
    }

    function initShare() {
        const inviteBtn = document.getElementById('lsInviteBtn');
        const inviteLabel = inviteBtn ? inviteBtn.textContent.trim() : 'Invite Friends';
        inviteBtn?.addEventListener('click', () => {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({ title: 'Watch SDTG Live', text: 'Join me for Send Down Thy Glory live!', url });
            } else {
                navigator.clipboard?.writeText(url);
                if (inviteBtn) {
                    inviteBtn.innerHTML = '<i class="fas fa-check"></i> Link Copied';
                    setTimeout(() => { inviteBtn.innerHTML = '<i class="fas fa-user-plus"></i> ' + inviteLabel; }, 2500);
                }
            }
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

    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' }); }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadLivestreamData().finally(() => {
        initPageFade();
        initNavbar();
        initVideoLogos();
        initParticles('lsHeroParticles');
        initParticles('lsCtaParticles');
        initLiveStatus();
        initPlayer();
        renderExperiencePanel();
        renderMinisteringNow();
        renderSchedule();
        renderPastBroadcasts();
        renderViewerMap();
        renderChat();
        initForms();
        initModals();
        initShare();
        initHeroReveal();
        revealBroadcastCards();
        initScrollReveal();
        document.documentElement.classList.add('js-ready');
        initSmoothScroll();
        initNewsletter();
        initScrollTop();
        });
    });

    window.SDTG_LIVESTREAM = LIVESTREAM_CONFIG;
})();

/**
 * SDTG Registration Page — Interactions
 */
(function () {
    'use strict';

    const CRUSADE_DATE = new Date('2026-08-15T09:00:00+01:00').getTime();
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const TOTAL_STEPS = 5;

    let formData = {};
    let currentStep = 1;
    let isSubmitting = false;
    let liveStats = { registrations: 0, countries: 0, churches: 0, volunteers: 0 };

    function apiBase() {
        const bodyBase = document.body.getAttribute('data-api-base');
        if (bodyBase) return bodyBase.replace(/\/$/, '');
        return '../api';
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function loadRegistrationStats() {
        return fetch(apiBase() + '/get-sdtg-registration-stats.php', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data || !data.success || !data.stats) return;
                liveStats = data.stats;
                document.querySelectorAll('[data-stat]').forEach(el => {
                    const key = el.getAttribute('data-stat');
                    if (key && Object.prototype.hasOwnProperty.call(liveStats, key)) {
                        el.dataset.count = String(liveStats[key] || 0);
                    }
                });
            })
            .catch(() => {});
    }

    /* ---- Page load ---- */
    function initPageLoad() {
        document.body.classList.add('reg-page');
        requestAnimationFrame(() => {
            document.body.classList.add('is-loaded');
        });
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

        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 60);
        }, { passive: true });
        navbar.classList.toggle('scrolled', window.scrollY > 60);

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

    /* ---- Particles ---- */
    function initParticles(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || prefersReducedMotion) return;

        const ctx = canvas.getContext('2d');
        let particles = [];

        const resize = () => {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
            particles = Array.from({ length: Math.min(45, Math.floor(canvas.width / 28)) }, () => ({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 2 + 0.5,
                dx: (Math.random() - 0.5) * 0.35,
                dy: (Math.random() - 0.5) * 0.35,
                alpha: Math.random() * 0.45 + 0.15
            }));
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
            requestAnimationFrame(draw);
        };

        resize();
        draw();
        window.addEventListener('resize', resize);
    }

    /* ---- Hero parallax ---- */
    function initHeroParallax() {
        if (prefersReducedMotion) return;

        const hero = document.querySelector('.reg-hero');
        if (!hero) return;

        window.addEventListener('scroll', () => {
            const offset = window.scrollY * 0.25;
            hero.style.setProperty('--reg-parallax', `${offset}px`);
            hero.classList.toggle('is-parallax', window.scrollY < window.innerHeight);
        }, { passive: true });
    }

    /* ---- Hero reveal ---- */
    function initHeroReveal() {
        document.querySelectorAll('.reg-hero .reveal').forEach((el, i) => {
            setTimeout(() => el.classList.add('is-visible'), 200 + (parseInt(el.dataset.delay, 10) || i * 100));
        });
    }

    /* ---- Journey steps sync ---- */
    function updateJourney(formStep) {
        document.querySelectorAll('.reg-journey__step').forEach((el, i) => {
            const j = i + 1;
            let active = false;
            let done = false;

            if (formStep <= 4) {
                active = j === 1;
            } else if (formStep === 5) {
                done = j === 1;
                active = j === 2;
            }

            el.classList.toggle('is-active', active);
            el.classList.toggle('is-done', done);
        });
    }

    function updateJourneyComplete() {
        document.querySelectorAll('.reg-journey__step').forEach((el, i) => {
            el.classList.toggle('is-done', i < 2);
            el.classList.toggle('is-active', i === 2);
        });
    }

    /* ---- Countdown ---- */
    function initCountdown() {
        const timer = document.getElementById('regCountdownTimer');
        if (!timer) return;

        const els = {
            days: document.getElementById('regCdDays'),
            hours: document.getElementById('regCdHours'),
            minutes: document.getElementById('regCdMinutes'),
            seconds: document.getElementById('regCdSeconds')
        };

        let prev = {};

        const pad = n => String(n).padStart(2, '0');

        const flip = (el, value) => {
            if (!el || prev[el.id] === value) return;
            el.classList.remove('flip');
            void el.offsetWidth;
            el.classList.add('flip');
            el.textContent = value;
            prev[el.id] = value;
        };

        const tick = () => {
            const diff = CRUSADE_DATE - Date.now();
            if (diff <= 0) {
                Object.values(els).forEach(el => { if (el) el.textContent = '00'; });
                return;
            }

            flip(els.days, pad(Math.floor(diff / 86400000)));
            flip(els.hours, pad(Math.floor((diff % 86400000) / 3600000)));
            flip(els.minutes, pad(Math.floor((diff % 3600000) / 60000)));
            flip(els.seconds, pad(Math.floor((diff % 60000) / 1000)));
        };

        tick();
        setInterval(tick, 1000);
    }

    /* ---- Animated counters ---- */
    function initCounters() {
        const counters = document.querySelectorAll('[data-count]');
        if (!counters.length) return;

        const animate = el => {
            const target = parseInt(el.dataset.count, 10);
            const suffix = el.dataset.suffix || '';
            const duration = 2200;
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
        }, { threshold: 0.35 });

        counters.forEach(c => observer.observe(c));
    }

    /* ---- Form validation ---- */
    function validateStep(step) {
        const stepEl = document.querySelector(`.reg-form__step[data-step="${step}"]`);
        if (!stepEl) return true;

        let valid = true;
        const fields = stepEl.querySelectorAll('input, select, textarea');

        fields.forEach(field => {
            const wrap = field.closest('.float-field');
            if (!wrap) return;

            let fieldValid = true;
            wrap.classList.remove('is-error');

            if (field.hasAttribute('required') && !field.value.trim()) {
                fieldValid = false;
            }

            if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                fieldValid = false;
                const err = wrap.querySelector('.float-field__error');
                if (err) err.textContent = 'Please enter a valid email address';
            }

            if (field.type === 'tel' && field.value && !/^[+\d\s()-]{7,}$/.test(field.value)) {
                fieldValid = false;
                const err = wrap.querySelector('.float-field__error');
                if (err) err.textContent = 'Please enter a valid phone number';
            }

            if (!fieldValid) {
                valid = false;
                wrap.classList.add('is-error');
                const err = wrap.querySelector('.float-field__error');
                if (err && !err.textContent) err.textContent = 'This field is required';
            }
        });

        /* Radio groups on step 3 */
        if (step === 3) {
            ['first_time', 'attendance_mode'].forEach(name => {
                const checked = stepEl.querySelector(`input[name="${name}"]:checked`);
                const group = stepEl.querySelector(`[data-group="${name}"]`);
                if (!checked && group) {
                    valid = false;
                    group.classList.add('is-error');
                } else if (group) {
                    group.classList.remove('is-error');
                }
            });
        }

        if (!valid) {
            const firstError = stepEl.querySelector('.is-error input, .is-error select, .is-error textarea');
            if (firstError) firstError.focus();
        }

        return valid;
    }

    function collectStepData(step) {
        const stepEl = document.querySelector(`.reg-form__step[data-step="${step}"]`);
        if (!stepEl) return;

        stepEl.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.type === 'radio') {
                if (field.checked) formData[field.name] = field.value;
            } else if (field.type === 'checkbox') {
                formData[field.name] = field.checked;
            } else {
                formData[field.name] = field.value.trim();
            }
        });
    }

    function populateReview() {
        const labels = {
            first_name: 'First Name',
            last_name: 'Last Name',
            email: 'Email',
            phone: 'Phone',
            gender: 'Gender',
            date_of_birth: 'Date of Birth',
            country: 'Country',
            state: 'State/Province',
            city: 'City',
            church_name: 'Church',
            first_time: 'First Time',
            attendance_mode: 'Attendance',
            volunteer_interest: 'Volunteer',
            choir_participation: 'Choir',
            accommodation_request: 'Accommodation',
            prayer_request: 'Prayer Request',
            expectations: 'Expectations'
        };

        const groups = {
            personal: ['first_name', 'last_name', 'email', 'phone', 'gender', 'date_of_birth'],
            location: ['country', 'state', 'city', 'church_name'],
            participation: ['first_time', 'attendance_mode', 'volunteer_interest', 'choir_participation', 'accommodation_request'],
            additional: ['prayer_request', 'expectations']
        };

        Object.entries(groups).forEach(([groupId, keys]) => {
            const container = document.getElementById(`review-${groupId}`);
            if (!container) return;

            container.innerHTML = keys.map(key => {
                let val = formData[key] || '—';
                if (key === 'volunteer_interest' || key === 'choir_participation' || key === 'accommodation_request') {
                    val = formData[key] === 'yes' || formData[key] === true ? 'Yes' : formData[key] === 'no' ? 'No' : val;
                }
                return `<div class="reg-review__row"><span>${labels[key]}</span><span>${val}</span></div>`;
            }).join('');
        });
    }

    function goToStep(step) {
        if (step < 1 || step > TOTAL_STEPS) return;

        const current = document.querySelector('.reg-form__step.is-active');
        const next = document.querySelector(`.reg-form__step[data-step="${step}"]`);

        if (current && current !== next) {
            current.classList.add('is-exiting');
            current.classList.remove('is-active');
            setTimeout(() => current.classList.remove('is-exiting'), 450);
        }

        if (next) {
            setTimeout(() => next.classList.add('is-active'), current && current !== next ? 80 : 0);
        }

        currentStep = step;

        const fill = document.getElementById('regProgressFill');
        const stepLabel = document.getElementById('regProgressStep');
        const pct = document.getElementById('regProgressPct');

        if (fill) fill.style.width = `${(step / TOTAL_STEPS) * 100}%`;
        if (stepLabel) stepLabel.textContent = `Step ${step} of ${TOTAL_STEPS}`;
        if (pct) pct.textContent = `${Math.round((step / TOTAL_STEPS) * 100)}%`;

        const btnPrev = document.getElementById('regBtnPrev');
        const btnNext = document.getElementById('regBtnNext');
        const btnSubmit = document.getElementById('regBtnSubmit');

        if (btnPrev) btnPrev.disabled = step === 1;
        if (btnNext) btnNext.classList.toggle('hidden', step === TOTAL_STEPS);
        if (btnSubmit) btnSubmit.classList.toggle('hidden', step !== TOTAL_STEPS);

        updateJourney(step);

        if (step === TOTAL_STEPS) populateReview();

        const titles = [
            'Personal Information',
            'Location Information',
            'Participation Details',
            'Additional Information',
            'Review & Confirm'
        ];
        const titleEl = document.getElementById('regStepTitle');
        const descEl = document.getElementById('regStepDesc');
        if (titleEl) titleEl.textContent = titles[step - 1];
        if (descEl) {
            descEl.textContent = step === TOTAL_STEPS
                ? 'Please review your details before submitting your registration.'
                : 'All fields marked with required validation must be completed to continue.';
        }
    }

    function initRegistrationForm() {
        const form = document.getElementById('sdtgRegistrationForm');
        if (!form) return;

        document.getElementById('regBtnNext')?.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                collectStepData(currentStep);
                goToStep(currentStep + 1);
            }
        });

        document.getElementById('regBtnPrev')?.addEventListener('click', () => {
            collectStepData(currentStep);
            goToStep(currentStep - 1);
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (isSubmitting) return;
            if (!validateStep(currentStep)) return;

            collectStepData(currentStep);

            const submitBtn = document.getElementById('regBtnSubmit');
            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Submitting…';
            }

            const payload = Object.assign({}, formData, {
                event_id: 'sdtg-2026',
                form_version: '1.0',
                csrf_token: csrfToken()
            });

            fetch(apiBase() + '/submit-sdtg-registration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
                .then(res => res.json().then(body => ({ ok: res.ok, body })))
                .then(({ ok, body }) => {
                    if (!ok || !body.success) {
                        throw new Error((body && body.message) || 'Registration failed. Please try again.');
                    }

                    const ref = (body.data && body.data.registration_code) || 'SDTG';
                    form.dataset.lastPayload = JSON.stringify(Object.assign({}, payload, {
                        registration_ref: ref,
                        registered_at: new Date().toISOString()
                    }));

                    showSuccessModal(ref);
                    updateJourneyComplete();
                    launchConfetti();
                    loadRegistrationStats().then(() => initCounters());
                })
                .catch(err => {
                    alert(err.message || 'Unable to submit registration. Please try again.');
                })
                .finally(() => {
                    isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> Submit Registration';
                    }
                });
        });

        /* Clear errors on input */
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', () => {
                const wrap = field.closest('.float-field');
                if (wrap) {
                    wrap.classList.remove('is-error');
                    const err = wrap.querySelector('.float-field__error');
                    if (err) err.textContent = '';
                }
            });
        });

        /* Floating label sync for date/select */
        form.querySelectorAll('select, input[type="date"]').forEach(field => {
            const sync = () => {
                field.closest('.float-field')?.classList.toggle('has-value', !!field.value);
            };
            field.addEventListener('change', sync);
            field.addEventListener('input', sync);
            sync();
        });

        goToStep(1);
    }

    /* ---- Confetti ---- */
    function launchConfetti() {
        if (prefersReducedMotion || document.querySelector('.confetti-canvas')) return;

        const canvas = document.createElement('canvas');
        canvas.className = 'confetti-canvas';
        canvas.style.cssText = 'position:fixed;inset:0;z-index:10001;pointer-events:none;';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const colors = ['#fbfe06', '#fdff66', '#ffffff', '#4a2d7a', '#2d1b4e'];
        const pieces = Array.from({ length: 150 }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * -canvas.height,
            w: Math.random() * 10 + 4,
            h: Math.random() * 6 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            speed: Math.random() * 4 + 2,
            drift: (Math.random() - 0.5) * 3
        }));

        let frame = 0;
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                p.y += p.speed;
                p.x += p.drift;
                p.rotation += 4;
                if (p.y > canvas.height) {
                    p.y = -20;
                    p.x = Math.random() * canvas.width;
                }
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });
            frame++;
            if (frame < 350) requestAnimationFrame(animate);
            else canvas.remove();
        }

        animate();
    }

    /* ---- Success modal ---- */
    function showSuccessModal(ref) {
        const modal = document.getElementById('successModal');
        const refEl = document.getElementById('successRef');
        if (refEl) refEl.textContent = ref;
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
        }
    }

    function initSuccessModal() {
        const modal = document.getElementById('successModal');
        if (!modal) return;

        document.getElementById('successDownload')?.addEventListener('click', () => {
            const form = document.getElementById('sdtgRegistrationForm');
            const payload = form?.dataset.lastPayload || '{}';
            const blob = new Blob([payload], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sdtg-registration-confirmation.json';
            a.click();
            URL.revokeObjectURL(url);
        });

        document.getElementById('successShare')?.addEventListener('click', () => {
            const text = encodeURIComponent('I just registered for Send Down Thy Glory 2026! Join me for an unforgettable worship encounter.');
            const url = encodeURIComponent(window.location.href);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank', 'noopener');
        });

        document.getElementById('successHome')?.addEventListener('click', () => {
            window.location.href = './';
        });
    }

    /* ---- Testimonial slider ---- */
    function initTestimonialSlider() {
        const track = document.getElementById('regTestimonialTrack');
        if (!track) return;

        const slides = track.querySelectorAll('.reg-slide');
        const dotsContainer = document.getElementById('regSliderDots');
        let current = 0;
        let autoplay;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', `Go to testimonial ${i + 1}`);
            dot.addEventListener('click', () => goTo(i));
            dotsContainer?.appendChild(dot);
        });

        const dots = dotsContainer?.querySelectorAll('button') || [];

        const goTo = index => {
            slides[current]?.classList.remove('is-active');
            dots[current]?.classList.remove('is-active');
            current = ((index % slides.length) + slides.length) % slides.length;
            slides[current]?.classList.add('is-active');
            dots[current]?.classList.add('is-active');
        };

        document.getElementById('regSliderPrev')?.addEventListener('click', () => goTo(current - 1));
        document.getElementById('regSliderNext')?.addEventListener('click', () => goTo(current + 1));

        goTo(0);

        if (!prefersReducedMotion) {
            autoplay = setInterval(() => goTo(current + 1), 6000);
            track.addEventListener('mouseenter', () => clearInterval(autoplay));
            track.addEventListener('mouseleave', () => {
                autoplay = setInterval(() => goTo(current + 1), 6000);
            });
        }
    }

    /* ---- FAQ accordion ---- */
    function initFaq() {
        document.querySelectorAll('.faq-item__question').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const isOpen = item.classList.contains('is-open');

                document.querySelectorAll('.faq-item.is-open').forEach(el => {
                    el.classList.remove('is-open');
                    el.querySelector('.faq-item__question')?.setAttribute('aria-expanded', 'false');
                });

                if (!isOpen) {
                    item.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    /* ---- Scroll reveal ---- */
    function initScrollReveal() {
        if (prefersReducedMotion) {
            document.querySelectorAll('.reveal:not(.reg-hero .reveal)').forEach(el => el.classList.add('is-visible'));
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
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.reveal:not(.reg-hero .reveal)').forEach(el => observer.observe(el));
    }

    /* ---- Smooth scroll ---- */
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
            setTimeout(() => { btn.textContent = 'Subscribe'; }, 2000);
        });
    }

    /* ---- Scroll to top ---- */
    function initScrollTop() {
        const btn = document.getElementById('scrollTop');
        if (!btn) return;

        window.addEventListener('scroll', () => {
            btn.classList.toggle('visible', window.scrollY > 400);
        }, { passive: true });

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
        });
    }

    /* ---- Init ---- */
    document.addEventListener('DOMContentLoaded', () => {
        initPageLoad();
        initNavbar();
        initVideoLogos();
        initParticles('regHeroParticles');
        initParticles('regCtaParticles');
        initHeroParallax();
        initHeroReveal();
        initCountdown();
        loadRegistrationStats().finally(() => initCounters());
        initRegistrationForm();
        initSuccessModal();
        initTestimonialSlider();
        initFaq();
        initScrollReveal();
        initSmoothScroll();
        initNewsletter();
        initScrollTop();
    });
})();

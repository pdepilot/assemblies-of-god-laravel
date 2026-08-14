/**
 * AGC Ikenegbu — load approved testimonies into page carousels
 */
(function () {
    'use strict';

    function escapeHtml(v) {
        if (v == null) return '';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function apiBase() {
        var bodyBase = document.body.getAttribute('data-api-base');
        if (bodyBase) return bodyBase.replace(/\/$/, '');
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src') || '';
            if (src.indexOf('testimony-display.js') !== -1) {
                return src.replace(/js\/testimony-display\.js(\?.*)?$/, 'api$1');
            }
        }
        return 'api';
    }

    function renderStars() {
        return '<i class="fas fa-star text-primary"></i>' +
            '<i class="fas fa-star text-primary"></i>' +
            '<i class="fas fa-star text-primary"></i>' +
            '<i class="fas fa-star text-primary"></i>' +
            '<i class="fas fa-star text-primary"></i>';
    }

    function buildItemFixed(item) {
        var photoHtml = item.photo_url
            ? '<img src="' + escapeHtml(item.photo_url) + '" class="img-fluid" alt="' + escapeHtml(item.name) + '">'
            : '<img src="img/testimonial-1.jpg" class="img-fluid" alt="' + escapeHtml(item.name) + '">';

        return '<div class="testimonial-item">' +
            '<div class="d-flex mb-3">' +
                '<div class="position-relative">' + photoHtml +
                    '<div class="btn-md-square bg-primary rounded-circle position-absolute" style="top: 25px; left: -25px;">' +
                        '<i class="fa fa-quote-left text-dark"></i>' +
                    '</div>' +
                '</div>' +
                '<div class="ps-3 my-auto">' +
                    '<h5 class="mb-0">' + escapeHtml(item.name) + '</h5>' +
                    '<p class="m-0">' + escapeHtml(item.role || 'Church Member') + '</p>' +
                '</div>' +
            '</div>' +
            '<div class="testimonial-content">' +
                '<div class="d-flex">' + renderStars() + '</div>' +
                '<p class="fs-5 m-0 pt-3">' + escapeHtml(item.testimony) + '</p>' +
            '</div>' +
        '</div>';
    }

    function initCarousel($carousel) {
        if (!window.jQuery || !window.jQuery.fn.owlCarousel) return;
        var $el = window.jQuery($carousel);
        if ($el.hasClass('owl-loaded')) {
            $el.trigger('destroy.owl.carousel');
            $el.removeClass('owl-loaded owl-hidden');
            $el.find('.owl-stage-outer').children().unwrap();
        }
        $el.owlCarousel({
            autoplay: true,
            smartSpeed: 1500,
            dots: false,
            loop: true,
            margin: 25,
            nav: true,
            navText: [
                '<i class="bi bi-arrow-left"></i>',
                '<i class="bi bi-arrow-right"></i>'
            ],
            responsive: {
                0: { items: 1 },
                768: { items: 1 },
                992: { items: 2 },
                1200: { items: 3 }
            }
        });
    }

    function loadCarousel(carousel) {
        var source = carousel.getAttribute('data-testimony-source') || 'index';
        var url = apiBase() + '/get-testimonies.php?source_page=' + encodeURIComponent(source) + '&limit=12';

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var items = (data && data.testimonies) || [];
                if (!items.length) {
                    carousel.innerHTML = '<div class="testimonial-item testimonial-item--empty">' +
                        '<p class="text-muted mb-0">Approved testimonies from this page will appear here. Be the first to share your story above.</p></div>';
                } else {
                    carousel.innerHTML = items.map(buildItemFixed).join('');
                }
                initCarousel(carousel);
            })
            .catch(function () {
                carousel.innerHTML = '<div class="testimonial-item testimonial-item--empty"><p class="text-muted mb-0">Unable to load testimonies right now.</p></div>';
            });
    }

    function init() {
        document.querySelectorAll('.testimonial-carousel[data-testimony-source]').forEach(loadCarousel);
    }

    window.agInitTestimonialCarousel = function (el) {
        if (el) initCarousel(el);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

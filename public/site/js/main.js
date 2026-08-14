(function ($) {
    "use strict";

    // Spinner (skipped on homepage — uses #agPreloader instead)
    var spinner = function () {
        if ($('#agPreloader').length > 0) {
            return;
        }
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sync hero / page offset with actual fixed header height (topbar + navbar)
    function updateSiteHeaderHeight() {
        var fixedTop = document.querySelector(".fixed-top");
        if (!fixedTop) {
            return;
        }
        document.documentElement.style.setProperty(
            "--site-header-height",
            fixedTop.offsetHeight + "px"
        );
    }

    updateSiteHeaderHeight();
    window.agUpdateSiteHeaderHeight = updateSiteHeaderHeight;
    $(window).on("resize orientationchange", updateSiteHeaderHeight);
    $(window).on("load", updateSiteHeaderHeight);
    setTimeout(updateSiteHeaderHeight, 150);
    setTimeout(updateSiteHeaderHeight, 600);


    // Fixed Navbar — hide topbar on scroll (desktop & mobile)
    function topbarOffset() {
        var $tb = $('.topbar:visible');
        return $tb.length ? $tb.outerHeight() : 0;
    }

    $(window).scroll(function () {
        var offset = topbarOffset();
        if ($(this).scrollTop() > 45) {
            $('.fixed-top').addClass('bg-white shadow').css('top', offset ? -offset : 0);
        } else {
            $('.fixed-top').removeClass('bg-white shadow').css('top', 0);
        }
        updateSiteHeaderHeight();
    });
    
    
   // Back to top button
   $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').fadeIn('slow');
    } else {
        $('.back-to-top').fadeOut('slow');
    }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Homepage hero slider (autoplay only, no dot navigation)
    var $heroSlider = $('.hero-slider');
    if ($heroSlider.length) {
        var $slides = $heroSlider.find('.hero-slide');
        var current = 0;
        var interval = parseInt($heroSlider.attr('data-interval-ms') || '7000', 10);
        if (!Number.isFinite(interval) || interval < 2000) interval = 7000;
        var timer;

        function goToSlide(index) {
            $slides.removeClass('active');
            current = (index + $slides.length) % $slides.length;
            $slides.eq(current).addClass('active');
        }

        function nextSlide() {
            goToSlide(current + 1);
        }

        function startAutoplay() {
            timer = setInterval(nextSlide, interval);
        }

        goToSlide(0);
        if ($slides.length > 1) {
            startAutoplay();
        }
    }


    // About banner — rotating Scripture quotes
    var $scriptureRotator = $('#agScriptureRotator');
    if ($scriptureRotator.length) {
        var $scriptureTrack = $scriptureRotator.find('.ag-scripture-track');
        var $scriptureCards = $scriptureRotator.find('.ag-scripture-card');
        var $scriptureDots = $scriptureRotator.find('.ag-scripture-dots button');
        var $scriptureProgress = $scriptureRotator.find('.ag-scripture-progress-fill');
        var scriptureCurrent = 0;
        var scriptureAnimating = false;
        var scriptureDuration = 1350;
        var scriptureInterval = 7000;
        var scriptureTimer;

        function syncScriptureHeight() {
            var $active = $scriptureCards.filter('.active');
            if ($active.length) {
                $scriptureTrack.css('min-height', $active.outerHeight());
            }
        }

        function restartScriptureProgress() {
            var el = $scriptureProgress.get(0);
            if (!el) {
                return;
            }
            el.style.animation = 'none';
            el.offsetHeight;
            el.style.animation = '';
        }

        function finishScripture(nextIndex) {
            $scriptureCards.removeClass('active is-leaving is-entering');
            $scriptureCards.eq(nextIndex).addClass('active');
            if ($scriptureDots.length) {
                $scriptureDots.removeClass('active');
                $scriptureDots.eq(nextIndex).addClass('active');
            }
            scriptureCurrent = nextIndex;
            scriptureAnimating = false;
            syncScriptureHeight();
            restartScriptureProgress();
        }

        function goToScripture(index) {
            var nextIndex = (index + $scriptureCards.length) % $scriptureCards.length;
            if (scriptureAnimating || nextIndex === scriptureCurrent) {
                return;
            }

            scriptureAnimating = true;
            var $out = $scriptureCards.eq(scriptureCurrent);
            var $in = $scriptureCards.eq(nextIndex);

            $scriptureTrack.css('min-height', $out.outerHeight());
            $out.removeClass('active').addClass('is-leaving');
            $in.addClass('is-entering');

            setTimeout(function () {
                finishScripture(nextIndex);
            }, scriptureDuration);
        }

        function startScriptureAutoplay() {
            clearInterval(scriptureTimer);
            scriptureTimer = setInterval(function () {
                if (!$scriptureRotator.hasClass('is-paused')) {
                    goToScripture(scriptureCurrent + 1);
                }
            }, scriptureInterval);
        }

        function resetScriptureAutoplay() {
            startScriptureAutoplay();
        }

        $scriptureCards.filter('.active').length || $scriptureCards.first().addClass('active');
        if ($scriptureDots.length) {
            $scriptureDots.eq(scriptureCurrent).addClass('active');
        }
        syncScriptureHeight();
        restartScriptureProgress();
        startScriptureAutoplay();
        $(window).on('resize', syncScriptureHeight);

        if ($scriptureDots.length) {
            $scriptureDots.on('click', function () {
                goToScripture($(this).data('verse'));
                resetScriptureAutoplay();
            });
        }

        $scriptureRotator.on('mouseenter focusin', function () {
            $scriptureRotator.addClass('is-paused');
        });

        $scriptureRotator.on('mouseleave focusout', function () {
            $scriptureRotator.removeClass('is-paused');
            restartScriptureProgress();
        });
    }


    // Homepage events carousel (one card, slide + zoom in place)
    var $eventsCarousel = $('#agEventsCarousel');
    if ($eventsCarousel.length) {
        var $eventTrack = $eventsCarousel.find('.ag-events-track');
        var $eventSlides = $eventsCarousel.find('.ag-events-slide');
        var $eventDots = $eventsCarousel.find('.ag-events-dots button');
        var eventCurrent = 0;
        var eventAnimating = false;
        var eventDuration = 550;

        function syncTrackHeight() {
            var $active = $eventSlides.filter('.active');
            if ($active.length) {
                $eventTrack.css('min-height', $active.outerHeight());
            }
        }

        function finishEventTransition(nextIndex) {
            $eventSlides.removeClass('active is-leaving-next is-leaving-prev is-entering-next is-entering-prev');
            $eventSlides.eq(nextIndex).addClass('active');
            $eventDots.removeClass('active');
            $eventDots.eq(nextIndex).addClass('active');
            eventCurrent = nextIndex;
            eventAnimating = false;
            syncTrackHeight();
        }

        function goToEvent(index, direction) {
            var nextIndex = (index + $eventSlides.length) % $eventSlides.length;
            if (eventAnimating || nextIndex === eventCurrent) {
                return;
            }

            var dir = direction;
            if (!dir) {
                dir = nextIndex > eventCurrent ? 'next' : 'prev';
                if (eventCurrent === $eventSlides.length - 1 && nextIndex === 0) {
                    dir = 'next';
                }
                if (eventCurrent === 0 && nextIndex === $eventSlides.length - 1) {
                    dir = 'prev';
                }
            }

            eventAnimating = true;
            var $out = $eventSlides.eq(eventCurrent);
            var $in = $eventSlides.eq(nextIndex);

            $eventTrack.css('min-height', $out.outerHeight());

            $out.removeClass('active').addClass(dir === 'next' ? 'is-leaving-next' : 'is-leaving-prev');
            $in.addClass(dir === 'next' ? 'is-entering-next' : 'is-entering-prev');

            setTimeout(function () {
                finishEventTransition(nextIndex);
            }, eventDuration);
        }

        $eventSlides.filter('.active').length || $eventSlides.first().addClass('active');
        $eventDots.eq(eventCurrent).addClass('active');
        syncTrackHeight();
        $(window).on('resize', syncTrackHeight);

        $eventsCarousel.find('.ag-events-nav--next').on('click', function () {
            goToEvent(eventCurrent + 1, 'next');
        });

        $eventsCarousel.find('.ag-events-nav--prev').on('click', function () {
            goToEvent(eventCurrent - 1, 'prev');
        });

        $eventDots.on('click', function () {
            var target = $(this).data('slide');
            if (target === eventCurrent) {
                return;
            }
            var dir = target > eventCurrent ? 'next' : 'prev';
            if (eventCurrent === $eventSlides.length - 1 && target === 0) {
                dir = 'next';
            }
            if (eventCurrent === 0 && target === $eventSlides.length - 1) {
                dir = 'prev';
            }
            goToEvent(target, dir);
        });
    }


    // Testimonial carousel (requires owl.carousel — not loaded on every page)
    function initTestimonialCarousel() {
        if (!$.fn.owlCarousel) return;
        $(".testimonial-carousel").each(function () {
            var $el = $(this);
            if ($el.attr('data-testimony-source') || $el.hasClass('owl-loaded')) return;
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
        });
    }

    window.agInitTestimonialCarousel = initTestimonialCarousel;
    initTestimonialCarousel();

    /* AG 3D logo videos — navbar & footer */
    document.querySelectorAll('.ag-logo-video__el').forEach(function (video) {
        video.muted = true;
        video.playsInline = true;
        var play = function () { video.play().catch(function () {}); };
        if (video.readyState >= 2) {
            play();
        } else {
            video.addEventListener('loadeddata', play, { once: true });
        }
    });

    (function loadSitePortalNav() {
        if (window.AGSitePortalNav || window.__agSitePortalNavLoading) {
            return;
        }
        window.__agSitePortalNavLoading = true;
        var base = 'js/site-portal-nav.js';
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].getAttribute('src') || '';
            if (src.indexOf('main.js') !== -1) {
                base = src.replace(/main\.js(\?.*)?$/, 'site-portal-nav.js$1');
                break;
            }
        }
        var el = document.createElement('script');
        el.src = base;
        el.defer = true;
        (document.body || document.documentElement).appendChild(el);
    })();

})(jQuery);


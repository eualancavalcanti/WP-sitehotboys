/**
 * HotBoys Theme - JavaScript Principal
 */

(function () {
    'use strict';

    // Menu Mobile Toggle
    var toggle = document.querySelector('.menu-toggle');
    var body = document.body;

    if (toggle) {
        toggle.addEventListener('click', function () {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            body.classList.toggle('nav-open');
        });
    }

    // Promo Bar dismiss
    var promoBar = document.getElementById('promoBar');
    var promoClose = document.getElementById('promoClose');
    if (promoBar && promoClose) {
        if (sessionStorage.getItem('hotboys_promo_closed')) {
            promoBar.classList.add('is-hidden');
        }
        promoClose.addEventListener('click', function () {
            promoBar.classList.add('is-hidden');
            sessionStorage.setItem('hotboys_promo_closed', '1');
        });
    }

    // Horizontal scroll rows — drag to scroll
    var scrollRows = document.querySelectorAll('[data-scroll-row]');
    for (var i = 0; i < scrollRows.length; i++) {
        (function (row) {
            var isDown = false;
            var startX, scrollLeft;

            row.addEventListener('mousedown', function (e) {
                isDown = true;
                row.style.cursor = 'grabbing';
                startX = e.pageX - row.offsetLeft;
                scrollLeft = row.scrollLeft;
            });

            row.addEventListener('mouseleave', function () {
                isDown = false;
                row.style.cursor = '';
            });

            row.addEventListener('mouseup', function () {
                isDown = false;
                row.style.cursor = '';
            });

            row.addEventListener('mousemove', function (e) {
                if (!isDown) return;
                e.preventDefault();
                var x = e.pageX - row.offsetLeft;
                var walk = (x - startX) * 1.5;
                row.scrollLeft = scrollLeft - walk;
            });
        })(scrollRows[i]);
    }

    // Newsletter form — basic handler (prevent reload, show feedback)
    var nlForm = document.querySelector('[data-newsletter-form]');
    if (nlForm) {
        nlForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = nlForm.querySelector('.newsletter-form__btn');
            var input = nlForm.querySelector('.newsletter-form__input');
            if (btn && input && input.value) {
                btn.textContent = '✓ Cadastrado!';
                btn.disabled = true;
                input.disabled = true;
                btn.style.opacity = '0.7';
            }
        });
    }

    // Age Gate (aviso de maioridade)
    if (!sessionStorage.getItem('hotboys_age_verified')) {
        var overlay = document.createElement('div');
        overlay.className = 'age-gate';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-label', 'Verificação de idade');
        overlay.innerHTML =
            '<div class="age-gate__content">' +
            '<h2 class="age-gate__title">Verificação de Idade</h2>' +
            '<p class="age-gate__text">Este site contém conteúdo destinado exclusivamente para maiores de 18 anos. Ao continuar, você confirma ter mais de 18 anos.</p>' +
            '<div class="age-gate__buttons">' +
            '<button class="btn btn-primary" id="age-confirm">Tenho 18+ anos</button>' +
            '<a href="https://google.com" class="btn btn-outline">Sair</a>' +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);

        document.getElementById('age-confirm').addEventListener('click', function () {
            sessionStorage.setItem('hotboys_age_verified', '1');
            overlay.remove();
        });
    }

    // ---- Floating Action Button (FAB) — scene & actor pages ----
    var isSingleScene = document.querySelector('.single-scene');
    var isSingleActor = document.querySelector('.single-actor');
    var isArchive = document.querySelector('.archive-header');

    if (isSingleScene || isSingleActor || isArchive) {
        var fab = document.createElement('div');
        fab.className = 'fab-cta';
        fab.id = 'fabCta';
        fab.innerHTML = '<a href="https://hotboys.com.br" target="_blank" rel="noopener" class="fab-cta__link">🔥 Assinar</a>';
        document.body.appendChild(fab);

        var fabThreshold = 300;
        var ticking = false;

        function updateFab() {
            if (window.scrollY > fabThreshold) {
                fab.classList.add('is-visible');
            } else {
                fab.classList.remove('is-visible');
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                requestAnimationFrame(updateFab);
                ticking = true;
            }
        }, { passive: true });
    }

    // ---- Sticky Bottom CTA (scene pages) — show after scrolling past main CTA ----
    var stickyCta = document.getElementById('stickyCta');
    var sceneMidCta = document.getElementById('sceneMidCta');

    if (stickyCta && sceneMidCta) {
        var stickyObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    stickyCta.classList.remove('is-visible');
                    stickyCta.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('sticky-active');
                } else {
                    // Only show if we've scrolled past (below the element)
                    var rect = sceneMidCta.getBoundingClientRect();
                    if (rect.bottom < 0) {
                        stickyCta.classList.add('is-visible');
                        stickyCta.setAttribute('aria-hidden', 'false');
                        document.body.classList.add('sticky-active');
                    }
                }
            });
        }, { threshold: 0 });

        stickyObserver.observe(sceneMidCta);
    }
})();

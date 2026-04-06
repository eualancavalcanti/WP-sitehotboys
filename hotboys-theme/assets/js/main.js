/**
 * HotBoys Theme - JavaScript Principal
 * Minimal: apenas menu mobile e aviso de maioridade
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

    // Age Gate (aviso de maioridade)
    // NAO bloqueia o HTML para crawlers do Google - renderizado via JS
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
})();

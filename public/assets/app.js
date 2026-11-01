'use strict';
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || '¿Continuar?')) event.preventDefault();
        });
    });
    const voteForm = document.getElementById('votacionForm');
    if (voteForm) {
        voteForm.addEventListener('submit', (event) => {
            if (!voteForm.reportValidity()) return;
            if (!window.confirm('¿Confirmas el voto seleccionado? Esta acción no se puede deshacer.')) {
                event.preventDefault();
                return;
            }
            const button = document.getElementById('btnVotar');
            if (button) {
                button.disabled = true;
                button.textContent = 'Registrando voto…';
            }
        });
    }
});

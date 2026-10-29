document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.getAttribute('data-confirm') || 'Confirmar operação?';
            if (!window.confirm(message)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-search-target]').forEach((input) => {
        const target = document.querySelector(input.getAttribute('data-search-target'));
        if (!target) return;
        input.addEventListener('input', () => {
            const term = input.value.toLowerCase().trim();
            target.querySelectorAll('tbody tr').forEach((row) => {
                row.hidden = term !== '' && !row.innerText.toLowerCase().includes(term);
            });
        });
    });

    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Processando...';
            }
        });
    });
});

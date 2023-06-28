document.addEventListener('DOMContentLoaded', function () {
    const iris = document.querySelectorAll('[data-behaviour="run-iris"]');
    iris.forEach((btn) => {
        const namespace = btn.dataset.ref;
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            btn.setAttribute('disabled', true);
            btn.classList.add('stats__button--disabled');
            btn.innerHTML = 'Iris Running...';

            const response = await fetch(`/wp-json/${namespace}/iris`, {
                method: 'POST',
                credentials: 'include',
                headers: { 'X-WP-Nonce': wpApiSettings.nonce },
            });

            if (response.status >= 300) {
                console.log(response);
                btn.setAttribute('disabled', false);
                btn.classList.remove('stats__button--disabled');
                btn.innerHTML = 'Iris Now Running';
            }
        });
    });
});

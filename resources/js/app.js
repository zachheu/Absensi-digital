document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-nav-backdrop]');

    const close = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    };

    toggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
        backdrop?.classList.toggle('hidden');
    });

    backdrop?.addEventListener('click', close);

    const scanPage = document.querySelector('[data-scan-page]');

    if (scanPage) {
        import('./scanner')
            .then(({ initScanner }) => {
                initScanner({
                    readerId: 'qr-reader',
                    messageId: 'scan-message',
                    endpoint: scanPage.dataset.scanEndpoint,
                });
            })
            .catch(() => {});
    }
});

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                <h6 class="mt-2 mb-1" id="confirmModalLabel">Hapus data?</h6>
                <p class="text-muted small mb-0" id="confirmModalMessage"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmModalYes">Ya, hapus</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.confirmDelete = function (message) {
        const modalElement = document.getElementById('confirmModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        document.getElementById('confirmModalMessage').textContent = message;

        return new Promise(function (resolve) {
            let confirmed = false;
            const yesButton = document.getElementById('confirmModalYes');
            yesButton.onclick = function () {
                confirmed = true;
                modal.hide();
            };
            modalElement.addEventListener('hidden.bs.modal', function () {
                resolve(confirmed);
            }, { once: true });
            modal.show();
        });
    };

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        event.preventDefault();
        window.confirmDelete(form.dataset.confirm).then(function (confirmed) {
            if (confirmed) HTMLFormElement.prototype.submit.call(form);
        });
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-toggle-password]');
        if (!button) return;

        const input = document.getElementById(button.dataset.togglePassword);
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        const icon = button.querySelector('i');
        if (icon) icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    function initializePageScripts(documentToLoad) {
        document.querySelectorAll('script[data-page-script]').forEach((script) => script.remove());
        documentToLoad.querySelectorAll('script[data-page-script]').forEach((source) => {
            const script = document.createElement('script');
            script.dataset.pageScript = '';
            script.textContent = source.textContent;
            document.body.appendChild(script);
        });
    }

    let activeNavigation;
    async function navigate(url, { replace = false } = {}) {
        activeNavigation?.abort();
        const controller = new AbortController();
        activeNavigation = controller;
        const main = document.querySelector('main.container');
        if (!main) {
            window.location.assign(url);
            return;
        }

        main.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`Permintaan halaman gagal (${response.status}).`);

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = nextDocument.querySelector('main.container');
            if (!nextMain || response.redirected) {
                window.location.assign(response.url);
                return;
            }

            const oldNavbar = document.querySelector('nav.navbar');
            const nextNavbar = nextDocument.querySelector('nav.navbar');
            if (oldNavbar && nextNavbar) oldNavbar.replaceWith(nextNavbar);
            main.innerHTML = nextMain.innerHTML;
            document.title = nextDocument.title;
            initializePageScripts(nextDocument);
            if (replace) history.replaceState({}, '', response.url);
            else history.pushState({}, '', response.url);
            window.scrollTo({ top: 0, behavior: 'instant' });
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Navigasi AJAX gagal:', error);
                window.location.assign(url);
            }
        } finally {
            if (activeNavigation === controller) {
                activeNavigation = undefined;
                main.removeAttribute('aria-busy');
            }
        }
    }

    document.addEventListener('click', function (event) {
        const target = event.target instanceof Element ? event.target : event.target.parentElement;
        const link = target?.closest('main.container a[href], nav.navbar a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 ||
            event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
            link.target || link.hasAttribute('download')) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin ||
            (url.hash && url.pathname === location.pathname && url.search === location.search)) return;
        event.preventDefault();
        navigate(url.href);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('main.container') ||
            form.method.toUpperCase() !== 'GET' || form.target) return;

        const url = new URL(form.action, window.location.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        new FormData(form).forEach((value, key) => {
            if (typeof value === 'string') url.searchParams.set(key, value);
        });
        navigate(url.href);
    });

    window.addEventListener('popstate', function () {
        navigate(window.location.href, { replace: true });
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert-autohide').forEach(function (alert) {
            setTimeout(function () {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }, 4000);
        });
    });
</script>
@stack('scripts')

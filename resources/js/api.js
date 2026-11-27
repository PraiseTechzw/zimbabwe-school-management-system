/**
 * Minimal API client for the handful of interactions that need JavaScript.
 *
 * The Laravel page owns the session cookie and CSRF token; this client never
 * stores credentials or tokens in browser storage. Server-rendered screens
 * read data through the PHP adapter in app/Frontend instead.
 */

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function createApiClient(fetcher = globalThis.fetch.bind(globalThis)) {
    async function request(method, url, body) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        };

        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }

        let response;
        try {
            response = await fetcher(url, {
                method,
                headers,
                credentials: 'same-origin',
                body: body === undefined ? undefined : JSON.stringify(body),
            });
        } catch {
            throw new Error('The server could not be reached. Check your connection and try again.');
        }

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(payload.message ?? `Request failed (${response.status}).`);
            error.status = response.status;
            error.fields = payload.errors ?? {};
            throw error;
        }

        return payload;
    }

    return {
        get: (url) => request('GET', url),
        post: (url, body) => request('POST', url, body ?? {}),
    };
}

export const api = createApiClient();

function showError(container, message) {
    if (!container) return;
    container.textContent = message;
    container.hidden = false;
}

function mountLoginForm(root, client) {
    const form = root.querySelector('form[data-api-login]');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = form.querySelector('[data-api-error]');
        const submit = form.querySelector('button[type="submit"]');
        if (errorBox) errorBox.hidden = true;
        if (submit) submit.disabled = true;

        const data = new FormData(form);
        try {
            await client.post('/api/auth/login', {
                email: String(data.get('email') ?? ''),
                password: String(data.get('password') ?? ''),
            });
            globalThis.location.assign('/app');
        } catch (error) {
            const fields = error.fields ?? {};
            const firstField = Object.values(fields)[0];
            showError(errorBox, Array.isArray(firstField) ? firstField[0] : error.message);
            if (submit) submit.disabled = false;
        }
    });
}

function mountLogoutButtons(root, client) {
    root.querySelectorAll('[data-api-logout]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                await client.post('/api/auth/logout');
            } catch {
                // Even if logout fails, leave the application surface.
            }
            globalThis.location.assign('/login');
        });
    });
}

if (typeof document !== 'undefined') {
    mountLoginForm(document, api);
    mountLogoutButtons(document, api);
}

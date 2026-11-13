// POST JSON ringan (tanpa navigasi Inertia) dengan token CSRF dari cookie XSRF-TOKEN Laravel.
function xsrf() {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

export async function postJson(url, body, { retries = 2 } = {}) {
    for (let attempt = 0; ; attempt++) {
        try {
            const r = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf() },
                body: JSON.stringify(body),
            });
            if (r.ok) return await r.json();
            if (r.status < 500 || attempt >= retries) throw new Error('HTTP ' + r.status);
        } catch (e) {
            if (attempt >= retries) throw e;
        }
        await new Promise((res) => setTimeout(res, 600 * (attempt + 1)));
    }
}

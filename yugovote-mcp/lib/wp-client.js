/**
 * WordPress REST API Client
 * Handles authentication and HTTP requests to the YugoVote WordPress REST API.
 */

const WP_BASE_URL = process.env.WP_BASE_URL || 'https://yugovote.com';
const WP_USERNAME = process.env.WP_USERNAME || '';
const WP_APP_PASSWORD = process.env.WP_APP_PASSWORD || '';

const API_BASE = `${WP_BASE_URL}/wp-json/yugovote-mcp/v1`;

function getAuthHeader() {
    const credentials = Buffer.from(`${WP_USERNAME}:${WP_APP_PASSWORD}`).toString('base64');
    return `Basic ${credentials}`;
}

/**
 * Make an authenticated request to the WordPress REST API
 */
export async function wpRequest(method, path, body = null, queryParams = {}) {
    const url = new URL(`${API_BASE}${path}`);

    for (const [key, value] of Object.entries(queryParams)) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, String(value));
        }
    }

    const options = {
        method: method.toUpperCase(),
        headers: {
            'Authorization': getAuthHeader(),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    if (body && ['POST', 'PUT', 'PATCH'].includes(options.method)) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url.toString(), options);

    let data;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        data = await response.json();
    } else {
        const text = await response.text();
        throw new Error(`Non-JSON response (${response.status}): ${text.substring(0, 500)}`);
    }

    if (!response.ok) {
        const message = data?.message || data?.data?.message || JSON.stringify(data);
        throw new Error(`WordPress API error (${response.status}): ${message}`);
    }

    return data;
}

/**
 * Shorthand methods
 */
export const wp = {
    get: (path, params) => wpRequest('GET', path, null, params),
    post: (path, body) => wpRequest('POST', path, body),
    put: (path, body) => wpRequest('PUT', path, body),
};

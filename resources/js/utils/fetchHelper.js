/**
 * Perform a fetch request with automatic CSRF token handling
 *
 * @param {string} url - The URL to fetch
 * @param {Object} options - Fetch options (method, body, headers, etc.)
 * @returns {Promise<Response>}
 */
export async function csrfFetch(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    return fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        }
    });
}

/**
 * Submit form data with automatic CSRF handling and JSON parsing
 *
 * @param {string} url - The URL to submit to
 * @param {Object} data - The data to submit
 * @param {Object} options - Additional fetch options
 * @returns {Promise<Object>} - Parsed JSON response
 * @throws {Error} - If the response is not OK or contains error message
 */
export async function submitForm(url, data, options = {}) {
    const response = await csrfFetch(url, {
        method: 'POST',
        body: JSON.stringify(data),
        ...options
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Request failed');
    }

    return response.json();
}

/**
 * Delete a resource with automatic CSRF handling
 *
 * @param {string} url - The URL to delete
 * @param {Object} options - Additional fetch options
 * @returns {Promise<Response>}
 */
export async function deleteResource(url, options = {}) {
    return csrfFetch(url, {
        method: 'DELETE',
        ...options
    });
}

/**
 * Update a resource with automatic CSRF handling
 *
 * @param {string} url - The URL to update
 * @param {Object} data - The data to update with
 * @param {Object} options - Additional fetch options
 * @returns {Promise<Object>} - Parsed JSON response
 */
export async function updateResource(url, data, options = {}) {
    const response = await csrfFetch(url, {
        method: 'PUT',
        body: JSON.stringify(data),
        ...options
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Update failed');
    }

    return response.json();
}

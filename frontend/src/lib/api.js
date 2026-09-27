/**
 * Nuvora Weather API Client
 */

const API_BASE = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000/api';

/**
 * Helper to fetch with timeout and error handling
 */
async function fetchWithTimeout(url, options = {}, timeoutMs = 8000) {
	const controller = new AbortController();
	const id = setTimeout(() => controller.abort(), timeoutMs);

	try {
		const response = await fetch(url, {
			...options,
			signal: controller.signal,
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json',
				...(options.headers || {})
			}
		});
		clearTimeout(id);
		return response;
	} catch (err) {
		clearTimeout(id);
		throw err;
	}
}

export async function fetchWeather(lat, lon, cityName = null, country = null) {
	const params = new URLSearchParams();
	if (lat !== undefined && lat !== null) params.append('lat', lat);
	if (lon !== undefined && lon !== null) params.append('lon', lon);
	if (cityName) params.append('city', cityName);
	if (country) params.append('country', country);

	const url = `${API_BASE}/weather/current?${params.toString()}`;
	const res = await fetchWithTimeout(url);

	if (!res.ok) {
		const errJson = await res.json().catch(() => ({}));
		throw new Error(errJson.message || `API Error (${res.status})`);
	}

	const json = await res.json();
	return json.data;
}

export async function fetchWeatherByLocation(lat, lon) {
	const url = `${API_BASE}/weather/location?latitude=${lat}&longitude=${lon}`;
	const res = await fetchWithTimeout(url);

	if (!res.ok) {
		const errJson = await res.json().catch(() => ({}));
		throw new Error(errJson.message || `Location Error (${res.status})`);
	}

	const json = await res.json();
	return json.data;
}

export async function searchCities(query) {
	if (!query || query.trim().length < 2) return [];

	const url = `${API_BASE}/weather/search?q=${encodeURIComponent(query.trim())}`;
	const res = await fetchWithTimeout(url, {}, 6000);

	if (!res.ok) return [];

	const json = await res.json();
	return json.results || [];
}

export async function fetchIndonesiaRegions() {
	const url = `${API_BASE}/weather/indonesia-regions`;
	const res = await fetchWithTimeout(url, {}, 5000);

	if (!res.ok) return [];

	const json = await res.json();
	return json.regions || [];
}

export async function fetchFeaturedCities() {
	const url = `${API_BASE}/cities/featured`;
	const res = await fetchWithTimeout(url, {}, 6000);

	if (!res.ok) return [];

	const json = await res.json();
	return json.data || [];
}

/* =========================================================================
   Disaster Monitoring API Methods (BMKG & Flood Sensors)
   ========================================================================= */

export async function fetchLatestEarthquake(lat = null, lon = null) {
	const params = new URLSearchParams();
	if (lat !== null && lat !== undefined) params.append('lat', lat);
	if (lon !== null && lon !== undefined) params.append('lon', lon);

	const url = `${API_BASE}/disaster/earthquakes/latest?${params.toString()}`;
	const res = await fetchWithTimeout(url, {}, 6000);

	if (!res.ok) throw new Error('Data gempa BMKG sementara tidak tersedia.');
	const json = await res.json();
	return json.data;
}

export async function fetchEarthquakes(lat = null, lon = null, filter = 'all') {
	const params = new URLSearchParams();
	if (lat !== null && lat !== undefined) params.append('lat', lat);
	if (lon !== null && lon !== undefined) params.append('lon', lon);
	if (filter && filter !== 'all') params.append('filter', filter);

	const url = `${API_BASE}/disaster/earthquakes?${params.toString()}`;
	const res = await fetchWithTimeout(url, {}, 8000);

	if (!res.ok) return [];
	const json = await res.json();
	return json.data || [];
}

export async function fetchFloodStations(lat = null, lon = null, province = '') {
	const params = new URLSearchParams();
	if (lat !== null && lat !== undefined) params.append('lat', lat);
	if (lon !== null && lon !== undefined) params.append('lon', lon);
	if (province) params.append('province', province);

	const url = `${API_BASE}/disaster/floods?${params.toString()}`;
	const res = await fetchWithTimeout(url, {}, 6000);

	if (!res.ok) return [];
	const json = await res.json();
	return json.data || [];
}

export async function fetchDisasterStatus() {
	const url = `${API_BASE}/disaster/status`;
	const res = await fetchWithTimeout(url, {}, 6000);

	if (!res.ok) throw new Error('Failed to load disaster telemetry');
	const json = await res.json();
	return json;
}

/* =========================================================================
   Admin API Methods
   ========================================================================= */

function getAdminHeaders() {
	const token = typeof localStorage !== 'undefined' ? localStorage.getItem('nuvora_admin_token') : null;
	return token ? { 'Authorization': `Bearer ${token}` } : {};
}

export async function adminLogin(email, password) {
	const url = `${API_BASE}/admin/login`;
	const res = await fetchWithTimeout(url, {
		method: 'POST',
		body: JSON.stringify({ email, password })
	});

	const json = await res.json();
	if (!res.ok) {
		throw new Error(json.message || json.errors?.email?.[0] || 'Authentication failed');
	}

	if (typeof localStorage !== 'undefined' && json.token) {
		localStorage.setItem('nuvora_admin_token', json.token);
		localStorage.setItem('nuvora_admin_user', JSON.stringify(json.admin));
	}

	return json;
}

export async function adminLogout() {
	try {
		const url = `${API_BASE}/admin/logout`;
		await fetchWithTimeout(url, {
			method: 'POST',
			headers: getAdminHeaders()
		});
	} catch (e) {
		console.warn('Logout API error:', e);
	} finally {
		if (typeof localStorage !== 'undefined') {
			localStorage.removeItem('nuvora_admin_token');
			localStorage.removeItem('nuvora_admin_user');
		}
	}
}

export async function fetchAdminStats() {
	const url = `${API_BASE}/admin/stats`;
	const res = await fetchWithTimeout(url, {
		headers: getAdminHeaders()
	});

	if (!res.ok) throw new Error('Unauthorized or failed to load stats');
	const json = await res.json();
	return json;
}

export async function fetchAdminLogs(page = 1) {
	const url = `${API_BASE}/admin/logs?page=${page}`;
	const res = await fetchWithTimeout(url, {
		headers: getAdminHeaders()
	});

	if (!res.ok) throw new Error('Failed to load logs');
	const json = await res.json();
	return json.data;
}

export async function fetchAdminCities() {
	const url = `${API_BASE}/admin/featured-cities`;
	const res = await fetchWithTimeout(url, {
		headers: getAdminHeaders()
	});

	if (!res.ok) throw new Error('Failed to load cities');
	const json = await res.json();
	return json.cities || [];
}

export async function saveAdminCity(cityData) {
	const isEdit = !!cityData.id;
	const url = isEdit ? `${API_BASE}/admin/featured-cities/${cityData.id}` : `${API_BASE}/admin/featured-cities`;
	const res = await fetchWithTimeout(url, {
		method: isEdit ? 'PUT' : 'POST',
		headers: getAdminHeaders(),
		body: JSON.stringify(cityData)
	});

	const json = await res.json();
	if (!res.ok) throw new Error(json.message || 'Failed to save city');
	return json;
}

export async function deleteAdminCity(id) {
	const url = `${API_BASE}/admin/featured-cities/${id}`;
	const res = await fetchWithTimeout(url, {
		method: 'DELETE',
		headers: getAdminHeaders()
	});

	const json = await res.json();
	if (!res.ok) throw new Error(json.message || 'Failed to delete city');
	return json;
}

export async function fetchAdminSettings() {
	const url = `${API_BASE}/admin/settings`;
	const res = await fetchWithTimeout(url, {
		headers: getAdminHeaders()
	});

	if (!res.ok) throw new Error('Failed to load settings');
	const json = await res.json();
	return json.settings || {};
}

export async function saveAdminSettings(settings) {
	const url = `${API_BASE}/admin/settings`;
	const res = await fetchWithTimeout(url, {
		method: 'POST',
		headers: getAdminHeaders(),
		body: JSON.stringify(settings)
	});

	const json = await res.json();
	if (!res.ok) throw new Error(json.message || 'Failed to save settings');
	return json;
}

/**
 * Nuvora Weather API Client
 */

import {
	fetchWeatherDirect,
	fetchWeatherByLocationDirect,
	searchCitiesDirect,
	fetchIndonesiaRegionsDirect,
	fetchFeaturedCitiesDirect,
	fetchLatestEarthquakeDirect,
	fetchEarthquakesDirect,
	fetchFloodStationsDirect,
	fetchDisasterStatusDirect
} from './openMeteoDirect.js';

const API_BASE = import.meta.env.PUBLIC_API_URL || 'http://localhost:8000/api';

/**
 * Helper to fetch with timeout and error handling
 */
async function fetchWithTimeout(url, options = {}, timeoutMs = 4000) {
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
	try {
		const params = new URLSearchParams();
		if (lat !== undefined && lat !== null) params.append('lat', lat);
		if (lon !== undefined && lon !== null) params.append('lon', lon);
		if (cityName) params.append('city', cityName);
		if (country) params.append('country', country);

		const url = `${API_BASE}/weather/current?${params.toString()}`;
		const res = await fetchWithTimeout(url);

		if (res.ok) {
			const json = await res.json();
			if (json && json.data) return json.data;
		}
	} catch (err) {
		console.warn('Backend API unavailable, falling back to direct Open-Meteo API:', err.message);
	}

	// Fallback to direct client-side Open-Meteo API
	return fetchWeatherDirect(lat, lon, cityName, country);
}

export async function fetchWeatherByLocation(lat, lon) {
	try {
		const url = `${API_BASE}/weather/location?latitude=${lat}&longitude=${lon}`;
		const res = await fetchWithTimeout(url);

		if (res.ok) {
			const json = await res.json();
			if (json && json.data) return json.data;
		}
	} catch (err) {
		console.warn('Backend location API unavailable, falling back to direct location provider:', err.message);
	}

	return fetchWeatherByLocationDirect(lat, lon);
}

export async function searchCities(query) {
	if (!query || query.trim().length < 2) return [];

	try {
		const url = `${API_BASE}/weather/search?q=${encodeURIComponent(query.trim())}`;
		const res = await fetchWithTimeout(url, {}, 3500);

		if (res.ok) {
			const json = await res.json();
			if (json.results && json.results.length > 0) return json.results;
		}
	} catch (err) {
		console.warn('Backend search API unavailable, using direct geocoding fallback');
	}

	return searchCitiesDirect(query);
}

export async function fetchIndonesiaRegions() {
	try {
		const url = `${API_BASE}/weather/indonesia-regions`;
		const res = await fetchWithTimeout(url, {}, 3000);

		if (res.ok) {
			const json = await res.json();
			if (json.regions && json.regions.length > 0) return json.regions;
		}
	} catch (err) {
		// Silent fallback
	}

	return fetchIndonesiaRegionsDirect();
}

export async function fetchFeaturedCities() {
	try {
		const url = `${API_BASE}/cities/featured`;
		const res = await fetchWithTimeout(url, {}, 3500);

		if (res.ok) {
			const json = await res.json();
			if (json.data && json.data.length > 0) return json.data;
		}
	} catch (err) {
		// Silent fallback
	}

	return fetchFeaturedCitiesDirect();
}

/* =========================================================================
   Disaster Monitoring API Methods (BMKG & Flood Sensors)
   ========================================================================= */

export async function fetchLatestEarthquake(lat = null, lon = null) {
	try {
		const params = new URLSearchParams();
		if (lat !== null && lat !== undefined) params.append('lat', lat);
		if (lon !== null && lon !== undefined) params.append('lon', lon);

		const url = `${API_BASE}/disaster/earthquakes/latest?${params.toString()}`;
		const res = await fetchWithTimeout(url, {}, 3500);

		if (res.ok) {
			const json = await res.json();
			if (json.data) return json.data;
		}
	} catch (err) {
		console.warn('Backend BMKG API unavailable, using direct BMKG fallback');
	}

	return fetchLatestEarthquakeDirect(lat, lon);
}

export async function fetchEarthquakes(lat = null, lon = null, filter = 'all') {
	try {
		const params = new URLSearchParams();
		if (lat !== null && lat !== undefined) params.append('lat', lat);
		if (lon !== null && lon !== undefined) params.append('lon', lon);
		if (filter && filter !== 'all') params.append('filter', filter);

		const url = `${API_BASE}/disaster/earthquakes?${params.toString()}`;
		const res = await fetchWithTimeout(url, {}, 4000);

		if (res.ok) {
			const json = await res.json();
			if (json.data) return json.data;
		}
	} catch (err) {
		// Silent fallback
	}

	return fetchEarthquakesDirect(lat, lon, filter);
}

export async function fetchFloodStations(lat = null, lon = null, province = '') {
	try {
		const params = new URLSearchParams();
		if (lat !== null && lat !== undefined) params.append('lat', lat);
		if (lon !== null && lon !== undefined) params.append('lon', lon);
		if (province) params.append('province', province);

		const url = `${API_BASE}/disaster/floods?${params.toString()}`;
		const res = await fetchWithTimeout(url, {}, 3500);

		if (res.ok) {
			const json = await res.json();
			if (json.data) return json.data;
		}
	} catch (err) {
		// Silent fallback
	}

	return fetchFloodStationsDirect(lat, lon, province);
}

export async function fetchDisasterStatus() {
	try {
		const url = `${API_BASE}/disaster/status`;
		const res = await fetchWithTimeout(url, {}, 3000);

		if (res.ok) {
			const json = await res.json();
			return json;
		}
	} catch (err) {
		// Silent fallback
	}

	return fetchDisasterStatusDirect();
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

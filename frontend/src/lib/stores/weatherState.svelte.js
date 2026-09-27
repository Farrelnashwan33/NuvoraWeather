/**
 * Global reactive Weather State with Svelte 5 Runes
 */
import { fetchWeather, fetchWeatherByLocation } from '$lib/api.js';

class WeatherStore {
	data = $state(null);
	loading = $state(true);
	error = $state(null);
	unit = $state('C'); // 'C' | 'F'
	activeTab = $state('overview'); // 'overview' | 'forecast' | 'map' | 'cities'
	theme = $state('dark'); // 'dark' | 'light'
	searchOpen = $state(false);

	lastCity = $state({
		name: 'Bandung',
		country: 'Indonesia',
		lat: -6.9175,
		lon: 107.6191
	});

	init() {
		if (typeof localStorage !== 'undefined') {
			const savedUnit = localStorage.getItem('nuvora-unit');
			if (savedUnit) this.unit = savedUnit;

			const savedTheme = localStorage.getItem('nuvora-theme');
			if (savedTheme) {
				this.theme = savedTheme;
				this.applyTheme(savedTheme);
			}

			const savedCity = localStorage.getItem('nuvora-last-city');
			if (savedCity) {
				try {
					this.lastCity = JSON.parse(savedCity);
				} catch (e) {}
			}
		}
	}

	toggleUnit() {
		this.unit = this.unit === 'C' ? 'F' : 'C';
		if (typeof localStorage !== 'undefined') {
			localStorage.setItem('nuvora-unit', this.unit);
		}
	}

	toggleTheme() {
		this.theme = this.theme === 'dark' ? 'light' : 'dark';
		this.applyTheme(this.theme);
	}

	applyTheme(theme) {
		if (typeof document !== 'undefined') {
			if (theme === 'dark') {
				document.documentElement.classList.add('dark');
			} else {
				document.documentElement.classList.remove('dark');
			}
			localStorage.setItem('nuvora-theme', theme);
		}
	}

	formatTemp(celsius) {
		if (celsius === null || celsius === undefined) return '--';
		if (this.unit === 'F') {
			const fahrenheit = Math.round((celsius * 9) / 5 + 32);
			return `${fahrenheit}°F`;
		}
		return `${Math.round(celsius)}°C`;
	}

	formatSpeed(kmh) {
		if (kmh === null || kmh === undefined) return '--';
		if (this.unit === 'F') {
			const mph = Math.round(kmh * 0.621371);
			return `${mph} mph`;
		}
		return `${kmh} km/h`;
	}

	async loadWeather(lat, lon, cityName, country) {
		this.loading = true;
		this.error = null;

		try {
			const payload = await fetchWeather(lat, lon, cityName, country);
			this.data = payload;
			this.lastCity = {
				name: payload.location.city,
				country: payload.location.country,
				lat: payload.location.latitude,
				lon: payload.location.longitude
			};

			if (typeof localStorage !== 'undefined') {
				localStorage.setItem('nuvora-last-city', JSON.stringify(this.lastCity));
			}
		} catch (err) {
			console.error('Failed to load weather:', err);
			this.error = err.message || 'Unable to connect to weather satellites.';
		} finally {
			this.loading = false;
		}
	}

	async detectLocation() {
		if (typeof navigator === 'undefined' || !navigator.geolocation) {
			return this.loadWeather(this.lastCity.lat, this.lastCity.lon, this.lastCity.name, this.lastCity.country);
		}

		this.loading = true;
		this.error = null;

		return new Promise((resolve) => {
			navigator.geolocation.getCurrentPosition(
				async (pos) => {
					try {
						const lat = pos.coords.latitude;
						const lon = pos.coords.longitude;
						const payload = await fetchWeatherByLocation(lat, lon);
						this.data = payload;
						this.lastCity = {
							name: payload.location.city,
							country: payload.location.country,
							lat: payload.location.latitude,
							lon: payload.location.longitude
						};
						if (typeof localStorage !== 'undefined') {
							localStorage.setItem('nuvora-last-city', JSON.stringify(this.lastCity));
						}
					} catch (e) {
						this.error = 'Location detected, but weather retrieval failed.';
					} finally {
						this.loading = false;
						resolve();
					}
				},
				(geoErr) => {
					console.warn('Geolocation denied or timed out:', geoErr);
					// Fallback to last saved city or default Bandung
					this.loadWeather(this.lastCity.lat, this.lastCity.lon, this.lastCity.name, this.lastCity.country);
					resolve();
				},
				{ timeout: 8000, enableHighAccuracy: false }
			);
		});
	}
}

export const weatherStore = new WeatherStore();

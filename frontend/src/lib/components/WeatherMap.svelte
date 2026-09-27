<script>
	import { onMount, onDestroy } from 'svelte';
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Map as MapIcon, Maximize2, Minimize2, ExternalLink, Globe, Compass } from 'lucide-svelte';

	let mapElement = $state(null);
	let mapInstance = null;
	let markerInstance = null;
	let circleInstance = null;
	let isFullscreen = $state(false);
	
	// Map mode: 'radar' (interactive Leaflet with Google Maps tiles) | 'gmaps' (direct Google Maps iframe embed)
	let viewMode = $state('radar'); 
	let activeLayer = $state('google-hybrid'); // 'google-hybrid' | 'google-roadmap' | 'google-terrain' | 'dark'

	let location = $derived(weatherStore.data?.location);
	let current = $derived(weatherStore.data?.current);

	// High-definition Google Maps tile layers and Esri Dark canvas (no watermarks)
	let tileLayers = {
		'google-hybrid': 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
		'google-roadmap': 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
		'google-terrain': 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
		'dark': 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}'
	};

	let currentTile = null;

	onMount(async () => {
		if (typeof window !== 'undefined') {
			const L = await import('leaflet');

			if (mapElement && !mapInstance) {
				const lat = location?.latitude ?? -6.9175;
				const lon = location?.longitude ?? 107.6191;

				mapInstance = L.map(mapElement, {
					center: [lat, lon],
					zoom: 11,
					zoomControl: false,
					attributionControl: false
				});

				currentTile = L.tileLayer(tileLayers[activeLayer], {
					maxZoom: 20,
					subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
				}).addTo(mapInstance);

				updateMarker(L, lat, lon);
			}
		}
	});

	$effect(() => {
		if (mapInstance && location && viewMode === 'radar') {
			import('leaflet').then((L) => {
				mapInstance.setView([location.latitude, location.longitude], 11, {
					animate: true,
					duration: 1
				});
				updateMarker(L, location.latitude, location.longitude);
			});
		}
	});

	function updateMarker(L, lat, lon) {
		if (!mapInstance) return;

		if (markerInstance) mapInstance.removeLayer(markerInstance);
		if (circleInstance) mapInstance.removeLayer(circleInstance);

		const tempText = current ? weatherStore.formatTemp(current.temperature) : '';
		const condText = current?.condition || '';

		const customIcon = L.divIcon({
			className: 'custom-weather-pin',
			html: `
				<div class="relative flex items-center justify-center">
					<div class="absolute -inset-3 rounded-full bg-cyan-400/40 animate-ping"></div>
					<div class="relative px-3.5 py-1.5 rounded-2xl bg-slate-950/90 border border-cyan-400 text-white shadow-2xl shadow-cyan-500/50 flex items-center gap-2 whitespace-nowrap text-xs font-bold font-mono">
						<span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
						<span class="text-white">${tempText}</span>
						<span class="text-[11px] text-cyan-300 font-normal font-sans hidden sm:inline">• ${location?.city || ''}</span>
					</div>
				</div>
			`,
			iconSize: [100, 40],
			iconAnchor: [50, 20]
		});

		markerInstance = L.marker([lat, lon], { icon: customIcon }).addTo(mapInstance);

		circleInstance = L.circle([lat, lon], {
			radius: 8000,
			color: '#38bdf8',
			weight: 1.5,
			fillColor: '#0ea5e9',
			fillOpacity: 0.2
		}).addTo(mapInstance);
	}

	function changeLayer(type) {
		activeLayer = type;
		viewMode = 'radar';
		if (mapInstance && currentTile) {
			import('leaflet').then((L) => {
				mapInstance.removeLayer(currentTile);
				currentTile = L.tileLayer(tileLayers[type], {
					maxZoom: 20,
					subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
				}).addTo(mapInstance);
			});
		}
	}

	function toggleFullscreen() {
		isFullscreen = !isFullscreen;
		setTimeout(() => {
			if (mapInstance) mapInstance.invalidateSize();
		}, 250);
	}

	function openGoogleMapsDirect() {
		const lat = location?.latitude ?? -6.9175;
		const lon = location?.longitude ?? 107.6191;
		window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lon}`, '_blank');
	}

	onDestroy(() => {
		if (mapInstance) {
			mapInstance.remove();
			mapInstance = null;
		}
	});
</script>

<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4 transition-all duration-300 {isFullscreen ? 'fixed inset-4 z-50 p-6 flex flex-col' : ''}">
	<!-- Header -->
	<div class="flex flex-wrap items-center justify-between gap-3">
		<div class="flex items-center gap-2">
			<MapIcon class="w-4 h-4 text-cyan-400" />
			<h2 class="font-heading font-bold text-base sm:text-lg text-white">
				{i18n.t('interactive_weather_radar', 'Radar Cuaca & Google Maps')}
			</h2>
			{#if location}
				<span class="text-xs text-slate-400 font-mono hidden sm:inline">
					({location.latitude.toFixed(2)}°, {location.longitude.toFixed(2)}°)
				</span>
			{/if}
		</div>

		<!-- Map Controls & Layer Selector -->
		<div class="flex flex-wrap items-center gap-2">
			<!-- Layer Switcher (Google Hybrid, Google Road, Google Terrain, Dark) -->
			<div class="flex items-center bg-slate-900/80 rounded-xl p-0.5 border border-white/10 text-xs">
				<button
					onclick={() => changeLayer('google-hybrid')}
					class="px-2.5 py-1 rounded-lg transition {viewMode === 'radar' && activeLayer === 'google-hybrid' ? 'bg-cyan-500 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('radar_layer_satellite', 'Google Satelit')}
				</button>
				<button
					onclick={() => changeLayer('google-roadmap')}
					class="px-2.5 py-1 rounded-lg transition {viewMode === 'radar' && activeLayer === 'google-roadmap' ? 'bg-cyan-500 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('radar_layer_roadmap', 'Google Jalan')}
				</button>
				<button
					onclick={() => changeLayer('google-terrain')}
					class="px-2.5 py-1 rounded-lg transition {viewMode === 'radar' && activeLayer === 'google-terrain' ? 'bg-cyan-500 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('radar_layer_terrain', 'Google Terrain')}
				</button>
				<button
					onclick={() => changeLayer('dark')}
					class="px-2.5 py-1 rounded-lg transition {viewMode === 'radar' && activeLayer === 'dark' ? 'bg-cyan-500 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('radar_layer_dark', 'Dark')}
				</button>
				<button
					onclick={() => viewMode = 'gmaps'}
					class="px-2.5 py-1 rounded-lg transition {viewMode === 'gmaps' ? 'bg-cyan-500 text-white font-medium shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					Google Embed
				</button>
			</div>

			<!-- Open in Google Maps External -->
			<button
				onclick={openGoogleMapsDirect}
				class="p-2 rounded-xl bg-slate-900/80 hover:bg-cyan-500/20 text-cyan-300 hover:text-white border border-cyan-500/20 transition flex items-center gap-1.5 text-xs"
				title="Open in Google Maps app"
			>
				<ExternalLink class="w-3.5 h-3.5" />
				<span class="hidden md:inline">Google Maps</span>
			</button>

			<!-- Fullscreen Toggle -->
			<button
				onclick={toggleFullscreen}
				class="p-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-white/10 transition"
				title="Toggle Fullscreen"
			>
				{#if isFullscreen}
					<Minimize2 class="w-4 h-4" />
				{:else}
					<Maximize2 class="w-4 h-4" />
				{/if}
			</button>
		</div>
	</div>

	<!-- Map Container -->
	<div class="relative w-full rounded-2xl overflow-hidden border border-white/10 bg-slate-950 {isFullscreen ? 'flex-1 min-h-[450px]' : 'h-64 sm:h-80'}">
		{#if viewMode === 'radar'}
			<!-- High-Performance Interactive Google Map Radar -->
			<div bind:this={mapElement} class="w-full h-full z-10"></div>
		{:else}
			<!-- Official Google Maps Embed -->
			{#if location}
				<iframe
					title="Google Maps Live View"
					width="100%"
					height="100%"
					style="border:0;"
					loading="lazy"
					allowfullscreen
					referrerpolicy="no-referrer-when-downgrade"
					src="https://maps.google.com/maps?q={location.latitude},{location.longitude}&hl=en&z=12&output=embed"
					class="w-full h-full"
				></iframe>
			{/if}
		{/if}

		<!-- Live Atmosphere Badge Overlay -->
		<div class="absolute bottom-3 left-3 z-20 pointer-events-none flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/85 backdrop-blur-md border border-white/10 text-xs text-white shadow-xl">
			<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
			<span>{viewMode === 'gmaps' ? 'Google Maps Live Embed' : 'Google Satellite Radar Online'}</span>
		</div>
	</div>
</div>

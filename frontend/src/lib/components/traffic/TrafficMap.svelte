<script>
	import { onMount, onDestroy } from 'svelte';
	import { browser } from '$app/environment';
	import { Layers, Globe, Map as MapIcon, ShieldCheck } from 'lucide-svelte';

	let {
		roads = [],
		selectedRoad = null,
		center = [-6.2088, 106.8456],
		zoom = 12,
		onBoundsChange = null,
		onRoadSelect = null
	} = $props();

	let mapElement = $state(null);
	let mapInstance = null;
	let currentTileLayer = null;
	let polylineLayerGroup = null;
	let isLeafletReady = $state(false);
	let moveDebounceTimer = null;

	// Google Maps & Dark Canvas Tile Layers (Clean, high-speed, no watermarks)
	let activeLayer = $state('google-traffic'); // 'google-traffic' | 'google-hybrid' | 'google-roadmap' | 'dark'

	const TILE_LAYERS = {
		'google-traffic': 'https://mt1.google.com/vt/lyrs=m,traffic&x={x}&y={y}&z={z}',
		'google-hybrid': 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
		'google-roadmap': 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
		'dark': 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}'
	};

	const STATUS_COLORS = {
		lancar: '#10b981', // green
		ramai: '#f59e0b',  // amber
		padat: '#f97316',  // orange
		macet: '#ef4444',  // red
		unknown: '#64748b' // slate
	};

	onMount(async () => {
		if (!browser || !mapElement) return;

		try {
			// Dynamically import leaflet on client-side only
			const L = await import('leaflet');
			await import('leaflet/dist/leaflet.css');

			if (!mapElement) return;

			// Initialize Leaflet Map
			mapInstance = L.map(mapElement, {
				center: center,
				zoom: zoom,
				zoomControl: false,
				attributionControl: false
			});

			// Google Maps HD Tile Layer
			currentTileLayer = L.tileLayer(TILE_LAYERS[activeLayer], {
				maxZoom: 20,
				subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
				attribution: '&copy; Google Maps & Nuvora Traffic Telemetry'
			}).addTo(mapInstance);

			// Add Zoom control at top right
			L.control.zoom({ position: 'topright' }).addTo(mapInstance);

			// Layer Group for Polylines
			polylineLayerGroup = L.layerGroup().addTo(mapInstance);

			// Viewport change listener with 350ms debounce
			mapInstance.on('moveend zoomend', () => {
				if (moveDebounceTimer) clearTimeout(moveDebounceTimer);
				moveDebounceTimer = setTimeout(() => {
					if (!mapInstance || !onBoundsChange) return;
					const bounds = mapInstance.getBounds();
					onBoundsChange({
						north: bounds.getNorth(),
						south: bounds.getSouth(),
						east: bounds.getEast(),
						west: bounds.getWest(),
						zoom: mapInstance.getZoom(),
						center: [mapInstance.getCenter().lat, mapInstance.getCenter().lng]
					});
				}, 350);
			});

			isLeafletReady = true;

			// Initial trigger
			if (onBoundsChange) {
				const bounds = mapInstance.getBounds();
				onBoundsChange({
					north: bounds.getNorth(),
					south: bounds.getSouth(),
					east: bounds.getEast(),
					west: bounds.getWest(),
					zoom: mapInstance.getZoom(),
					center: [mapInstance.getCenter().lat, mapInstance.getCenter().lng]
				});
			}

			renderRoads();
		} catch (err) {
			console.error('Error initializing Traffic Map:', err);
		}
	});

	onDestroy(() => {
		if (moveDebounceTimer) clearTimeout(moveDebounceTimer);
		if (mapInstance) {
			mapInstance.off();
			mapInstance.remove();
			mapInstance = null;
		}
	});

	function switchTileLayer(layerKey) {
		activeLayer = layerKey;
		if (!mapInstance || !browser) return;

		import('leaflet').then((L) => {
			if (currentTileLayer) {
				mapInstance.removeLayer(currentTileLayer);
			}
			currentTileLayer = L.tileLayer(TILE_LAYERS[layerKey], {
				maxZoom: 20,
				subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
				attribution: '&copy; Google Maps & Nuvora Traffic Telemetry'
			}).addTo(mapInstance);
			
			if (polylineLayerGroup) {
				polylineLayerGroup.bringToFront();
			}
		});
	}

	function renderRoads() {
		if (!mapInstance || !polylineLayerGroup || !isLeafletReady) return;

		polylineLayerGroup.clearLayers();

		if (!roads || roads.length === 0) return;

		import('leaflet').then((L) => {
			roads.forEach((road) => {
				if (!road.coordinates || road.coordinates.length < 2) return;

				const color = STATUS_COLORS[road.status] || STATUS_COLORS.unknown;
				const isSelected = selectedRoad && selectedRoad.id === road.id;

				const latLngs = road.coordinates.map(pt => [pt[0], pt[1]]);

				// Outer glowing shadow polyline
				const glowLine = L.polyline(latLngs, {
					color: color,
					weight: isSelected ? 10 : 7,
					opacity: isSelected ? 0.8 : 0.45,
					lineCap: 'round',
					lineJoin: 'round'
				});

				// Core bright polyline
				const coreLine = L.polyline(latLngs, {
					color: isSelected ? '#ffffff' : color,
					weight: isSelected ? 5 : 3.5,
					opacity: 0.95,
					lineCap: 'round',
					lineJoin: 'round'
				});

				const popupContent = `
					<div class="p-2.5 font-sans text-slate-100 text-xs min-w-[170px]">
						<div class="font-bold text-sm text-cyan-300">${road.road_name || road.name}</div>
						<div class="text-[10px] text-slate-400 mb-2">${road.district ? `${road.district}, ` : ''}${road.city || ''}</div>
						<div class="flex items-center justify-between text-xs py-0.5 border-t border-white/10 pt-1">
							<span class="text-slate-400">Status:</span>
							<span class="font-bold uppercase" style="color: ${color}">${road.status}</span>
						</div>
						<div class="flex items-center justify-between text-xs py-0.5">
							<span class="text-slate-400">Kecepatan:</span>
							<span class="font-semibold text-white">${road.speed_kmh} km/j</span>
						</div>
						<div class="flex items-center justify-between text-xs py-0.5">
							<span class="text-slate-400">Est. Waktu:</span>
							<span class="font-semibold text-cyan-400">~${road.delay_minutes || 5} mnt</span>
						</div>
					</div>
				`;

				coreLine.bindPopup(popupContent, {
					className: 'traffic-leaflet-popup',
					closeButton: false
				});

				glowLine.on('click', () => {
					if (onRoadSelect) onRoadSelect(road);
					coreLine.openPopup();
				});

				coreLine.on('click', () => {
					if (onRoadSelect) onRoadSelect(road);
				});

				polylineLayerGroup.addLayer(glowLine);
				polylineLayerGroup.addLayer(coreLine);
			});
		});
	}

	$effect(() => {
		if (roads && isLeafletReady) {
			renderRoads();
		}
	});

	export function setView(lat, lng, newZoom = 14) {
		if (mapInstance) {
			mapInstance.flyTo([lat, lng], newZoom, {
				duration: 1.2,
				easeLinearity: 0.25
			});
		}
	}
</script>

<div class="relative w-full h-full min-h-[380px] lg:min-h-[520px] rounded-3xl overflow-hidden glass-panel border border-white/10 shadow-2xl">
	<!-- Map DOM container -->
	<div bind:this={mapElement} class="w-full h-full z-10 bg-slate-950"></div>

	<!-- Loading overlay if leaflet not yet ready -->
	{#if !isLeafletReady}
		<div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/80 backdrop-blur-md">
			<div class="w-8 h-8 rounded-full border-2 border-cyan-500 border-t-transparent animate-spin mb-3"></div>
			<div class="text-xs text-slate-300 font-medium tracking-wide">Memuat Google Maps Lalu Lintas...</div>
		</div>
	{/if}

	<!-- Top Left: Google Maps Layer Switcher -->
	<div class="absolute top-4 left-4 z-20 pointer-events-auto">
		<div class="flex items-center gap-1 p-1 rounded-2xl bg-slate-950/90 backdrop-blur-xl border border-white/15 shadow-2xl">
			<button
				onclick={() => switchTileLayer('google-traffic')}
				class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold flex items-center gap-1.5 transition-all duration-200 {activeLayer === 'google-traffic' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				title="Google Maps Live Traffic"
			>
				<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
				<span>Google Traffic</span>
			</button>

			<button
				onclick={() => switchTileLayer('google-hybrid')}
				class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold flex items-center gap-1.5 transition-all duration-200 {activeLayer === 'google-hybrid' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				title="Google Satelit & Hybrid"
			>
				<Globe class="w-3.5 h-3.5 text-cyan-400" />
				<span class="hidden sm:inline">Satelit</span>
			</button>

			<button
				onclick={() => switchTileLayer('google-roadmap')}
				class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold flex items-center gap-1.5 transition-all duration-200 {activeLayer === 'google-roadmap' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				title="Google Maps Standar"
			>
				<MapIcon class="w-3.5 h-3.5 text-cyan-400" />
				<span class="hidden sm:inline">Standar</span>
			</button>

			<button
				onclick={() => switchTileLayer('dark')}
				class="px-2.5 py-1.5 rounded-xl text-[11px] font-semibold flex items-center gap-1.5 transition-all duration-200 {activeLayer === 'dark' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				title="Dark High Contrast"
			>
				<span>Dark</span>
			</button>
		</div>
	</div>

	<!-- Map Legend Overlay -->
	<div class="absolute bottom-4 left-4 z-20 pointer-events-none sm:pointer-events-auto">
		<div class="px-3.5 py-2.5 rounded-2xl bg-slate-900/90 backdrop-blur-xl border border-white/10 shadow-xl flex flex-wrap items-center gap-3 text-[11px]">
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
				<span class="text-slate-300">Lancar (&gt;40km/j)</span>
			</div>
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-sm shadow-amber-500/50"></span>
				<span class="text-slate-300">Ramai (25-40)</span>
			</div>
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-sm shadow-orange-500/50"></span>
				<span class="text-slate-300">Padat (15-25)</span>
			</div>
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50 animate-pulse"></span>
				<span class="text-slate-300">Macet (&lt;15)</span>
			</div>
		</div>
	</div>
</div>

<style>
	:global(.traffic-leaflet-popup .leaflet-popup-content-wrapper) {
		background: rgba(15, 23, 42, 0.95) !important;
		backdrop-filter: blur(16px) !important;
		border: 1px solid rgba(255, 255, 255, 0.15) !important;
		border-radius: 1rem !important;
		box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.6) !important;
		padding: 0 !important;
	}
	:global(.traffic-leaflet-popup .leaflet-popup-tip) {
		background: rgba(15, 23, 42, 0.95) !important;
	}
</style>

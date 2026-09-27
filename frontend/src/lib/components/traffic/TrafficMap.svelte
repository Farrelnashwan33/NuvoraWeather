<script>
	import { onMount, onDestroy } from 'svelte';
	import { browser } from '$app/environment';

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
	let polylineLayerGroup = null;
	let isLeafletReady = $state(false);
	let moveDebounceTimer = null;

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

			// Add dark/neon tailored tile layer
			L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
				subdomains: 'abcd',
				maxZoom: 19,
				attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
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

	function renderRoads() {
		if (!mapInstance || !polylineLayerGroup || !window.L && !isLeafletReady) return;

		polylineLayerGroup.clearLayers();

		if (!roads || roads.length === 0) return;

		roads.forEach((road) => {
			if (!road.coordinates || road.coordinates.length < 2) return;

			const color = STATUS_COLORS[road.status] || STATUS_COLORS.unknown;
			const isSelected = selectedRoad && selectedRoad.id === road.id;

			// Convert coordinates [lat, lng]
			const latLngs = road.coordinates.map(pt => [pt[0], pt[1]]);

			// Outer glowing shadow polyline
			const glowLine = window.L.polyline(latLngs, {
				color: color,
				weight: isSelected ? 10 : 7,
				opacity: isSelected ? 0.6 : 0.35,
				lineCap: 'round',
				lineJoin: 'round'
			});

			// Core bright polyline
			const coreLine = window.L.polyline(latLngs, {
				color: isSelected ? '#ffffff' : color,
				weight: isSelected ? 5 : 3.5,
				opacity: 0.95,
				lineCap: 'round',
				lineJoin: 'round'
			});

			// Popup & click
			const popupContent = `
				<div class="p-2 font-sans text-slate-100 text-xs min-w-[160px]">
					<div class="font-bold text-sm text-cyan-300">${road.road_name || road.name}</div>
					<div class="text-[10px] text-slate-400 mb-1.5">${road.district || ''}, ${road.city || ''}</div>
					<div class="flex items-center justify-between text-xs py-0.5">
						<span class="text-slate-400">Status:</span>
						<span class="font-bold uppercase" style="color: ${color}">${road.status}</span>
					</div>
					<div class="flex items-center justify-between text-xs py-0.5">
						<span class="text-slate-400">Kecepatan:</span>
						<span class="font-semibold text-white">${road.speed_kmh} km/j</span>
					</div>
					<div class="flex items-center justify-between text-xs py-0.5">
						<span class="text-slate-400">Waktu:</span>
						<span class="font-semibold text-cyan-400">${road.delay_minutes || 0} menit</span>
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
	}

	$effect(() => {
		// Re-render road segments when roads or selectedRoad changes
		if (roads && isLeafletReady) {
			renderRoads();
		}
	});

	// Programmatic flyTo
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
			<div class="text-xs text-slate-300 font-medium tracking-wide">Memuat Peta Lalu Lintas...</div>
		</div>
	{/if}

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

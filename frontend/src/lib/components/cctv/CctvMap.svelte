<script>
	import { onMount, onDestroy } from 'svelte';
	import { browser } from '$app/environment';

	let {
		cameras = [],
		selectedCamera = null,
		center = [-6.2088, 106.8456],
		zoom = 12,
		onBoundsChange = null,
		onCameraSelect = null
	} = $props();

	let mapElement = $state(null);
	let mapInstance = null;
	let markerLayerGroup = null;
	let isLeafletReady = $state(false);
	let moveDebounceTimer = null;

	onMount(async () => {
		if (!browser || !mapElement) return;

		try {
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

			// Dark CartoDB Neon Map
			L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
				subdomains: 'abcd',
				maxZoom: 19,
				attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
			}).addTo(mapInstance);

			L.control.zoom({ position: 'topright' }).addTo(mapInstance);

			markerLayerGroup = L.layerGroup().addTo(mapInstance);

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

			renderMarkers();
		} catch (err) {
			console.error('Error initializing CCTV Map:', err);
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

	function renderMarkers() {
		if (!mapInstance || !markerLayerGroup || !window.L && !isLeafletReady) return;

		markerLayerGroup.clearLayers();

		if (!cameras || cameras.length === 0) return;

		cameras.forEach((cam) => {
			if (!cam.latitude || !cam.longitude) return;

			const isOnline = cam.status === 'online';
			const isSelected = selectedCamera && selectedCamera.id === cam.id;

			const markerHtml = `
				<div class="cctv-marker-pin ${isOnline ? 'online' : 'offline'} ${isSelected ? 'selected' : ''}">
					<div class="cctv-marker-glow"></div>
					<div class="cctv-marker-inner">
						<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
							<path d="m22 8-6 4 6 4V8Z" fill="currentColor"/>
							<rect width="14" height="12" x="2" y="6" rx="2"/>
						</svg>
					</div>
				</div>
			`;

			const customIcon = window.L.divIcon({
				className: 'custom-cctv-div-icon',
				html: markerHtml,
				iconSize: [34, 34],
				iconAnchor: [17, 34],
				popupAnchor: [0, -34]
			});

			const marker = window.L.marker([cam.latitude, cam.longitude], { icon: customIcon });

			const popupContent = `
				<div class="p-2 font-sans text-slate-100 text-xs min-w-[180px]">
					<div class="font-bold text-sm text-cyan-300">${cam.name}</div>
					<div class="text-[10px] text-slate-400 mb-2">${cam.road ? `${cam.road}, ` : ''}${cam.city}, ${cam.province}</div>
					<div class="flex items-center justify-between py-1 border-t border-white/10">
						<span class="text-[10px] text-slate-400">Status:</span>
						<span class="font-bold text-[10px] uppercase ${isOnline ? 'text-emerald-400' : 'text-rose-400'}">${cam.status}</span>
					</div>
					<div class="flex items-center justify-between py-0.5">
						<span class="text-[10px] text-slate-400">Sumber:</span>
						<span class="text-[10px] font-semibold text-cyan-400">${cam.source_name || 'ATCS'}</span>
					</div>
					<button class="w-full mt-2 py-1 px-2 rounded-lg bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 font-bold text-[10px] text-center transition">
						Lihat Live Streaming
					</button>
				</div>
			`;

			marker.bindPopup(popupContent, {
				className: 'cctv-leaflet-popup',
				closeButton: false
			});

			marker.on('click', () => {
				if (onCameraSelect) onCameraSelect(cam);
			});

			markerLayerGroup.addLayer(marker);
		});
	}

	$effect(() => {
		if (cameras && isLeafletReady) {
			renderMarkers();
		}
	});

	export function setView(lat, lng, newZoom = 15) {
		if (mapInstance) {
			mapInstance.flyTo([lat, lng], newZoom, {
				duration: 1.2,
				easeLinearity: 0.25
			});
		}
	}
</script>

<div class="relative w-full h-full min-h-[380px] lg:min-h-[520px] rounded-3xl overflow-hidden glass-panel border border-white/10 shadow-2xl">
	<div bind:this={mapElement} class="w-full h-full z-10 bg-slate-950"></div>

	{#if !isLeafletReady}
		<div class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/80 backdrop-blur-md">
			<div class="w-8 h-8 rounded-full border-2 border-cyan-500 border-t-transparent animate-spin mb-3"></div>
			<div class="text-xs text-slate-300 font-medium tracking-wide">Memuat Peta CCTV Indonesia...</div>
		</div>
	{/if}

	<!-- Map Legend Overlay -->
	<div class="absolute bottom-4 left-4 z-20 pointer-events-none sm:pointer-events-auto">
		<div class="px-3.5 py-2.5 rounded-2xl bg-slate-900/90 backdrop-blur-xl border border-white/10 shadow-xl flex items-center gap-3 text-[11px]">
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-cyan-400 shadow-sm shadow-cyan-400/50 animate-pulse"></span>
				<span class="text-slate-300">CCTV Online</span>
			</div>
			<div class="flex items-center gap-1.5">
				<span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
				<span class="text-slate-400">Offline / Maintenance</span>
			</div>
		</div>
	</div>
</div>

<style>
	:global(.custom-cctv-div-icon) {
		background: transparent;
		border: none;
	}
	:global(.cctv-marker-pin) {
		position: relative;
		width: 34px;
		height: 34px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
	}
	:global(.cctv-marker-glow) {
		position: absolute;
		inset: 0;
		border-radius: 9999px;
		opacity: 0.4;
		transform: scale(0.85);
		transition: all 0.3s ease;
	}
	:global(.cctv-marker-pin.online .cctv-marker-glow) {
		background: radial-gradient(circle, rgba(6, 182, 212, 0.9) 0%, rgba(6, 182, 212, 0) 70%);
		animation: pulse-glow 2s infinite ease-in-out;
	}
	:global(.cctv-marker-pin.offline .cctv-marker-glow) {
		background: radial-gradient(circle, rgba(148, 163, 184, 0.4) 0%, rgba(148, 163, 184, 0) 70%);
	}
	:global(.cctv-marker-inner) {
		position: relative;
		width: 28px;
		height: 28px;
		border-radius: 9999px;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1.5px solid rgba(255, 255, 255, 0.3);
		box-shadow: 0 4px 10px rgba(0,0,0,0.5);
		transition: all 0.2s ease;
	}
	:global(.cctv-marker-pin.online .cctv-marker-inner) {
		background: linear-gradient(135deg, #06b6d4, #2563eb);
		color: #ffffff;
	}
	:global(.cctv-marker-pin.offline .cctv-marker-inner) {
		background: #334155;
		color: #94a3b8;
		border-color: rgba(255,255,255,0.1);
	}
	:global(.cctv-marker-pin:hover .cctv-marker-inner),
	:global(.cctv-marker-pin.selected .cctv-marker-inner) {
		transform: scale(1.2);
		border-color: #ffffff;
		box-shadow: 0 0 16px rgba(6, 182, 212, 0.8);
	}
	:global(.cctv-leaflet-popup .leaflet-popup-content-wrapper) {
		background: rgba(15, 23, 42, 0.95) !important;
		backdrop-filter: blur(16px) !important;
		border: 1px solid rgba(255, 255, 255, 0.15) !important;
		border-radius: 1rem !important;
		box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.6) !important;
		padding: 0 !important;
	}
	:global(.cctv-leaflet-popup .leaflet-popup-tip) {
		background: rgba(15, 23, 42, 0.95) !important;
	}
	@keyframes pulse-glow {
		0%, 100% { transform: scale(0.85); opacity: 0.3; }
		50% { transform: scale(1.3); opacity: 0.7; }
	}
</style>

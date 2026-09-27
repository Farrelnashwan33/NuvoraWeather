<script>
	import { onMount, onDestroy } from 'svelte';
	import { Map as MapIcon, Maximize2, Minimize2, Eye, ShieldAlert } from 'lucide-svelte';

	let { earthquakes = [], userCoords = null, onSelectEarthquake, onOpenShakemap } = $props();

	let mapElement = $state(null);
	let mapInstance = null;
	let layersGroup = null;
	let isFullscreen = $state(false);
	let activeLayer = $state('dark');

	let tileLayers = {
		'dark': 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}',
		'google-satellite': 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
		'google-terrain': 'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}'
	};

	let currentTile = null;

	onMount(async () => {
		if (typeof window !== 'undefined') {
			const L = await import('leaflet');

			if (mapElement && !mapInstance) {
				mapInstance = L.map(mapElement, {
					center: [-2.5489, 118.0149], // Indonesia center
					zoom: 5,
					zoomControl: false,
					attributionControl: false
				});

				currentTile = L.tileLayer(tileLayers[activeLayer], {
					maxZoom: 18,
					subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
				}).addTo(mapInstance);

				layersGroup = L.layerGroup().addTo(mapInstance);
				renderMarkers(L);
			}
		}
	});

	$effect(() => {
		if (mapInstance && earthquakes) {
			import('leaflet').then((L) => {
				renderMarkers(L);
			});
		}
	});

	function renderMarkers(L) {
		if (!mapInstance || !layersGroup) return;

		layersGroup.clearLayers();

		// Add user location pin if available
		if (userCoords?.latitude && userCoords?.longitude) {
			const userIcon = L.divIcon({
				className: 'user-geo-pin',
				html: `
					<div class="relative flex items-center justify-center">
						<div class="absolute -inset-2 rounded-full bg-cyan-400/40 animate-ping"></div>
						<div class="w-4 h-4 rounded-full bg-cyan-400 border-2 border-white shadow-lg"></div>
					</div>
				`,
				iconSize: [20, 20],
				iconAnchor: [10, 10]
			});

			L.marker([userCoords.latitude, userCoords.longitude], { icon: userIcon })
				.bindTooltip("Lokasi Anda", { permanent: false, direction: 'top' })
				.addTo(layersGroup);
		}

		// Add earthquake markers
		earthquakes.forEach((eq, index) => {
			if (!eq.latitude || !eq.longitude) return;

			const mag = eq.magnitude;
			const isLatest = index === 0;

			const colorHex = mag >= 5.0 ? '#f43f5e' : (mag >= 4.0 ? '#f59e0b' : '#38bdf8');
			const ringRadius = Math.max(15000, mag * 25000);

			// Pulsing zone circle
			L.circle([eq.latitude, eq.longitude], {
				radius: ringRadius,
				color: colorHex,
				weight: isLatest ? 2 : 1,
				fillColor: colorHex,
				fillOpacity: isLatest ? 0.25 : 0.12
			}).addTo(layersGroup);

			// Magnitude icon marker
			const customIcon = L.divIcon({
				className: 'earthquake-marker',
				html: `
					<div class="relative flex items-center justify-center cursor-pointer group">
						${isLatest ? `<div class="absolute -inset-2 rounded-full bg-rose-500/40 animate-ping"></div>` : ''}
						<div class="px-2.5 py-1 rounded-xl font-mono text-xs font-extrabold text-white shadow-xl flex items-center gap-1 border transition-transform hover:scale-110" style="background-color: ${colorHex}dd; border-color: ${colorHex};">
							<span>M ${mag.toFixed(1)}</span>
						</div>
					</div>
				`,
				iconSize: [60, 30],
				iconAnchor: [30, 15]
			});

			const marker = L.marker([eq.latitude, eq.longitude], { icon: customIcon }).addTo(layersGroup);

			// Popup
			const popupContent = `
				<div class="p-3 font-sans text-xs text-slate-100 bg-slate-950 rounded-xl max-w-xs space-y-1.5">
					<div class="font-bold text-sm text-white flex items-center gap-2">
						<span class="px-2 py-0.5 rounded text-[11px] font-mono font-extrabold" style="background:${colorHex}33; color:${colorHex};">
							M ${mag.toFixed(1)}
						</span>
						<span>${eq.depth}</span>
					</div>
					<div class="text-slate-300 leading-snug">${eq.location}</div>
					<div class="text-[10px] text-slate-400 font-mono">${eq.display_time}</div>
					${eq.distance_formatted ? `<div class="text-emerald-400 font-medium text-[11px]">${eq.distance_formatted}</div>` : ''}
					<div class="text-[10px] text-cyan-300 border-t border-white/10 pt-1">${eq.potensi_tsunami}</div>
				</div>
			`;

			marker.bindPopup(popupContent, {
				className: 'custom-eq-popup'
			});
		});
	}

	function changeLayer(type) {
		activeLayer = type;
		if (mapInstance && currentTile) {
			import('leaflet').then((L) => {
				mapInstance.removeLayer(currentTile);
				currentTile = L.tileLayer(tileLayers[type], {
					maxZoom: 18,
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
			<MapIcon class="w-4 h-4 text-rose-400" />
			<h2 class="font-heading font-bold text-base sm:text-lg text-white">
				Peta Seismik & Episentrum Gempa BMKG
			</h2>
			<span class="text-xs text-slate-400 font-mono hidden sm:inline">
				({earthquakes.length} Titik Kejadian)
			</span>
		</div>

		<!-- Layer selector & Fullscreen -->
		<div class="flex items-center gap-2">
			<div class="flex items-center bg-slate-900/80 rounded-xl p-0.5 border border-white/10 text-xs">
				<button
					onclick={() => changeLayer('dark')}
					class="px-2.5 py-1 rounded-lg transition {activeLayer === 'dark' ? 'bg-cyan-500 text-white font-medium' : 'text-slate-400 hover:text-white'}"
				>
					Dark
				</button>
				<button
					onclick={() => changeLayer('google-satellite')}
					class="px-2.5 py-1 rounded-lg transition {activeLayer === 'google-satellite' ? 'bg-cyan-500 text-white font-medium' : 'text-slate-400 hover:text-white'}"
				>
					Satelit
				</button>
				<button
					onclick={() => changeLayer('google-terrain')}
					class="px-2.5 py-1 rounded-lg transition {activeLayer === 'google-terrain' ? 'bg-cyan-500 text-white font-medium' : 'text-slate-400 hover:text-white'}"
				>
					Relief
				</button>
			</div>

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
	<div class="relative w-full rounded-2xl overflow-hidden border border-white/10 bg-slate-950 {isFullscreen ? 'flex-1 min-h-[450px]' : 'h-72 sm:h-96'}">
		<div bind:this={mapElement} class="w-full h-full z-10"></div>

		<!-- Legend Overlay -->
		<div class="absolute bottom-3 left-3 z-20 flex flex-wrap items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/85 backdrop-blur-md border border-white/10 text-[11px] text-white shadow-xl">
			<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> M ≥ 5.0</span>
			<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> M 4.0 - 4.9</span>
			<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-cyan-400"></span> M &lt; 4.0</span>
		</div>
	</div>
</div>

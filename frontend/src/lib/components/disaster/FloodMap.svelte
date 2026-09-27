<script>
	import { onMount, onDestroy } from 'svelte';
	import { Droplets, Maximize2, Minimize2, Map as MapIcon, Info } from 'lucide-svelte';

	let { stations = [], userCoords = null } = $props();

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
					center: [-6.5, 107.5], // Western/Central Java basin focus default
					zoom: 7,
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
		if (mapInstance && stations) {
			import('leaflet').then((L) => {
				renderMarkers(L);
			});
		}
	});

	function renderMarkers(L) {
		if (!mapInstance || !layersGroup) return;

		layersGroup.clearLayers();

		// Add user pin if available
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

		// Add flood station pins
		stations.forEach((st) => {
			if (!st.latitude || !st.longitude) return;

			const isBahaya = st.status === 'BAHAYA';
			const isSiaga = st.status === 'SIAGA';
			const isWaspada = st.status === 'WASPADA';
			const colorHex = isBahaya ? '#f43f5e' : (isSiaga ? '#f97316' : (isWaspada ? '#f59e0b' : '#10b981'));

			const customIcon = L.divIcon({
				className: 'flood-station-marker',
				html: `
					<div class="relative flex items-center justify-center cursor-pointer group">
						${isBahaya || isSiaga ? `<div class="absolute -inset-2 rounded-full animate-ping" style="background-color: ${colorHex}55;"></div>` : ''}
						<div class="px-2.5 py-1 rounded-xl font-mono text-xs font-bold text-white shadow-xl flex items-center gap-1 border transition-transform hover:scale-110" style="background-color: ${colorHex}ee; border-color: ${colorHex};">
							<span>${st.water_level_formatted}</span>
						</div>
					</div>
				`,
				iconSize: [70, 30],
				iconAnchor: [35, 15]
			});

			const marker = L.marker([st.latitude, st.longitude], { icon: customIcon }).addTo(layersGroup);

			// Popup
			const popupContent = `
				<div class="p-3 font-sans text-xs text-slate-100 bg-slate-950 rounded-xl max-w-xs space-y-1.5">
					<div class="font-bold text-sm text-white flex items-center justify-between">
						<span>${st.name}</span>
					</div>
					<div class="text-slate-300">${st.river} • ${st.region}</div>
					<div class="text-sm font-extrabold font-mono text-cyan-300">Tinggi Air: ${st.water_level_formatted}</div>
					<div class="px-2 py-0.5 rounded text-[10px] font-mono font-bold inline-block" style="background:${colorHex}33; color:${colorHex};">
						Status: ${st.status_label}
					</div>
					<div class="text-[10px] text-slate-400 border-t border-white/10 pt-1 font-mono">${st.agency}</div>
				</div>
			`;

			marker.bindPopup(popupContent, {
				className: 'custom-flood-popup'
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
			<MapIcon class="w-4 h-4 text-cyan-400" />
			<h2 class="font-heading font-bold text-base sm:text-lg text-white">
				Peta Pos Pantau Ketinggian Air & Daerah Aliran Sungai (DAS)
			</h2>
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
					Topografi
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

		<!-- Distinction & Status Legend Overlay -->
		<div class="absolute bottom-3 left-3 z-20 flex flex-col gap-1.5 p-2.5 rounded-xl bg-slate-950/90 backdrop-blur-md border border-white/10 text-[10px] text-slate-300 shadow-xl max-w-sm">
			<div class="font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
				<Info class="w-3 h-3 text-cyan-400" />
				<span>Keterangan Status Sensor (BWS/BBWS)</span>
			</div>
			<div class="flex flex-wrap items-center gap-2">
				<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Normal (Siaga 4)</span>
				<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Waspada (Siaga 3)</span>
				<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-400"></span> Siaga (Siaga 2)</span>
				<span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Bahaya (Siaga 1)</span>
			</div>
			<div class="text-[9px] text-slate-500 pt-0.5 border-t border-white/5">
				*Data ini merupakan titik pemantauan ketinggian air sensor aktif, bukan peta risiko genangan statis.
			</div>
		</div>
	</div>
</div>

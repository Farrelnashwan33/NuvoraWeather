<script>
	import { onMount, onDestroy } from 'svelte';
	import { browser } from '$app/environment';
	import { trafficApi } from '$lib/api.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import TrafficMap from '$lib/components/traffic/TrafficMap.svelte';
	import TrafficCard from '$lib/components/traffic/TrafficCard.svelte';
	import {
		Search,
		Compass,
		Car,
		Layers,
		Filter,
		RefreshCw,
		AlertCircle,
		MapPin,
		Activity,
		ChevronRight,
		SlidersHorizontal,
		Info
	} from 'lucide-svelte';

	// State
	let searchQuery = $state('');
	let activeFilter = $state('all'); // all, lancar, ramai, padat, macet
	let selectedRegion = $state('indonesia');
	let selectedRoad = $state(null);

	let roads = $state([]);
	let filteredRoads = $state([]);
	let hierarchy = $state(null);
	let isLoading = $state(false);
	let isLocating = $state(false);
	let locationMode = $state(false);
	let userCoords = $state(null);
	let errorMessage = $state(null);

	let mapComponentRef = $state(null);
	let mapCenter = $state([-6.2088, 106.8456]); // Default: Jakarta
	let mapZoom = $state(12);

	let searchDebounceTimer = null;
	let abortController = null;

	const REGIONS = [
		{ id: 'indonesia', label: 'Seluruh Indonesia', center: [-2.5489, 118.0149], zoom: 5 },
		{ id: 'jawa', label: 'Jawa', center: [-7.2504, 110.0], zoom: 8 },
		{ id: 'sumatera', label: 'Sumatera', center: [0.5897, 101.3431], zoom: 7 },
		{ id: 'kalimantan', label: 'Kalimantan', center: [-0.0263, 113.9213], zoom: 7 },
		{ id: 'sulawesi', label: 'Sulawesi', center: [-2.0, 120.5], zoom: 7 },
		{ id: 'bali-nusra', label: 'Bali & Nusa Tenggara', center: [-8.4095, 115.1889], zoom: 9 },
		{ id: 'maluku-papua', label: 'Maluku & Papua', center: [-3.5, 134.0], zoom: 6 }
	];

	const FILTERS = [
		{ id: 'all', label: 'Semua Status' },
		{ id: 'lancar', label: 'Lancar', color: 'text-emerald-400' },
		{ id: 'ramai', label: 'Ramai', color: 'text-amber-400' },
		{ id: 'padat', label: 'Padat', color: 'text-orange-400' },
		{ id: 'macet', label: 'Macet', color: 'text-rose-400' }
	];

	onMount(async () => {
		await loadInitialTraffic();
		loadHierarchy();
	});

	onDestroy(() => {
		if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
		if (abortController) {
			abortController.abort();
		}
	});

	async function loadInitialTraffic() {
		isLoading = true;
		errorMessage = null;
		if (abortController) abortController.abort();
		abortController = new AbortController();

		try {
			const res = await trafficApi.getTraffic({ region: selectedRegion }, { signal: abortController.signal });
			roads = res.data || [];
			applyClientFilters();
		} catch (err) {
			if (err.name !== 'AbortError') {
				console.error('Failed loading traffic:', err);
				errorMessage = 'Data sementara tidak dapat dimuat.';
			}
		} finally {
			isLoading = false;
		}
	}

	async function loadHierarchy() {
		try {
			const res = await trafficApi.getHierarchy();
			hierarchy = res.data || null;
		} catch (err) {
			console.error('Hierarchy load error:', err);
		}
	}

	function handleBoundsChange(bounds) {
		// Fetch data strictly for visible viewport bounding box
		if (locationMode || searchQuery.trim()) return;

		fetchViewportTraffic(bounds);
	}

	async function fetchViewportTraffic(bounds) {
		if (abortController) abortController.abort();
		abortController = new AbortController();

		try {
			const res = await trafficApi.getTraffic({
				north: bounds.north,
				south: bounds.south,
				east: bounds.east,
				west: bounds.west,
				region: selectedRegion !== 'indonesia' ? selectedRegion : undefined
			}, { signal: abortController.signal });

			if (res && res.data && res.data.length > 0) {
				roads = res.data;
				applyClientFilters();
			}
		} catch (err) {
			if (err.name !== 'AbortError') {
				console.warn('Viewport traffic fetch error:', err);
			}
		}
	}

	function handleSearchInput(e) {
		searchQuery = e.target.value;
		if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

		searchDebounceTimer = setTimeout(async () => {
			const q = searchQuery.trim();
			if (!q) {
				loadInitialTraffic();
				return;
			}

			isLoading = true;
			errorMessage = null;

			try {
				const res = await trafficApi.search(q);
				roads = res.data || [];
				applyClientFilters();

				if (roads.length > 0 && roads[0].coordinates && roads[0].coordinates.length > 0) {
					const firstPt = roads[0].coordinates[0];
					selectedRoad = roads[0];
					if (mapComponentRef) {
						mapComponentRef.setView(firstPt[0], firstPt[1], 14);
					}
				}
			} catch (err) {
				console.error('Search error:', err);
				errorMessage = 'Gagal melakukan pencarian lalu lintas.';
			} finally {
				isLoading = false;
			}
		}, 300);
	}

	function handleRegionSelect(regionId) {
		selectedRegion = regionId;
		searchQuery = '';
		locationMode = false;
		selectedRoad = null;

		const reg = REGIONS.find(r => r.id === regionId);
		if (reg && mapComponentRef) {
			mapComponentRef.setView(reg.center[0], reg.center[1], reg.zoom);
		}

		loadInitialTraffic();
	}

	function handleUseMyLocation() {
		if (!browser || !navigator.geolocation) {
			alert('Geolocation tidak didukung pada perangkat ini.');
			return;
		}

		isLocating = true;
		locationMode = true;
		errorMessage = null;

		navigator.geolocation.getCurrentPosition(
			async (pos) => {
				const lat = pos.coords.latitude;
				const lng = pos.coords.longitude;
				userCoords = { lat, lng };

				if (mapComponentRef) {
					mapComponentRef.setView(lat, lng, 14);
				}

				try {
					isLoading = true;
					const res = await trafficApi.getTraffic({ lat, lng, radius_km: 15 });
					roads = res.data || [];
					applyClientFilters();
				} catch (err) {
					console.error('Location traffic error:', err);
					errorMessage = 'Gagal memuat lalu lintas di sekitar Anda.';
				} finally {
					isLoading = false;
					isLocating = false;
				}
			},
			(err) => {
				isLocating = false;
				console.warn('Geolocation error:', err);
				alert('Tidak dapat mengakses lokasi Anda. Mohon izinkan akses lokasi pada browser.');
			},
			{ enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
		);
	}

	function applyClientFilters() {
		if (activeFilter === 'all') {
			filteredRoads = roads;
		} else {
			filteredRoads = roads.filter(r => r.status === activeFilter);
		}
	}

	function selectRoad(road) {
		selectedRoad = road;
		if (road && road.coordinates && road.coordinates.length > 0 && mapComponentRef) {
			const midIdx = Math.floor(road.coordinates.length / 2);
			const pt = road.coordinates[midIdx];
			mapComponentRef.setView(pt[0], pt[1], 15);
		}
	}

	$effect(() => {
		// Re-filter when activeFilter or roads changes
		if (roads) {
			applyClientFilters();
		}
	});
</script>

<svelte:head>
	<title>Lalu Lintas Indonesia | Nuvora Weather & Monitoring</title>
	<meta name="description" content="Pantau kondisi lalu lintas, kemacetan, dan kecepatan rata-rata jalan di seluruh Indonesia secara real-time." />
</svelte:head>

<div class="w-full min-h-screen px-4 sm:px-6 lg:px-8 py-4 sm:py-6 pb-28 md:pb-12 max-w-7xl mx-auto space-y-6">
	<!-- Header Title & Subtitle -->
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div>
			<div class="flex items-center gap-2 mb-1">
				<div class="p-2 rounded-xl bg-gradient-to-tr from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 text-cyan-400">
					<Car class="w-5 h-5" />
				</div>
				<h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
					Lalu Lintas Indonesia
				</h1>
			</div>
			<p class="text-xs sm:text-sm text-slate-400">
				Pantau kondisi lalu lintas, tingkat kemacetan, dan kecepatan rata-rata berdasarkan wilayah
			</p>
		</div>

		<!-- Top Action: Gunakan Lokasi Saya -->
		<div class="flex items-center gap-2.5">
			<button
				onclick={handleUseMyLocation}
				disabled={isLocating}
				class="flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-medium text-xs shadow-lg shadow-cyan-500/20 active:scale-95 transition-all duration-200 disabled:opacity-50"
			>
				<Compass class="w-4 h-4 {isLocating ? 'animate-spin' : ''}" />
				<span>{isLocating ? 'Mendeteksi Lokasi...' : 'Gunakan Lokasi Saya'}</span>
			</button>

			<button
				onclick={loadInitialTraffic}
				disabled={isLoading}
				class="p-2.5 rounded-2xl glass-panel text-slate-300 hover:text-cyan-400 hover:border-cyan-500/40 transition duration-200 active:scale-95 disabled:opacity-50"
				title="Refresh Data"
			>
				<RefreshCw class="w-4 h-4 {isLoading ? 'animate-spin' : ''}" />
			</button>
		</div>
	</div>

	<!-- Search Bar & Region Selector Row (Sticky on Mobile) -->
	<div class="sticky top-2 z-30 space-y-3 bg-slate-950/80 backdrop-blur-xl p-3 sm:p-4 rounded-2xl sm:rounded-3xl border border-white/10 shadow-2xl w-full max-w-full">
		<div class="flex flex-col sm:flex-row gap-2.5">
			<!-- Search Input -->
			<div class="relative flex-1 min-w-0">
				<Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
				<input
					type="text"
					value={searchQuery}
					oninput={handleSearchInput}
					placeholder="Cari kota, kabupaten, kecamatan, atau nama jalan..."
					class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/10 text-xs sm:text-sm text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500/60 focus:ring-1 focus:ring-cyan-500/60 transition duration-200"
				/>
				{#if isLoading}
					<div class="absolute right-3.5 top-1/2 -translate-y-1/2">
						<div class="w-4 h-4 rounded-full border-2 border-cyan-400 border-t-transparent animate-spin"></div>
					</div>
				{/if}
			</div>

			<!-- Region Selector Pills (Horizontal Scroll) -->
			<div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none shrink-0 max-w-full">
				{#each REGIONS as reg}
					<button
						onclick={() => handleRegionSelect(reg.id)}
						class="px-3 py-2 rounded-xl text-xs font-medium whitespace-nowrap transition-all duration-200 {selectedRegion === reg.id ? 'bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 font-semibold shadow-sm' : 'bg-white/5 border border-white/5 text-slate-400 hover:text-white hover:bg-white/10'}"
					>
						{reg.label}
					</button>
				{/each}
			</div>
		</div>

		<!-- Status Filter Pills -->
		<div class="flex items-center gap-2 overflow-x-auto scrollbar-none pt-1 border-t border-white/5">
			<div class="flex items-center gap-1.5 text-[11px] text-slate-400 shrink-0 mr-1">
				<Filter class="w-3.5 h-3.5 text-cyan-400" />
				<span>Filter:</span>
			</div>
			{#each FILTERS as f}
				<button
					onclick={() => { activeFilter = f.id; }}
					class="px-3 py-1 rounded-xl text-xs font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {activeFilter === f.id ? 'bg-white/15 text-white border border-white/30 shadow-sm' : 'bg-transparent text-slate-400 hover:text-slate-200'}"
				>
					<span class="{f.color || 'text-slate-300'}">{f.label}</span>
				</button>
			{/each}
		</div>
	</div>

	<!-- Location Mode Active Banner -->
	{#if locationMode}
		<div class="flex items-center justify-between p-3 rounded-2xl bg-cyan-950/40 border border-cyan-500/30 text-xs text-cyan-300">
			<div class="flex items-center gap-2">
				<MapPin class="w-4 h-4 text-cyan-400 shrink-0" />
				<span>Menampilkan lalu lintas di sekitar lokasi Anda saat ini</span>
			</div>
			<button
				onclick={() => { locationMode = false; loadInitialTraffic(); }}
				class="text-[11px] underline text-cyan-400 hover:text-white transition"
			>
				Reset Wilayah
			</button>
		</div>
	{/if}

	<!-- Error Alert State -->
	{#if errorMessage}
		<div class="flex items-center justify-between p-4 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-300">
			<div class="flex items-center gap-2.5">
				<AlertCircle class="w-5 h-5 text-rose-400 shrink-0" />
				<span>{errorMessage}</span>
			</div>
			<button
				onclick={loadInitialTraffic}
				class="px-3 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-white font-medium text-xs transition"
			>
				Coba lagi
			</button>
		</div>
	{/if}

	<!-- Main Responsive Grid: Desktop (Map 65-70%, Sidebar 30-35%), Mobile (100% Map + 1 Column List) -->
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
		<!-- Map Section (Large on Desktop / 100% on Mobile) -->
		<div class="lg:col-span-8 h-[420px] sm:h-[500px] lg:h-[680px]">
			<TrafficMap
				bind:this={mapComponentRef}
				roads={filteredRoads}
				{selectedRoad}
				center={mapCenter}
				zoom={mapZoom}
				onBoundsChange={handleBoundsChange}
				onRoadSelect={selectRoad}
			/>
		</div>

		<!-- Sidebar / Traffic Conditions List (30-35% on Desktop) -->
		<div class="lg:col-span-4 space-y-4">
			<div class="flex items-center justify-between px-1">
				<div class="flex items-center gap-2">
					<Activity class="w-4 h-4 text-cyan-400" />
					<h2 class="font-heading font-bold text-sm text-white">
						Kondisi Jalan ({filteredRoads.length})
					</h2>
				</div>
				<span class="text-[10px] text-slate-400">Real-time Telemetry</span>
			</div>

			<!-- Road Cards or Empty State -->
			{#if isLoading && filteredRoads.length === 0}
				<!-- Skeleton Loader -->
				<div class="space-y-3">
					{#each Array(4) as _}
						<div class="p-4 rounded-2xl glass-panel animate-pulse space-y-3">
							<div class="h-4 bg-white/10 rounded w-2/3"></div>
							<div class="h-3 bg-white/5 rounded w-1/2"></div>
							<div class="grid grid-cols-3 gap-2 pt-2">
								<div class="h-8 bg-white/5 rounded"></div>
								<div class="h-8 bg-white/5 rounded"></div>
								<div class="h-8 bg-white/5 rounded"></div>
							</div>
						</div>
					{/each}
				</div>
			{:else if filteredRoads.length === 0}
				<!-- Elegant Empty State -->
				<div class="p-8 rounded-3xl glass-panel text-center flex flex-col items-center justify-center space-y-3 border border-white/10">
					<div class="w-12 h-12 rounded-2xl bg-cyan-950/40 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
						<Car class="w-6 h-6 opacity-60" />
					</div>
					<div class="font-heading font-semibold text-sm text-white">
						Data belum tersedia di wilayah ini
					</div>
					<p class="text-xs text-slate-400 max-w-xs">
						Data kondisi lalu lintas akan otomatis tampil jika sensor publik dan feed resmi Dishub/ATCS tersedia.
					</p>
					<button
						onclick={() => handleRegionSelect('indonesia')}
						class="mt-2 px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-cyan-300 text-xs font-medium border border-white/10 transition"
					>
						Kembali ke Seluruh Indonesia
					</button>
				</div>
			{:else}
				<!-- Scrollable List of Traffic Cards -->
				<div class="space-y-3 max-h-[640px] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-white/10">
					{#each filteredRoads as road (road.id)}
						<TrafficCard
							{road}
							isSelected={selectedRoad?.id === road.id}
							onClick={selectRoad}
						/>
					{/each}
				</div>
			{/if}

			<!-- Notice attribution footer -->
			<div class="p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 text-[11px] text-slate-400 flex items-start gap-2.5">
				<Info class="w-4 h-4 text-cyan-400 shrink-0 mt-0.5" />
				<div>
					Sensor diperbarui berkala setiap 1-2 menit dengan data kecepatan dan waktu tempuh rata-rata.
				</div>
			</div>
		</div>
	</div>
</div>

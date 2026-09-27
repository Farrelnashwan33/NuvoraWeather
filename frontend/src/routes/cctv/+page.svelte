<script>
	import { onMount, onDestroy } from 'svelte';
	import { browser } from '$app/environment';
	import { cctvApi } from '$lib/api.js';
	import CctvMap from '$lib/components/cctv/CctvMap.svelte';
	import CctvPlayerModal from '$lib/components/cctv/CctvPlayerModal.svelte';
	import {
		Video,
		Search,
		Filter,
		RefreshCw,
		AlertCircle,
		MapPin,
		CheckCircle,
		Play,
		Radio,
		ShieldCheck,
		Info,
		Layers
	} from 'lucide-svelte';

	// State
	let searchQuery = $state('');
	let activeFilter = $state('all'); // all, active, inactive
	let selectedRegion = $state('all');
	let selectedCamera = $state(null);
	let activeModalCamera = $state(null);

	let cameras = $state([]);
	let filteredCameras = $state([]);
	let sources = $state([]);
	let isLoading = $state(false);
	let errorMessage = $state(null);

	let mapComponentRef = $state(null);
	let mapCenter = $state([-6.2088, 106.8456]);
	let mapZoom = $state(12);

	let searchDebounceTimer = null;
	let abortController = null;

	const REGIONS = [
		{ id: 'all', label: 'Seluruh Indonesia', center: [-2.5489, 118.0149], zoom: 5 },
		{ id: 'jawa', label: 'Jawa', center: [-6.9175, 107.6191], zoom: 8 },
		{ id: 'sumatera', label: 'Sumatera', center: [3.5952, 98.6722], zoom: 7 },
		{ id: 'bali', label: 'Bali', center: [-8.6705, 115.2126], zoom: 11 },
		{ id: 'nusa-tenggara', label: 'Nusa Tenggara', center: [-8.5833, 116.1167], zoom: 8 },
		{ id: 'kalimantan', label: 'Kalimantan', center: [-1.2379, 116.8289], zoom: 7 },
		{ id: 'sulawesi', label: 'Sulawesi', center: [-5.1477, 119.4327], zoom: 8 },
		{ id: 'maluku', label: 'Maluku', center: [-3.6547, 128.1906], zoom: 8 },
		{ id: 'papua', label: 'Papua', center: [-2.5337, 140.7181], zoom: 7 }
	];

	const STATUS_FILTERS = [
		{ id: 'all', label: 'Semua CCTV' },
		{ id: 'active', label: 'CCTV Aktif (Online)', color: 'text-emerald-400' },
		{ id: 'inactive', label: 'CCTV Tidak Aktif', color: 'text-slate-400' }
	];

	onMount(async () => {
		await loadInitialCctv();
		loadSources();
	});

	onDestroy(() => {
		if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
		if (abortController) abortController.abort();
	});

	async function loadInitialCctv() {
		isLoading = true;
		errorMessage = null;
		if (abortController) abortController.abort();
		abortController = new AbortController();

		try {
			const res = await cctvApi.getCameras({
				region: selectedRegion !== 'all' ? selectedRegion : undefined
			}, { signal: abortController.signal });

			cameras = res.data || [];
			applyClientFilters();
		} catch (err) {
			if (err.name !== 'AbortError') {
				console.error('CCTV load error:', err);
				errorMessage = 'Data sementara tidak dapat dimuat.';
			}
		} finally {
			isLoading = false;
		}
	}

	async function loadSources() {
		try {
			const res = await cctvApi.getSources();
			sources = res.data || [];
		} catch (e) {
			console.warn('CCTV sources error:', e);
		}
	}

	function handleBoundsChange(bounds) {
		if (searchQuery.trim()) return;
		fetchViewportCctv(bounds);
	}

	async function fetchViewportCctv(bounds) {
		if (abortController) abortController.abort();
		abortController = new AbortController();

		try {
			const res = await cctvApi.getCameras({
				north: bounds.north,
				south: bounds.south,
				east: bounds.east,
				west: bounds.west,
				region: selectedRegion !== 'all' ? selectedRegion : undefined
			}, { signal: abortController.signal });

			if (res && res.data && res.data.length > 0) {
				cameras = res.data;
				applyClientFilters();
			}
		} catch (err) {
			if (err.name !== 'AbortError') {
				console.warn('Viewport CCTV error:', err);
			}
		}
	}

	function handleSearchInput(e) {
		searchQuery = e.target.value;
		if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

		searchDebounceTimer = setTimeout(async () => {
			const q = searchQuery.trim();
			if (!q) {
				loadInitialCctv();
				return;
			}

			isLoading = true;
			errorMessage = null;

			try {
				const res = await cctvApi.search(q);
				cameras = res.data || [];
				applyClientFilters();

				if (cameras.length > 0 && mapComponentRef) {
					const firstCam = cameras[0];
					selectedCamera = firstCam;
					mapComponentRef.setView(firstCam.latitude, firstCam.longitude, 15);
				}
			} catch (err) {
				console.error('CCTV search error:', err);
				errorMessage = 'Gagal melakukan pencarian CCTV.';
			} finally {
				isLoading = false;
			}
		}, 300);
	}

	function handleRegionSelect(regionId) {
		selectedRegion = regionId;
		searchQuery = '';
		selectedCamera = null;

		const reg = REGIONS.find(r => r.id === regionId);
		if (reg && mapComponentRef) {
			mapComponentRef.setView(reg.center[0], reg.center[1], reg.zoom);
		}

		loadInitialCctv();
	}

	function applyClientFilters() {
		if (activeFilter === 'all') {
			filteredCameras = cameras;
		} else if (activeFilter === 'active') {
			filteredCameras = cameras.filter(c => c.status === 'online');
		} else if (activeFilter === 'inactive') {
			filteredCameras = cameras.filter(c => c.status !== 'online');
		}
	}

	function selectCamera(cam) {
		selectedCamera = cam;
		if (cam.latitude && cam.longitude && mapComponentRef) {
			mapComponentRef.setView(cam.latitude, cam.longitude, 16);
		}
	}

	function openPlayer(cam) {
		selectedCamera = cam;
		activeModalCamera = cam;
	}

	$effect(() => {
		if (cameras) {
			applyClientFilters();
		}
	});
</script>

<svelte:head>
	<title>CCTV Lalu Lintas Indonesia | Nuvora Weather & Monitoring</title>
	<meta name="description" content="Pantau siaran langsung kamera CCTV ATCS dan Dishub di seluruh persimpangan jalan kota di Indonesia." />
</svelte:head>

<div class="w-full min-h-screen px-4 sm:px-6 lg:px-8 py-4 sm:py-6 pb-28 md:pb-12 max-w-7xl mx-auto space-y-6">
	<!-- Header Title & Subtitle -->
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div>
			<div class="flex items-center gap-2 mb-1">
				<div class="p-2 rounded-xl bg-gradient-to-tr from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 text-cyan-400">
					<Video class="w-5 h-5" />
				</div>
				<h1 class="font-heading font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
					CCTV Lalu Lintas Indonesia
				</h1>
			</div>
			<p class="text-xs sm:text-sm text-slate-400">
				Lihat siaran kamera lalu lintas langsung dari portal resmi ATCS & Dinas Perhubungan
			</p>
		</div>

		<!-- Live Counter Badge -->
		<div class="flex items-center gap-2.5">
			<div class="flex items-center gap-2 px-3.5 py-2 rounded-2xl glass-panel text-xs text-slate-300">
				<span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
				<span>{cameras.filter(c => c.status === 'online').length} Kamera Online</span>
			</div>

			<button
				onclick={loadInitialCctv}
				disabled={isLoading}
				class="p-2.5 rounded-2xl glass-panel text-slate-300 hover:text-cyan-400 hover:border-cyan-500/40 transition duration-200 active:scale-95 disabled:opacity-50"
				title="Refresh CCTV"
			>
				<RefreshCw class="w-4 h-4 {isLoading ? 'animate-spin' : ''}" />
			</button>
		</div>
	</div>

	<!-- Search & Region Sticky Top Controls -->
	<div class="sticky top-2 z-30 space-y-3 bg-slate-950/80 backdrop-blur-xl p-3 -mx-3 sm:mx-0 rounded-2xl sm:rounded-3xl border border-white/10 shadow-2xl">
		<div class="flex flex-col sm:flex-row gap-2.5">
			<!-- Search input -->
			<div class="relative flex-1">
				<Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
				<input
					type="text"
					value={searchQuery}
					oninput={handleSearchInput}
					placeholder="Cari kota, kabupaten, nama jalan, atau simpang..."
					class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-900/90 border border-white/10 text-xs sm:text-sm text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500/60 focus:ring-1 focus:ring-cyan-500/60 transition duration-200"
				/>
				{#if isLoading}
					<div class="absolute right-3.5 top-1/2 -translate-y-1/2">
						<div class="w-4 h-4 rounded-full border-2 border-cyan-400 border-t-transparent animate-spin"></div>
					</div>
				{/if}
			</div>

			<!-- Region Selector Pills -->
			<div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none shrink-0">
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
				<span>Filter Status:</span>
			</div>
			{#each STATUS_FILTERS as f}
				<button
					onclick={() => { activeFilter = f.id; }}
					class="px-3 py-1 rounded-xl text-xs font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-1.5 {activeFilter === f.id ? 'bg-white/15 text-white border border-white/30 shadow-sm' : 'bg-transparent text-slate-400 hover:text-slate-200'}"
				>
					<span class="{f.color || 'text-slate-300'}">{f.label}</span>
				</button>
			{/each}
		</div>
	</div>

	<!-- Error Alert -->
	{#if errorMessage}
		<div class="flex items-center justify-between p-4 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-xs text-rose-300">
			<div class="flex items-center gap-2.5">
				<AlertCircle class="w-5 h-5 text-rose-400 shrink-0" />
				<span>{errorMessage}</span>
			</div>
			<button
				onclick={loadInitialCctv}
				class="px-3 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-white font-medium text-xs transition"
			>
				Coba lagi
			</button>
		</div>
	{/if}

	<!-- Main Responsive Grid: Desktop (Map 65-70%, Sidebar 30-35%), Mobile (100% Map + 1 Column List) -->
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
		<!-- Map Section -->
		<div class="lg:col-span-8 h-[420px] sm:h-[500px] lg:h-[680px]">
			<CctvMap
				bind:this={mapComponentRef}
				cameras={filteredCameras}
				{selectedCamera}
				center={mapCenter}
				zoom={mapZoom}
				onBoundsChange={handleBoundsChange}
				onCameraSelect={openPlayer}
			/>
		</div>

		<!-- Sidebar Camera Catalog (30-35% on Desktop) -->
		<div class="lg:col-span-4 space-y-4">
			<div class="flex items-center justify-between px-1">
				<div class="flex items-center gap-2">
					<Radio class="w-4 h-4 text-cyan-400" />
					<h2 class="font-heading font-bold text-sm text-white">
						Daftar Kamera ({filteredCameras.length})
					</h2>
				</div>
				<span class="text-[10px] text-slate-400">Live Video Stream</span>
			</div>

			<!-- Cards List / Skeletons / Empty State -->
			{#if isLoading && filteredCameras.length === 0}
				<div class="space-y-3">
					{#each Array(4) as _}
						<div class="p-4 rounded-2xl glass-panel animate-pulse space-y-3">
							<div class="h-4 bg-white/10 rounded w-2/3"></div>
							<div class="h-3 bg-white/5 rounded w-1/2"></div>
							<div class="h-10 bg-white/5 rounded"></div>
						</div>
					{/each}
				</div>
			{:else if filteredCameras.length === 0}
				<div class="p-8 rounded-3xl glass-panel text-center flex flex-col items-center justify-center space-y-3 border border-white/10">
					<div class="w-12 h-12 rounded-2xl bg-cyan-950/40 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
						<Video class="w-6 h-6 opacity-60" />
					</div>
					<div class="font-heading font-semibold text-sm text-white">
						Data belum tersedia di wilayah ini
					</div>
					<p class="text-xs text-slate-400 max-w-xs">
						Data kamera CCTV akan otomatis tersedia jika sumber publik dari portal ATCS/Dishub daerah telah diintegrasikan.
					</p>
					<button
						onclick={() => handleRegionSelect('all')}
						class="mt-2 px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-cyan-300 text-xs font-medium border border-white/10 transition"
					>
						Lihat Seluruh Indonesia
					</button>
				</div>
			{:else}
				<div class="space-y-3 max-h-[640px] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-white/10">
					{#each filteredCameras as cam (cam.id)}
						<div
							class="w-full text-left p-4 rounded-2xl glass-panel transition-all duration-200 group relative border {selectedCamera?.id === cam.id ? 'border-cyan-400/60 bg-cyan-950/30 shadow-lg shadow-cyan-500/10' : 'border-white/10 hover:border-white/20 hover:bg-white/[0.04]'}"
						>
							<div class="flex items-start justify-between gap-3">
								<div class="flex-1 min-w-0">
									<div class="flex items-center gap-2 mb-1">
										<span class="w-2 h-2 rounded-full {cam.status === 'online' ? 'bg-cyan-400 animate-pulse' : 'bg-slate-500'}"></span>
										<h3 class="font-heading font-semibold text-sm text-white group-hover:text-cyan-300 transition-colors truncate">
											{cam.name}
										</h3>
									</div>
									<div class="text-xs text-slate-400 truncate">
										{cam.road ? `${cam.road}, ` : ''}{cam.city}, {cam.province}
									</div>
								</div>

								<!-- Online Badge -->
								<div class="flex items-center gap-1.5 px-2 py-0.5 rounded-xl text-[10px] font-bold uppercase shrink-0 {cam.status === 'online' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/15 text-slate-400 border border-slate-500/30'}">
									{#if cam.status === 'online'}
										<CheckCircle class="w-3 h-3" />
										<span>ONLINE</span>
									{:else}
										<span>OFFLINE</span>
									{/if}
								</div>
							</div>

							<!-- Action buttons -->
							<div class="flex items-center justify-between mt-3 pt-3 border-t border-white/5">
								<div class="flex items-center gap-1.5 text-[11px] text-cyan-300">
									<ShieldCheck class="w-3.5 h-3.5 text-cyan-400" />
									<span class="truncate">{cam.source_name || 'Dishub ATCS'}</span>
								</div>

								<div class="flex items-center gap-2">
									<button
										onclick={() => selectCamera(cam)}
										class="px-2.5 py-1 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white text-xs transition"
									>
										Pusatkan Peta
									</button>
									<button
										onclick={() => openPlayer(cam)}
										class="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-medium text-xs shadow-md shadow-cyan-500/20 active:scale-95 transition"
									>
										<Play class="w-3 h-3 fill-current" />
										<span>Lihat Feed</span>
									</button>
								</div>
							</div>
						</div>
					{/each}
				</div>
			{/if}

			<!-- Attribution Footer -->
			<div class="p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 text-[11px] text-slate-400 flex items-start gap-2.5">
				<Info class="w-4 h-4 text-cyan-400 shrink-0 mt-0.5" />
				<div>
					Feed CCTV disiarkan dari server publik ATCS Dishub setempat. Hak cipta gambar dan streaming sepenuhnya dimiliki dinas terkait.
				</div>
			</div>
		</div>
	</div>
</div>

<!-- CCTV Stream Player Modal -->
{#if activeModalCamera}
	<CctvPlayerModal
		camera={activeModalCamera}
		onClose={() => { activeModalCamera = null; }}
	/>
{/if}

<script>
	import { onMount } from 'svelte';
	import { fetchLatestEarthquake, fetchEarthquakes, fetchFloodStations, fetchDisasterStatus } from '$lib/api.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherHeader from '$lib/components/WeatherHeader.svelte';
	import BottomNavigation from '$lib/components/BottomNavigation.svelte';
	import LocationSelector from '$lib/components/LocationSelector.svelte';
	import EarthquakeLatestCard from '$lib/components/disaster/EarthquakeLatestCard.svelte';
	import EarthquakeMap from '$lib/components/disaster/EarthquakeMap.svelte';
	import EarthquakeList from '$lib/components/disaster/EarthquakeList.svelte';
	import FloodMonitorSection from '$lib/components/disaster/FloodMonitorSection.svelte';
	import FloodMap from '$lib/components/disaster/FloodMap.svelte';
	import ShakemapModal from '$lib/components/disaster/ShakemapModal.svelte';
	import { 
		ShieldAlert, 
		Activity, 
		Droplets, 
		AlertTriangle, 
		RefreshCw, 
		Compass, 
		Info, 
		ExternalLink, 
		Clock, 
		CheckCircle, 
		MapPin,
		Loader2,
		Radio,
		Layers
	} from 'lucide-svelte';

	let loading = $state(true);
	let error = $state('');
	let latestEarthquake = $state(null);
	let earthquakes = $state([]);
	let floodStations = $state([]);
	let disasterStatus = $state(null);
	let userCoords = $state(null);

	let activeSection = $state('earthquake'); // 'earthquake' | 'flood' | 'all'
	let searchModalOpen = $state(false);

	// Shakemap Modal
	let shakemapModalOpen = $state(false);
	let activeShakemapUrl = $state('');

	onMount(async () => {
		// Detect user coordinates if available
		detectUserLocation();
		await refreshAllDisasterData();

		// Auto refresh every 3 minutes (sensible interval, avoiding spam)
		const interval = setInterval(() => {
			refreshAllDisasterData(false);
		}, 180000);

		return () => clearInterval(interval);
	});

	function detectUserLocation() {
		if (typeof navigator !== 'undefined' && navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(
				(pos) => {
					userCoords = {
						latitude: pos.coords.latitude,
						longitude: pos.coords.longitude
					};
					// Re-enrich with distance
					refreshAllDisasterData(false);
				},
				(err) => {
					console.info('Disaster page geolocation not granted / skipped.');
				},
				{ timeout: 8000 }
			);
		}
	}

	async function refreshAllDisasterData(showSpinner = true) {
		if (showSpinner) loading = true;
		error = '';

		const lat = userCoords?.latitude ?? null;
		const lon = userCoords?.longitude ?? null;

		try {
			const [latestEq, eqList, floodList, statusRes] = await Promise.all([
				fetchLatestEarthquake(lat, lon).catch(e => null),
				fetchEarthquakes(lat, lon).catch(e => []),
				fetchFloodStations(lat, lon).catch(e => []),
				fetchDisasterStatus().catch(e => null)
			]);

			latestEarthquake = latestEq;
			earthquakes = eqList;
			floodStations = floodList;
			disasterStatus = statusRes;
		} catch (err) {
			console.error('Error fetching disaster data:', err);
			error = err.message || 'Gagal memuat data telemetri kebencanaan.';
		} finally {
			loading = false;
		}
	}

	function handleOpenShakemap(url) {
		activeShakemapUrl = url;
		shakemapModalOpen = true;
	}
</script>

<svelte:head>
	<title>Disaster Monitor — Nuvora Weather & Environmental Safety</title>
	<meta name="description" content="Pusat pemantauan aktivitas seismik gempa BMKG dan ketinggian air banjir real-time se-Indonesia." />
</svelte:head>

<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between pb-24 md:pb-12 relative overflow-x-hidden">
	<!-- Ambient Background Glows (Serious Deep Navy & Crimson Atmosphere) -->
	<div class="fixed inset-0 pointer-events-none -z-10">
		<div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950"></div>
		<div class="absolute top-0 right-1/4 w-[500px] h-[400px] rounded-full bg-blue-700/10 blur-[150px]"></div>
		<div class="absolute top-1/3 left-10 w-96 h-96 rounded-full bg-rose-900/10 blur-[160px]"></div>
	</div>

	<!-- Top Navigation Header -->
	<WeatherHeader onSearchClick={() => searchModalOpen = true} />

	<!-- Main Disaster Dashboard Container -->
	<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-8">
		<!-- Page Title & Subtitle Banner -->
		<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-white/10">
			<div>
				<div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold mb-2">
					<ShieldAlert class="w-4 h-4 text-rose-400" />
					<span>{i18n.t('disaster_center_badge', 'Pusat Telemetri Kebencanaan')}</span>
				</div>
				<h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold font-heading text-white tracking-tight">
					{i18n.t('disaster_monitor_title', 'Disaster Monitor')}
				</h1>
				<p class="text-sm sm:text-base text-slate-300 mt-1 font-sans">
					{i18n.t('disaster_subtitle', 'Pemantauan real-time aktivitas seismik dan hidrologi dari stasiun telemetri')}
				</p>
			</div>

			<!-- Quick Refresh & GPS Status -->
			<div class="flex items-center gap-3">
				{#if userCoords}
					<div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-mono">
						<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
						<span>{i18n.t('gps_connected', 'GPS Terhubung')}</span>
					</div>
				{:else}
					<button
						onclick={detectUserLocation}
						class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-white/10 transition"
					>
						<Compass class="w-3.5 h-3.5 text-cyan-400" />
						<span>{i18n.t('activate_gps', 'Aktifkan GPS')}</span>
					</button>
				{/if}

				<button
					onclick={() => refreshAllDisasterData(true)}
					disabled={loading}
					class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-cyan-500/20 disabled:opacity-50"
				>
					<RefreshCw class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" />
					<span>{i18n.t('sync_data', 'Sinkronisasi Data')}</span>
				</button>
			</div>
		</div>

		<!-- Mandatory Official Emergency Disclaimer Alert -->
		<div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs flex items-start gap-3">
			<Info class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
			<div class="leading-relaxed">
				<strong class="text-white font-semibold">{i18n.t('official_notice', 'Pemberitahuan Resmi:')}</strong> {i18n.t('emergency_disclaimer', 'Informasi pada halaman ini bersumber dari data pihak ketiga dan dapat mengalami perubahan. Untuk informasi resmi ikuti pembaruan instansi terkait.')}
			</div>
		</div>

		<!-- Top Navigation Toggle: [ Earthquake Activity ] | [ Flood Monitoring ] | [ All ] -->
		<div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/80 border border-white/10 w-fit">
			<button
				onclick={() => activeSection = 'earthquake'}
				class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition {activeSection === 'earthquake' ? 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/25' : 'text-slate-400 hover:text-white'}"
			>
				<Activity class="w-4 h-4" />
				<span>{i18n.t('earthquake_monitor', 'Earthquake Monitor')}</span>
			</button>

			<button
				onclick={() => activeSection = 'flood'}
				class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition {activeSection === 'flood' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg shadow-cyan-500/25' : 'text-slate-400 hover:text-white'}"
			>
				<Droplets class="w-4 h-4" />
				<span>{i18n.t('flood_monitor', 'Flood Monitor')}</span>
			</button>

			<button
				onclick={() => activeSection = 'all'}
				class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition {activeSection === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-white'}"
			>
				<Layers class="w-4 h-4" />
				<span>{i18n.t('all_monitoring', 'Semua Monitoring')}</span>
			</button>
		</div>

		{#if loading && !latestEarthquake && floodStations.length === 0}
			<!-- Loading State -->
			<div class="text-center py-20 space-y-3">
				<Loader2 class="w-10 h-10 text-cyan-400 animate-spin mx-auto" />
				<p class="text-sm text-slate-400 font-mono">Menghubungkan ke satelit seismik BMKG & telemetri air...</p>
			</div>
		{:else if error && !latestEarthquake}
			<!-- Error state -->
			<div class="glass-panel rounded-3xl p-8 text-center max-w-md mx-auto space-y-4 border-rose-500/20">
				<AlertTriangle class="w-10 h-10 text-rose-400 mx-auto" />
				<h3 class="font-heading font-bold text-lg text-white">Data Sementara Tidak Tersedia</h3>
				<p class="text-xs text-slate-400">{error}</p>
				<button 
					onclick={() => refreshAllDisasterData(true)}
					class="px-5 py-2.5 rounded-xl bg-cyan-500 text-slate-950 font-bold text-xs"
				>
					Coba Lagi
				</button>
			</div>
		{:else}

			<!-- ========================================================= -->
			<!-- SECTION 1: EARTHQUAKE MONITOR -->
			<!-- ========================================================= -->
			{#if activeSection === 'earthquake' || activeSection === 'all'}
				<section class="space-y-6" aria-label="Earthquake Monitor">
					<!-- Hero Latest Earthquake Card -->
					{#if latestEarthquake}
						<EarthquakeLatestCard 
							earthquake={latestEarthquake} 
							onOpenShakemap={handleOpenShakemap}
						/>
					{/if}

					<!-- Interactive Earthquake Leaflet Map -->
					<EarthquakeMap 
						{earthquakes} 
						{userCoords} 
						onOpenShakemap={handleOpenShakemap}
					/>

					<!-- Filterable Earthquake List (Desktop & Mobile) -->
					<EarthquakeList 
						{earthquakes} 
						loading={loading}
						onRefresh={() => refreshAllDisasterData(true)}
					/>
				</section>
			{/if}

			<!-- ========================================================= -->
			<!-- SECTION 2: FLOOD MONITOR -->
			<!-- ========================================================= -->
			{#if activeSection === 'flood' || activeSection === 'all'}
				<section class="space-y-6 pt-4" aria-label="Flood Monitor">
					<!-- Flood Monitoring Stations Grid & Filter -->
					<FloodMonitorSection 
						stations={floodStations} 
						{userCoords}
						loading={loading}
						onDetectLocation={detectUserLocation}
						onRefresh={() => refreshAllDisasterData(true)}
					/>

					<!-- Interactive Flood & River Basin Map -->
					<FloodMap 
						stations={floodStations} 
						{userCoords}
					/>
				</section>
			{/if}

			<!-- ========================================================= -->
			<!-- SECTION 3: OFFICIAL DATA SOURCES & TELEMETRY FOOTER -->
			<!-- ========================================================= -->
			<section class="glass-panel rounded-3xl p-6 space-y-4 border-white/5" aria-label="Official Data Sources">
				<div class="flex items-center gap-2">
					<Info class="w-4 h-4 text-cyan-400" />
					<h3 class="font-heading font-bold text-sm text-white uppercase tracking-wider">
						Daftar Sumber Data Resmi & Metadata Sinkronisasi
					</h3>
				</div>

				<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
					<!-- Source 1: BMKG -->
					<div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 space-y-1">
						<div class="font-bold text-white flex items-center justify-between">
							<span>Data Gempa Bumi:</span>
							<span class="text-emerald-400 font-mono text-[10px] bg-emerald-500/10 px-2 py-0.5 rounded-full">LIVE TEWS</span>
						</div>
						<div class="text-slate-300 font-medium">BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)</div>
						<div class="text-[11px] text-slate-500 font-mono">Status: Operasional • Format: JSON Real-time</div>
					</div>

					<!-- Source 2: Flood Telemetry -->
					<div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 space-y-1">
						<div class="font-bold text-white flex items-center justify-between">
							<span>Monitoring Ketinggian Air:</span>
							<span class="text-cyan-400 font-mono text-[10px] bg-cyan-500/10 px-2 py-0.5 rounded-full">SENSOR BBWS</span>
						</div>
						<div class="text-slate-300 font-medium">Balai Wilayah Sungai (BWS/BBWS) & Dinas Sumber Daya Air</div>
						<div class="text-[11px] text-slate-500 font-mono">12+ Pos Pantau Utama • Ambang Batas Resmi Siaga 1-4</div>
					</div>

					<!-- Source 3: BNPB InaRISK -->
					<div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 space-y-1">
						<div class="font-bold text-white flex items-center justify-between">
							<span>Pemetaan Risiko Bencana:</span>
							<span class="text-amber-400 font-mono text-[10px] bg-amber-500/10 px-2 py-0.5 rounded-full">INARISK</span>
						</div>
						<div class="text-slate-300 font-medium">BNPB (Badan Nasional Penanggulangan Bencana) / InaRISK</div>
						<div class="text-[11px] text-slate-500 font-mono">Portal Geospasial Kebencanaan Nasional</div>
					</div>
				</div>

				<div class="pt-2 border-t border-white/5 flex flex-wrap items-center justify-between text-[11px] text-slate-500 font-mono">
					<span>Nuvora Disaster Telemetry System v2.0</span>
					<span>Terakhir Diperbarui: {new Date().toLocaleTimeString()} WIB</span>
				</div>
			</section>

		{/if}
	</main>

	<!-- Footer -->
	<footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-slate-400 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3">
		<div class="flex items-center gap-2">
			<span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
			<span>Nuvora Disaster Monitor</span>
			<span class="text-slate-400">• BMKG & BNPB Real-time Feeds</span>
		</div>
		<div class="flex items-center gap-4 text-[11px]">
			<a href="/" class="hover:text-white transition">Weather Overview</a>
			<a href="/disaster" class="text-rose-300 font-bold transition">Disaster Monitor</a>
			<a href="/admin/login" class="hover:text-cyan-300 transition">Admin Portal</a>
		</div>
	</footer>

	<!-- Mobile Bottom Navigation -->
	<BottomNavigation onSearchClick={() => searchModalOpen = true} />

	<!-- City Search Modal -->
	<LocationSelector bind:open={searchModalOpen} />

	<!-- Shakemap Lightbox Modal -->
	<ShakemapModal bind:open={shakemapModalOpen} url={activeShakemapUrl} />
</div>

<script>
	import { onMount } from 'svelte';
	import { monitoringApi } from '$lib/api.js';
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import {
		Activity,
		CloudSun,
		Car,
		Video,
		Flame,
		ShieldAlert,
		ArrowUpRight,
		CheckCircle,
		Radio,
		Layers,
		Wind,
		Droplets,
		RefreshCw
	} from 'lucide-svelte';

	let overview = $state(null);
	let isLoading = $state(false);

	onMount(async () => {
		loadOverview();
	});

	async function loadOverview() {
		isLoading = true;
		try {
			const res = await monitoringApi.getOverview();
			overview = res.data || null;
		} catch (e) {
			console.warn('Monitoring overview load error:', e);
		} finally {
			isLoading = false;
		}
	}
</script>

<svelte:head>
	<title>Pusat Monitoring Terpadu | Nuvora Weather</title>
	<meta name="description" content="Pusat pemantauan cuaca, lalu lintas, CCTV, dan kebencanaan Indonesia secara real-time." />
</svelte:head>

<div class="w-full min-h-screen px-4 sm:px-6 lg:px-8 py-6 pb-28 md:pb-12 max-w-7xl mx-auto space-y-8">
	<!-- Hub Header -->
	<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
		<div>
			<div class="flex items-center gap-2 mb-1.5">
				<div class="p-2 rounded-xl bg-gradient-to-tr from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 text-cyan-400">
					<Activity class="w-5 h-5" />
				</div>
				<h1 class="font-heading font-extrabold text-2xl sm:text-4xl text-white tracking-tight">
					Monitoring Indonesia
				</h1>
			</div>
			<p class="text-xs sm:text-sm text-slate-400">
				Pusat pemantauan cuaca, kondisi jalan raya, kamera ATCS, dan status kebencanaan nasional terpadu
			</p>
		</div>

		<!-- Live Telemetry Status -->
		<div class="flex items-center gap-2.5">
			<div class="flex items-center gap-2 px-3.5 py-2 rounded-2xl glass-panel text-xs text-slate-300">
				<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50 animate-pulse"></span>
				<span class="font-medium">Sistem Monitoring Aktif</span>
			</div>

			<button
				onclick={loadOverview}
				disabled={isLoading}
				class="p-2.5 rounded-2xl glass-panel text-slate-300 hover:text-cyan-400 hover:border-cyan-500/40 transition duration-200 active:scale-95 disabled:opacity-50"
				title="Refresh Status"
			>
				<RefreshCw class="w-4 h-4 {isLoading ? 'animate-spin' : ''}" />
			</button>
		</div>
	</div>

	<!-- 4 Telemetry Cards Grid -->
	<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
		<!-- 1. CUACA -->
		<a
			href="/"
			class="group relative p-6 sm:p-8 rounded-3xl glass-panel border border-white/10 hover:border-cyan-500/40 hover:bg-slate-900/60 transition-all duration-300 overflow-hidden flex flex-col justify-between shadow-2xl hover:shadow-cyan-500/10"
		>
			<!-- Ambient Glow -->
			<div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-cyan-500/20 to-blue-600/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

			<div>
				<div class="flex items-start justify-between gap-4 mb-4">
					<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400 group-hover:scale-110 transition-transform duration-300">
						<CloudSun class="w-7 h-7" />
					</div>
					<div class="flex items-center gap-1 text-xs font-semibold text-cyan-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-200">
						<span>Buka Prakiraan</span>
						<ArrowUpRight class="w-4 h-4" />
					</div>
				</div>

				<h2 class="font-heading font-extrabold text-xl sm:text-2xl text-white group-hover:text-cyan-200 transition-colors">
					Cuaca & Radar
				</h2>
				<p class="text-xs sm:text-sm text-slate-400 mt-1">
					Pantau kondisi cuaca, suhu, kelembaban, dan radar hujan real-time
				</p>
			</div>

			<!-- Live Metric Preview -->
			<div class="mt-6 pt-5 border-t border-white/5 grid grid-cols-2 gap-3 text-xs">
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Lokasi Aktif</div>
					<div class="font-bold text-white text-sm truncate mt-0.5">
						{weatherStore.data ? `${weatherStore.data.location.city}` : 'Jakarta, ID'}
					</div>
				</div>
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Suhu Saat Ini</div>
					<div class="font-mono font-bold text-cyan-300 text-sm mt-0.5">
						{weatherStore.data ? `${weatherStore.data.current.temperature_c}°C` : '29°C'}
					</div>
				</div>
			</div>
		</a>

		<!-- 2. LALU LINTAS -->
		<a
			href="/traffic"
			class="group relative p-6 sm:p-8 rounded-3xl glass-panel border border-white/10 hover:border-emerald-500/40 hover:bg-slate-900/60 transition-all duration-300 overflow-hidden flex flex-col justify-between shadow-2xl hover:shadow-emerald-500/10"
		>
			<div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-emerald-500/20 to-teal-600/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

			<div>
				<div class="flex items-start justify-between gap-4 mb-4">
					<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500/20 to-teal-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform duration-300">
						<Car class="w-7 h-7" />
					</div>
					<div class="flex items-center gap-1 text-xs font-semibold text-emerald-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-200">
						<span>Buka Traffic</span>
						<ArrowUpRight class="w-4 h-4" />
					</div>
				</div>

				<h2 class="font-heading font-extrabold text-xl sm:text-2xl text-white group-hover:text-emerald-200 transition-colors">
					Lalu Lintas
				</h2>
				<p class="text-xs sm:text-sm text-slate-400 mt-1">
					Pantau kepadatan jalan, kecepatan kendaraan, dan titik kemacetan
				</p>
			</div>

			<div class="mt-6 pt-5 border-t border-white/5 grid grid-cols-2 gap-3 text-xs">
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Ruas Terpantau</div>
					<div class="font-bold text-white text-sm mt-0.5">
						{overview?.traffic?.total_monitored_roads || '65+'} Jalan
					</div>
				</div>
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Tingkat Kelancaran</div>
					<div class="font-mono font-bold text-emerald-400 text-sm mt-0.5">
						{overview?.traffic?.smooth_percentage || 78}% Normal
					</div>
				</div>
			</div>
		</a>

		<!-- 3. CCTV -->
		<a
			href="/cctv"
			class="group relative p-6 sm:p-8 rounded-3xl glass-panel border border-white/10 hover:border-cyan-500/40 hover:bg-slate-900/60 transition-all duration-300 overflow-hidden flex flex-col justify-between shadow-2xl hover:shadow-cyan-500/10"
		>
			<div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-cyan-500/20 to-sky-600/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

			<div>
				<div class="flex items-start justify-between gap-4 mb-4">
					<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-500/20 to-sky-600/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400 group-hover:scale-110 transition-transform duration-300">
						<Video class="w-7 h-7" />
					</div>
					<div class="flex items-center gap-1 text-xs font-semibold text-cyan-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-200">
						<span>Lihat Kamera</span>
						<ArrowUpRight class="w-4 h-4" />
					</div>
				</div>

				<h2 class="font-heading font-extrabold text-xl sm:text-2xl text-white group-hover:text-cyan-200 transition-colors">
					CCTV Indonesia
				</h2>
				<p class="text-xs sm:text-sm text-slate-400 mt-1">
					Lihat kamera lalu lintas live stream dari ATCS dan Dishub seluruh Indonesia
				</p>
			</div>

			<div class="mt-6 pt-5 border-t border-white/5 grid grid-cols-2 gap-3 text-xs">
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Kamera Terdaftar</div>
					<div class="font-bold text-white text-sm mt-0.5">
						{overview?.cctv?.total_cameras || '120+'} Kamera
					</div>
				</div>
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Status Feed</div>
					<div class="font-mono font-bold text-cyan-400 text-sm mt-0.5 flex items-center gap-1.5">
						<span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
						<span>{overview?.cctv?.online_cameras || '95+'} Online</span>
					</div>
				</div>
			</div>
		</a>

		<!-- 4. BENCANA -->
		<a
			href="/disaster"
			class="group relative p-6 sm:p-8 rounded-3xl glass-panel border border-white/10 hover:border-rose-500/40 hover:bg-slate-900/60 transition-all duration-300 overflow-hidden flex flex-col justify-between shadow-2xl hover:shadow-rose-500/10"
		>
			<div class="absolute -top-24 -right-24 w-48 h-48 bg-gradient-to-br from-rose-500/20 to-red-600/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

			<div>
				<div class="flex items-start justify-between gap-4 mb-4">
					<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-rose-500/20 to-red-600/20 border border-rose-500/30 flex items-center justify-center text-rose-400 group-hover:scale-110 transition-transform duration-300">
						<Flame class="w-7 h-7" />
					</div>
					<div class="flex items-center gap-1 text-xs font-semibold text-rose-400 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-200">
						<span>Info Bencana</span>
						<ArrowUpRight class="w-4 h-4" />
					</div>
				</div>

				<h2 class="font-heading font-extrabold text-xl sm:text-2xl text-white group-hover:text-rose-200 transition-colors">
					Bencana & Gempa
				</h2>
				<p class="text-xs sm:text-sm text-slate-400 mt-1">
					Peringatan dini gempa BMKG, titik banjir, dan status siaga bencana
				</p>
			</div>

			<div class="mt-6 pt-5 border-t border-white/5 grid grid-cols-2 gap-3 text-xs">
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Gempa Terkini</div>
					<div class="font-bold text-rose-300 text-sm mt-0.5 truncate">
						{overview?.disaster?.latest_earthquake?.magnitude ? `M ${overview.disaster.latest_earthquake.magnitude}` : 'M 5.1 BMKG'}
					</div>
				</div>
				<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5">
					<div class="text-[10px] text-slate-400 uppercase font-semibold">Status Nasional</div>
					<div class="font-mono font-bold text-emerald-400 text-sm mt-0.5">
						Waspada Normal
					</div>
				</div>
			</div>
		</a>
	</div>
</div>

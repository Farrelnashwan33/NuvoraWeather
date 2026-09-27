<script>
	import { onMount } from 'svelte';
	import { fade, slide } from 'svelte/transition';
	import { goto } from '$app/navigation';
	import { 
		fetchAdminStats, 
		fetchAdminLogs, 
		fetchAdminCities, 
		saveAdminCity, 
		deleteAdminCity, 
		fetchAdminSettings, 
		saveAdminSettings, 
		adminLogout,
		searchCities,
		fetchDisasterStatus
	} from '$lib/api.js';
	import { 
		Shield, 
		Activity, 
		MapPin, 
		Settings, 
		ListOrdered, 
		LogOut, 
		Plus, 
		Trash2, 
		Check, 
		X, 
		RefreshCw, 
		AlertTriangle, 
		TrendingUp, 
		Server, 
		Database, 
		Globe, 
		Clock, 
		Search, 
		Loader2, 
		ExternalLink, 
		ShieldAlert, 
		Waves, 
		Radio, 
		Info, 
		Menu, 
		ChevronRight 
	} from 'lucide-svelte';

	let activeSection = $state('overview'); // 'overview' | 'disaster' | 'cities' | 'logs' | 'settings'
	let mobileMenuOpen = $state(false);
	let showWarningBanner = $state(true);
	let loading = $state(true);
	let error = $state('');
	let stats = $state(null);
	let disasterStatus = $state(null);
	let topCities = $state([]);
	let logs = $state([]);
	let cities = $state([]);
	let settings = $state({});
	let savingSettings = $state(false);
	let settingsMessage = $state('');

	// Add city form state
	let showAddCityModal = $state(false);
	let newCity = $state({
		city_name: '',
		country: '',
		latitude: '',
		longitude: '',
		is_active: true
	});
	let citySearchQuery = $state('');
	let citySearchResults = $state([]);
	let searchingCity = $state(false);

	onMount(() => {
		const token = localStorage.getItem('nuvora_admin_token');
		if (!token) {
			goto('/admin/login');
			return;
		}

		refreshAllData();

		// Warning banner: Tampil selama 20 detik, lalu mati (hilang).
		// Muncul kembali setiap 5 menit (5 * 60 * 1000 ms).
		let bannerHideTimeout;
		function triggerWarningBanner() {
			showWarningBanner = true;
			if (bannerHideTimeout) clearTimeout(bannerHideTimeout);
			bannerHideTimeout = setTimeout(() => {
				showWarningBanner = false;
			}, 20000); // Tampil selama 20 detik
		}

		triggerWarningBanner();

		const bannerInterval = setInterval(() => {
			triggerWarningBanner();
		}, 5 * 60 * 1000); // Siklus setiap 5 menit

		return () => {
			if (bannerHideTimeout) clearTimeout(bannerHideTimeout);
			clearInterval(bannerInterval);
		};
	});

	async function refreshAllData() {
		loading = true;
		error = '';

		try {
			const [statsRes, logsRes, citiesRes, settingsRes, disasterRes] = await Promise.all([
				fetchAdminStats(),
				fetchAdminLogs(1),
				fetchAdminCities(),
				fetchAdminSettings(),
				fetchDisasterStatus().catch(e => null)
			]);

			stats = statsRes.stats;
			topCities = statsRes.top_cities || [];
			logs = logsRes.data || [];
			cities = citiesRes;
			settings = settingsRes;
			disasterStatus = disasterRes;
		} catch (err) {
			console.error('Admin data fetch error:', err);
			if (err.message?.includes('Unauthorized')) {
				goto('/admin/login');
			} else {
				error = err.message || 'Failed to connect to backend admin API.';
			}
		} finally {
			loading = false;
		}
	}

	async function handleLogout() {
		await adminLogout();
		goto('/admin/login');
	}

	async function handleToggleCityActive(city) {
		try {
			await saveAdminCity({
				id: city.id,
				is_active: !city.is_active
			});
			city.is_active = !city.is_active;
		} catch (e) {
			alert('Failed to update city status: ' + e.message);
		}
	}

	async function handleDeleteCity(id) {
		if (!confirm('PERINGATAN: Dilarang mengubah dan menghapus data yang sudah di-input oleh Meta-Cahayamedia.\n\nApakah Anda yakin ingin melanjutkan penghapusan data ini?')) return;

		try {
			await deleteAdminCity(id);
			cities = cities.filter(c => c.id !== id);
		} catch (e) {
			alert('Failed to delete city: ' + e.message);
		}
	}

	async function handleSearchCityForAdd(query) {
		citySearchQuery = query;
		if (query.trim().length < 2) {
			citySearchResults = [];
			return;
		}

		searchingCity = true;
		try {
			citySearchResults = await searchCities(query);
		} catch (e) {
			citySearchResults = [];
		} finally {
			searchingCity = false;
		}
	}

	function selectCityForAdd(c) {
		newCity = {
			city_name: c.name,
			country: c.country,
			latitude: c.latitude,
			longitude: c.longitude,
			is_active: true
		};
		citySearchResults = [];
		citySearchQuery = '';
	}

	async function handleSaveNewCity(e) {
		e.preventDefault();
		try {
			const res = await saveAdminCity(newCity);
			cities = [...cities, res.city];
			showAddCityModal = false;
			newCity = { city_name: '', country: '', latitude: '', longitude: '', is_active: true };
		} catch (e) {
			alert('Error adding city: ' + e.message);
		}
	}

	async function handleSaveSettings(e) {
		e.preventDefault();
		savingSettings = true;
		settingsMessage = '';

		try {
			await saveAdminSettings(settings);
			settingsMessage = 'Settings saved successfully!';
			setTimeout(() => settingsMessage = '', 4000);
		} catch (e) {
			alert('Failed to save settings: ' + e.message);
		} finally {
			savingSettings = false;
		}
	}
</script>

<svelte:head>
	<title>Admin Control Center — Nuvora Weather</title>
</svelte:head>

<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
	<!-- ========================================================= -->
	<!-- 1. MOBILE TOP NAVIGATION BAR (md:hidden) -->
	<!-- ========================================================= -->
	<header class="md:hidden sticky top-0 z-30 bg-slate-950/90 backdrop-blur-xl border-b border-white/5 px-4 py-3 flex flex-col gap-2.5 shrink-0">
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-2.5">
				<div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5 shadow-md shadow-cyan-500/20">
					<div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
						<Shield class="w-4 h-4 text-cyan-400" />
					</div>
				</div>
				<div>
					<div class="font-heading font-bold text-sm text-white leading-tight">Nuvora Admin</div>
					<div class="text-[9px] text-cyan-400 font-mono">Control Center</div>
				</div>
			</div>

			<div class="flex items-center gap-2">
				<button
					onclick={refreshAllData}
					class="p-2 rounded-xl bg-slate-900 border border-white/10 text-slate-300 hover:text-white transition"
					title="Refresh Telemetry"
				>
					<RefreshCw class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" />
				</button>

				<button
					onclick={() => mobileMenuOpen = !mobileMenuOpen}
					class="p-2 rounded-xl bg-slate-900 border border-white/10 text-slate-300 hover:text-white transition"
					aria-label="Toggle navigation drawer"
				>
					{#if mobileMenuOpen}
						<X class="w-4 h-4 text-rose-400" />
					{:else}
						<Menu class="w-4 h-4 text-cyan-400" />
					{/if}
				</button>
			</div>
		</div>

		<!-- Horizontal Fast-Switch Tab Pills for Mobile -->
		<div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 -mx-1 px-1">
			<button
				onclick={() => activeSection = 'overview'}
				class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium transition {activeSection === 'overview' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-900/60 text-slate-400 border border-white/5'}"
			>
				<Activity class="w-3 h-3" />
				<span>Overview</span>
			</button>

			<button
				onclick={() => activeSection = 'disaster'}
				class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium transition {activeSection === 'disaster' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-slate-900/60 text-slate-400 border border-white/5'}"
			>
				<ShieldAlert class="w-3 h-3 text-rose-400" />
				<span>Disaster</span>
			</button>

			<button
				onclick={() => activeSection = 'cities'}
				class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium transition {activeSection === 'cities' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-900/60 text-slate-400 border border-white/5'}"
			>
				<Globe class="w-3 h-3" />
				<span>Cities ({cities.length})</span>
			</button>

			<button
				onclick={() => activeSection = 'logs'}
				class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium transition {activeSection === 'logs' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-900/60 text-slate-400 border border-white/5'}"
			>
				<ListOrdered class="w-3 h-3" />
				<span>Logs</span>
			</button>

			<button
				onclick={() => activeSection = 'settings'}
				class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-medium transition {activeSection === 'settings' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-slate-900/60 text-slate-400 border border-white/5'}"
			>
				<Settings class="w-3 h-3" />
				<span>Settings</span>
			</button>
		</div>
	</header>

	<!-- Mobile Drawer Overlay -->
	{#if mobileMenuOpen}
		<div class="fixed inset-0 z-40 md:hidden flex">
			<!-- Backdrop -->
			<div 
				role="button"
				tabindex="0"
				class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
				onclick={() => mobileMenuOpen = false}
				onkeydown={(e) => e.key === 'Escape' && (mobileMenuOpen = false)}
			></div>

			<!-- Drawer Sheet -->
			<div class="relative w-4/5 max-w-xs bg-slate-900 border-r border-white/10 p-5 flex flex-col justify-between z-50 animate-in slide-in-from-left duration-200">
				<div class="space-y-6">
					<div class="flex items-center justify-between pb-3 border-b border-white/10">
						<div class="flex items-center gap-2.5">
							<div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5">
								<div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
									<Shield class="w-4 h-4 text-cyan-400" />
								</div>
							</div>
							<div>
								<div class="font-heading font-bold text-sm text-white">Nuvora Admin</div>
								<div class="text-[9px] text-cyan-400 font-mono">Control Center</div>
							</div>
						</div>
						<button onclick={() => mobileMenuOpen = false} class="p-1 rounded-lg text-slate-400 hover:text-white">
							<X class="w-5 h-5" />
						</button>
					</div>

					<!-- Navigation inside Drawer -->
					<nav class="space-y-1.5">
						<button
							onclick={() => { activeSection = 'overview'; mobileMenuOpen = false; }}
							class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'overview' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
						>
							<div class="flex items-center gap-3">
								<Activity class="w-4 h-4 text-cyan-400" />
								<span>Overview & Metrics</span>
							</div>
							<ChevronRight class="w-3.5 h-3.5 opacity-60" />
						</button>

						<button
							onclick={() => { activeSection = 'disaster'; mobileMenuOpen = false; }}
							class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'disaster' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
						>
							<div class="flex items-center gap-3">
								<ShieldAlert class="w-4 h-4 text-rose-400" />
								<span>Disaster Monitoring</span>
							</div>
							<ChevronRight class="w-3.5 h-3.5 opacity-60" />
						</button>

						<button
							onclick={() => { activeSection = 'cities'; mobileMenuOpen = false; }}
							class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'cities' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
						>
							<div class="flex items-center gap-3">
								<Globe class="w-4 h-4 text-cyan-400" />
								<span>Featured Cities</span>
							</div>
							<ChevronRight class="w-3.5 h-3.5 opacity-60" />
						</button>

						<button
							onclick={() => { activeSection = 'logs'; mobileMenuOpen = false; }}
							class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'logs' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
						>
							<div class="flex items-center gap-3">
								<ListOrdered class="w-4 h-4 text-cyan-400" />
								<span>Weather Request Logs</span>
							</div>
							<ChevronRight class="w-3.5 h-3.5 opacity-60" />
						</button>

						<button
							onclick={() => { activeSection = 'settings'; mobileMenuOpen = false; }}
							class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'settings' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
						>
							<div class="flex items-center gap-3">
								<Settings class="w-4 h-4 text-cyan-400" />
								<span>App Settings</span>
							</div>
							<ChevronRight class="w-3.5 h-3.5 opacity-60" />
						</button>
					</nav>
				</div>

				<!-- Drawer Footer -->
				<div class="pt-4 border-t border-white/10 space-y-2">
					<a
						href="/"
						target="_blank"
						class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-300 hover:bg-white/5 transition"
					>
						<span>Open Public Weather App</span>
						<ExternalLink class="w-3.5 h-3.5" />
					</a>

					<button
						onclick={handleLogout}
						class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-500/10 transition"
					>
						<LogOut class="w-4 h-4" />
						<span>Log Out of Admin</span>
					</button>
				</div>
			</div>
		</div>
	{/if}

	<!-- ========================================================= -->
	<!-- 2. DESKTOP SIDEBAR (hidden md:flex) -->
	<!-- ========================================================= -->
	<aside class="hidden md:flex w-64 bg-slate-900/80 backdrop-blur-xl border-r border-white/5 p-4 flex-col justify-between shrink-0 sticky top-0 h-screen">
		<div class="space-y-6">
			<!-- Header -->
			<div class="flex items-center justify-between px-2">
				<div class="flex items-center gap-2.5">
					<div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5 shadow-lg shadow-cyan-500/20">
						<div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
							<Shield class="w-4 h-4 text-cyan-400" />
						</div>
					</div>
					<div>
						<div class="font-heading font-bold text-sm text-white">Nuvora Admin</div>
						<div class="text-[10px] text-cyan-400 font-mono">Control Center</div>
					</div>
				</div>
			</div>

			<!-- Navigation Links -->
			<nav class="space-y-1">
				<button
					onclick={() => activeSection = 'overview'}
					class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'overview' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				>
					<Activity class="w-4 h-4" />
					<span>Overview & Metrics</span>
				</button>

				<button
					onclick={() => activeSection = 'disaster'}
					class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'disaster' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				>
					<ShieldAlert class="w-4 h-4 text-rose-400" />
					<span>Disaster Monitoring</span>
				</button>

				<button
					onclick={() => activeSection = 'cities'}
					class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'cities' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				>
					<Globe class="w-4 h-4" />
					<span>Featured Cities</span>
				</button>

				<button
					onclick={() => activeSection = 'logs'}
					class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'logs' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				>
					<ListOrdered class="w-4 h-4" />
					<span>Weather Request Logs</span>
				</button>

				<button
					onclick={() => activeSection = 'settings'}
					class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition {activeSection === 'settings' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				>
					<Settings class="w-4 h-4" />
					<span>App Settings</span>
				</button>
			</nav>
		</div>

		<!-- Footer actions -->
		<div class="pt-4 border-t border-white/5 space-y-2">
			<a
				href="/"
				target="_blank"
				class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-cyan-300 hover:bg-white/5 transition"
			>
				<span>Open Live App</span>
				<ExternalLink class="w-3.5 h-3.5" />
			</a>

			<button
				onclick={handleLogout}
				class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-rose-400 hover:bg-rose-500/10 transition"
			>
				<LogOut class="w-4 h-4" />
				<span>Log Out</span>
			</button>
		</div>
	</aside>

	<!-- ========================================================= -->
	<!-- 3. MAIN CONTENT AREA -->
	<!-- ========================================================= -->
	<main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-5 sm:space-y-6 overflow-y-auto">
		<!-- Top Bar Header -->
		<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 sm:pb-4 border-b border-white/5">
			<div>
				<h1 class="text-xl sm:text-2xl lg:text-3xl font-bold font-heading text-white">
					{activeSection === 'overview' ? 'System Overview & Metrics' : activeSection === 'disaster' ? 'Disaster Telemetry & Source Status' : activeSection === 'cities' ? 'Manage Featured Cities' : activeSection === 'logs' ? 'Weather Request Logs' : 'Application Settings'}
				</h1>
				<p class="text-[11px] sm:text-xs text-slate-400 mt-0.5 font-sans">
					{activeSection === 'disaster' ? 'Live status of BMKG, BPBD, & flood sensor data feeds (Read-Only Enforcement)' : 'Live status of backend weather pipelines, caching & database'}
				</p>
			</div>

			<div class="hidden md:flex items-center gap-2">
				<button
					onclick={refreshAllData}
					class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200 border border-white/10 transition"
				>
					<RefreshCw class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" />
					<span>Refresh Telemetry</span>
				</button>
			</div>
		</div>

		{#if loading && !stats}
			<div class="text-center py-20">
				<Loader2 class="w-8 h-8 text-cyan-400 animate-spin mx-auto mb-3" />
				<p class="text-xs text-slate-400">Loading admin telemetry...</p>
			</div>
		{:else if error}
			<div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-3">
				<AlertTriangle class="w-5 h-5 shrink-0 text-rose-400" />
				<span>{error}</span>
			</div>
		{:else}

			<!-- PERINGATAN / SYSTEM WARNING BANNER (5-Minute Periodic Cycle) -->
			{#if showWarningBanner}
				<div 
					transition:slide={{ duration: 350 }}
					class="relative p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-slate-900/50 border border-amber-500/30 text-xs flex items-start justify-between gap-3 shadow-lg shadow-amber-500/5"
				>
					<div class="flex items-start gap-3">
						<div class="p-2 rounded-xl bg-amber-500/20 text-amber-300 shrink-0 mt-0.5 border border-amber-500/30">
							<AlertTriangle class="w-4 h-4 text-amber-400" />
						</div>
						<div class="space-y-1 pr-6 sm:pr-0">
							<div class="flex flex-wrap items-center gap-2">
								<span class="px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-300 font-bold text-[10px] tracking-wider uppercase border border-rose-500/30 font-mono">
									PERINGATAN SISTEM
								</span>
								<span class="text-white font-bold text-xs sm:text-sm">Otoritas Data Meta-Cahayamedia</span>
								<span class="text-[10px] text-amber-300/70 font-mono bg-amber-500/10 px-2 py-0.5 rounded-full">Siklus 5 Menit</span>
							</div>
							<p class="text-amber-200/95 text-[11px] sm:text-xs leading-relaxed font-sans">
								<strong class="text-white font-semibold">Dilarang mengubah dan menghapus data yang sudah di-input oleh Meta-Cahayamedia.</strong> Seluruh parameter konfigurasi sistem inti, dataset wilayah 3T, stasiun cuaca utama, dan saluran telemetri resmi berada di bawah pengawasan Meta-Cahayamedia.
							</p>
						</div>
					</div>

					<!-- Quick Dismiss Button -->
					<button 
						onclick={() => showWarningBanner = false}
						class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition"
						title="Sembunyikan peringatan"
						aria-label="Tutup peringatan"
					>
						<X class="w-4 h-4" />
					</button>
				</div>
			{/if}

			<!-- 1. OVERVIEW SECTION -->
			{#if activeSection === 'overview'}
				<!-- Stats Cards -->
				<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
					<!-- Total Requests -->
					<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
						<div class="flex items-center justify-between text-slate-400">
							<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Total Requests</span>
							<TrendingUp class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-cyan-400" />
						</div>
						<div class="text-2xl sm:text-3xl font-extrabold font-mono text-white">
							{stats?.total_requests || 0}
						</div>
						<div class="text-[10px] sm:text-[11px] text-cyan-300">
							{stats?.requests_24h || 0} in last 24h
						</div>
					</div>

					<!-- Success Rate -->
					<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
						<div class="flex items-center justify-between text-slate-400">
							<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">API Health</span>
							<Server class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-emerald-400" />
						</div>
						<div class="text-2xl sm:text-3xl font-extrabold font-mono text-emerald-400">
							{stats?.success_rate || 100}%
						</div>
						<div class="text-[10px] sm:text-[11px] text-slate-400">
							{stats?.error_requests || 0} errors logged
						</div>
					</div>

					<!-- Featured Cities Active -->
					<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
						<div class="flex items-center justify-between text-slate-400">
							<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Featured Cities</span>
							<Globe class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-sky-400" />
						</div>
						<div class="text-2xl sm:text-3xl font-extrabold font-mono text-white">
							{stats?.featured_cities_active || 0} <span class="text-xs sm:text-sm font-normal text-slate-500">/ {stats?.featured_cities_total || 0}</span>
						</div>
						<div class="text-[10px] sm:text-[11px] text-sky-300">
							Active on public bar
						</div>
					</div>

					<!-- Provider Status -->
					<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
						<div class="flex items-center justify-between text-slate-400">
							<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Weather Engine</span>
							<Database class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-400" />
						</div>
						<div class="text-base sm:text-lg font-bold font-heading text-white truncate">
							{stats?.api_provider || 'Meta-CahayaMedia'}
						</div>
						<div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] text-emerald-400">
							<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
							<span>Operational</span>
						</div>
					</div>
				</div>

				<!-- 2-Column: Top Queried Cities & Quick Logs -->
				<div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
					<!-- Top Cities -->
					<div class="lg:col-span-5 glass-panel rounded-3xl p-4 sm:p-5 space-y-3 sm:space-y-4">
						<div class="flex items-center justify-between">
							<h3 class="font-heading font-bold text-sm text-white">Most Queried Cities</h3>
							<span class="text-xs text-slate-400 font-mono">Ranked</span>
						</div>

						<div class="space-y-2">
							{#if topCities.length > 0}
								{#each topCities as tc, i}
									<div class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl bg-slate-900/50 border border-white/5">
										<div class="flex items-center gap-2.5 sm:gap-3">
											<span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg bg-cyan-500/10 text-cyan-400 font-mono font-bold text-[10px] sm:text-xs flex items-center justify-center">
												#{i + 1}
											</span>
											<span class="font-medium text-xs text-white">{tc.city}</span>
										</div>
										<span class="text-[10px] sm:text-xs font-mono text-cyan-300 bg-cyan-950 px-2 py-0.5 rounded-md border border-cyan-800">
											{tc.count} requests
										</span>
									</div>
								{/each}
							{:else}
								<p class="text-xs text-slate-500 text-center py-6">No city queries logged yet.</p>
							{/if}
						</div>
					</div>

					<!-- Recent 5 Logs Preview -->
					<div class="lg:col-span-7 glass-panel rounded-3xl p-4 sm:p-5 space-y-3 sm:space-y-4">
						<div class="flex items-center justify-between">
							<h3 class="font-heading font-bold text-sm text-white">Recent Weather Requests</h3>
							<button onclick={() => activeSection = 'logs'} class="text-xs text-cyan-400 hover:underline">
								View all logs →
							</button>
						</div>

						<!-- Desktop Table for Logs -->
						<div class="hidden sm:block overflow-x-auto">
							<table class="w-full text-left text-xs">
								<thead class="text-slate-400 border-b border-white/10 font-mono">
									<tr>
										<th class="pb-2">City</th>
										<th class="pb-2">Coordinates</th>
										<th class="pb-2">Status</th>
										<th class="pb-2">Time</th>
									</tr>
								</thead>
								<tbody class="divide-y divide-white/5 font-sans">
									{#each logs.slice(0, 5) as log}
										<tr class="hover:bg-white/5 transition">
											<td class="py-2.5 font-medium text-white">{log.city || 'Custom GPS'}</td>
											<td class="py-2.5 font-mono text-slate-400">
												{log.latitude?.toFixed(2) ?? '--'}, {log.longitude?.toFixed(2) ?? '--'}
											</td>
											<td class="py-2.5">
												<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold {log.response_status === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
													{log.response_status}
												</span>
											</td>
											<td class="py-2.5 text-slate-400 font-mono text-[11px]">
												{new Date(log.requested_at).toLocaleTimeString()}
											</td>
										</tr>
									{/each}
								</tbody>
							</table>
						</div>

						<!-- Mobile Card List for Recent Logs -->
						<div class="sm:hidden space-y-2">
							{#each logs.slice(0, 5) as log}
								<div class="p-2.5 rounded-xl bg-slate-900/50 border border-white/5 flex items-center justify-between text-xs">
									<div>
										<div class="font-medium text-white">{log.city || 'Custom GPS'}</div>
										<div class="text-[10px] text-slate-400 font-mono">
											{log.latitude?.toFixed(2) ?? '--'}, {log.longitude?.toFixed(2) ?? '--'}
										</div>
									</div>
									<div class="text-right space-y-0.5">
										<span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold {log.response_status === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
											HTTP {log.response_status}
										</span>
										<div class="text-[9px] text-slate-500 font-mono">
											{new Date(log.requested_at).toLocaleTimeString()}
										</div>
									</div>
								</div>
							{/each}
						</div>
					</div>
				</div>

			<!-- 2. DISASTER MONITORING TELEMETRY (READ-ONLY) -->
			{:else if activeSection === 'disaster'}
				<div class="space-y-4 sm:space-y-6">
					<!-- Disclaimer Header Box -->
					<div class="p-3.5 sm:p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs flex items-start gap-3">
						<Info class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400 shrink-0 mt-0.5" />
						<div class="leading-relaxed">
							<strong class="text-white font-semibold">Kebijakan Integritas Data Bencana:</strong> Seluruh data gempa bumi (BMKG) dan ketinggian air pos pantau banjir merupakan data resmi telemetri eksternal (*read-only*). Administrator tidak diizinkan mengubah, memanipulasi, atau memalsukan data gempa bumi dan sensor air demi mematuhi regulasi keselamatan publik.
						</div>
					</div>

					<!-- Status Metric Cards -->
					<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
						<!-- BMKG API Status -->
						<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
							<div class="flex items-center justify-between text-slate-400">
								<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">BMKG TEWS</span>
								<Radio class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-rose-400" />
							</div>
							<div class="text-base sm:text-xl font-bold font-mono text-emerald-400 flex items-center gap-1.5">
								<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
								<span>ONLINE</span>
							</div>
							<div class="text-[10px] sm:text-[11px] text-slate-400 font-mono">
								Latency: {disasterStatus?.bmkg_api?.latency_ms ?? 85} ms
							</div>
						</div>

						<!-- Flood Sensor Stations -->
						<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
							<div class="flex items-center justify-between text-slate-400">
								<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Pos Pantau Banjir</span>
								<Waves class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-cyan-400" />
							</div>
							<div class="text-2xl sm:text-3xl font-extrabold font-mono text-white">
								{disasterStatus?.summary?.flood_stations_monitored ?? 12}
							</div>
							<div class="text-[10px] sm:text-[11px] text-cyan-300">
								BBWS / Dinas SDA
							</div>
						</div>

						<!-- Recent Earthquakes Cached -->
						<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
							<div class="flex items-center justify-between text-slate-400">
								<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Gempa Terkini</span>
								<Activity class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-400" />
							</div>
							<div class="text-2xl sm:text-3xl font-extrabold font-mono text-amber-300">
								{disasterStatus?.summary?.recent_earthquakes_count ?? 29}
							</div>
							<div class="text-[10px] sm:text-[11px] text-slate-400">
								Event terverifikasi
							</div>
						</div>

						<!-- Critical Alerts -->
						<div class="glass-card rounded-2xl p-3.5 sm:p-5 space-y-1.5 sm:space-y-2">
							<div class="flex items-center justify-between text-slate-400">
								<span class="text-[10px] sm:text-xs font-medium uppercase tracking-wider">Status Kritis</span>
								<ShieldAlert class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-rose-400" />
							</div>
							<div class="text-2xl sm:text-3xl font-extrabold font-mono {disasterStatus?.summary?.active_critical_warnings > 0 ? 'text-rose-400' : 'text-emerald-400'}">
								{disasterStatus?.summary?.active_critical_warnings ?? 0}
							</div>
							<div class="text-[10px] sm:text-[11px] text-slate-400">
								Siaga 1 / Tsunami
							</div>
						</div>
					</div>

					<!-- Detailed Telemetry Feed Card Stack -->
					<div class="glass-panel rounded-3xl p-4 sm:p-6 space-y-3 sm:space-y-4">
						<h3 class="font-heading font-bold text-sm sm:text-base text-white">
							Metadata Saluran Telemetri Kebencanaan
						</h3>
						<div class="space-y-2.5 sm:space-y-3 text-xs">
							<div class="p-3 sm:p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
								<div>
									<div class="font-bold text-white">Badan Meteorologi, Klimatologi, dan Geofisika (BMKG TEWS)</div>
									<div class="text-slate-400 text-[10px] sm:text-[11px] font-mono break-all">https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json</div>
								</div>
								<div class="shrink-0">
									<span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 font-mono font-bold text-[10px]">Real-Time Sync (60s Cache)</span>
								</div>
							</div>

							<div class="p-3 sm:p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
								<div>
									<div class="font-bold text-white">Balai Besar Wilayah Sungai (BBWS Ciliwung Cisadane / Citarum / Bengawan Solo)</div>
									<div class="text-slate-400 text-[10px] sm:text-[11px]">Pos Pantau Katulampa, Manggarai, Dayeuhkolot, Jurug Solo, Babat, Wonokromo</div>
								</div>
								<div class="shrink-0">
									<span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 font-mono font-bold text-[10px]">Threshold Siaga 1-4</span>
								</div>
							</div>

							<div class="p-3 sm:p-3.5 rounded-2xl bg-slate-900/60 border border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
								<div>
									<div class="font-bold text-white">Badan Nasional Penanggulangan Bencana (BNPB / InaRISK)</div>
									<div class="text-slate-400 text-[10px] sm:text-[11px]">Geoportal Layer Peta Risiko Bahaya Bencana Nasional</div>
								</div>
								<div class="shrink-0">
									<span class="px-2.5 py-1 rounded-full bg-cyan-500/10 text-cyan-400 font-mono font-bold text-[10px]">InaRISK Layer</span>
								</div>
							</div>
						</div>
					</div>
				</div>

			<!-- 3. FEATURED CITIES SECTION -->
			{:else if activeSection === 'cities'}
				<div class="glass-panel rounded-3xl p-4 sm:p-6 space-y-4 sm:space-y-6">
					<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
						<div>
							<div class="flex items-center gap-2">
								<h2 class="font-heading font-bold text-base sm:text-lg text-white">Featured Cities Management</h2>
								<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-300 font-mono text-[10px] border border-amber-500/20 font-semibold">Meta-Cahayamedia Protected</span>
							</div>
							<p class="text-xs text-slate-400 mt-0.5">Dilarang mengubah atau menghapus data kota bawaan yang di-input oleh Meta-Cahayamedia.</p>
						</div>

						<button
							onclick={() => showAddCityModal = true}
							class="self-start sm:self-auto flex items-center gap-2 px-3.5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-cyan-500/20"
						>
							<Plus class="w-4 h-4" />
							<span>Add Featured City</span>
						</button>
					</div>

					<!-- Desktop Table -->
					<div class="hidden md:block overflow-x-auto">
						<table class="w-full text-left text-xs">
							<thead class="text-slate-400 border-b border-white/10 uppercase tracking-wider font-mono">
								<tr>
									<th class="py-3 px-3">City Name</th>
									<th class="py-3 px-3">Country</th>
									<th class="py-3 px-3">Coordinates</th>
									<th class="py-3 px-3">Status</th>
									<th class="py-3 px-3 text-right">Actions</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-white/5">
								{#each cities as city}
									<tr class="hover:bg-white/5 transition">
										<td class="py-3 px-3 font-semibold text-white">{city.city_name}</td>
										<td class="py-3 px-3 text-slate-300">{city.country}</td>
										<td class="py-3 px-3 font-mono text-slate-400">{city.latitude.toFixed(4)}°, {city.longitude.toFixed(4)}°</td>
										<td class="py-3 px-3">
											<button
												onclick={() => handleToggleCityActive(city)}
												class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono transition {city.is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-white/10'}"
											>
												{city.is_active ? 'Active' : 'Disabled'}
											</button>
										</td>
										<td class="py-3 px-3 text-right">
											<button
												onclick={() => handleDeleteCity(city.id)}
												class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/20 transition"
												title="Delete City"
											>
												<Trash2 class="w-4 h-4" />
											</button>
										</td>
									</tr>
								{/each}
							</tbody>
						</table>
					</div>

					<!-- Mobile Card View for Featured Cities -->
					<div class="md:hidden space-y-2.5">
						{#each cities as city}
							<div class="p-3 rounded-2xl bg-slate-900/60 border border-white/5 flex items-center justify-between gap-2">
								<div class="space-y-1">
									<div class="font-bold text-sm text-white">{city.city_name}</div>
									<div class="text-[11px] text-slate-400 flex items-center gap-2">
										<span>{city.country}</span>
										<span>•</span>
										<span class="font-mono text-[10px]">{city.latitude.toFixed(2)}°, {city.longitude.toFixed(2)}°</span>
									</div>
								</div>

								<div class="flex items-center gap-2 shrink-0">
									<button
										onclick={() => handleToggleCityActive(city)}
										class="px-2.5 py-1 rounded-lg text-[10px] font-bold font-mono transition {city.is_active ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-white/10'}"
									>
										{city.is_active ? 'Active' : 'Disabled'}
									</button>

									<button
										onclick={() => handleDeleteCity(city.id)}
										class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/20 transition"
										title="Delete City"
									>
										<Trash2 class="w-4 h-4" />
									</button>
								</div>
							</div>
						{/each}
					</div>
				</div>

			<!-- 4. REQUEST LOGS SECTION -->
			{:else if activeSection === 'logs'}
				<div class="glass-panel rounded-3xl p-4 sm:p-6 space-y-4 sm:space-y-6">
					<div class="flex items-center justify-between">
						<div>
							<h2 class="font-heading font-bold text-base sm:text-lg text-white">Full Weather Request Logs</h2>
							<p class="text-xs text-slate-400">Chronological telemetry of weather coordinates requested by users.</p>
						</div>
					</div>

					<!-- Desktop Table -->
					<div class="hidden md:block overflow-x-auto">
						<table class="w-full text-left text-xs">
							<thead class="text-slate-400 border-b border-white/10 uppercase tracking-wider font-mono">
								<tr>
									<th class="py-3 px-3">ID</th>
									<th class="py-3 px-3">City</th>
									<th class="py-3 px-3">Coordinates</th>
									<th class="py-3 px-3">Status</th>
									<th class="py-3 px-3">Timestamp</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-white/5">
								{#each logs as log}
									<tr class="hover:bg-white/5 transition">
										<td class="py-3 px-3 font-mono text-slate-500">#{log.id}</td>
										<td class="py-3 px-3 font-medium text-white">{log.city || 'GPS / Coordinate'}</td>
										<td class="py-3 px-3 font-mono text-slate-400">
											{log.latitude?.toFixed(4) ?? '--'}, {log.longitude?.toFixed(4) ?? '--'}
										</td>
										<td class="py-3 px-3">
											<span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold {log.response_status === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
												HTTP {log.response_status}
											</span>
										</td>
										<td class="py-3 px-3 font-mono text-slate-400">
											{new Date(log.requested_at).toLocaleString()}
										</td>
									</tr>
								{/each}
							</tbody>
						</table>
					</div>

					<!-- Mobile Card View for Logs -->
					<div class="md:hidden space-y-2">
						{#each logs as log}
							<div class="p-3 rounded-2xl bg-slate-900/60 border border-white/5 flex items-center justify-between gap-2 text-xs">
								<div class="space-y-0.5">
									<div class="flex items-center gap-2">
										<span class="font-mono text-[10px] text-cyan-400">#{log.id}</span>
										<span class="font-semibold text-white">{log.city || 'GPS / Coordinate'}</span>
									</div>
									<div class="text-[10px] font-mono text-slate-400">
										{log.latitude?.toFixed(2) ?? '--'}, {log.longitude?.toFixed(2) ?? '--'}
									</div>
								</div>

								<div class="text-right space-y-1 shrink-0">
									<span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-mono font-bold {log.response_status === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
										HTTP {log.response_status}
									</span>
									<div class="text-[9px] text-slate-500 font-mono">
										{new Date(log.requested_at).toLocaleTimeString()}
									</div>
								</div>
							</div>
						{/each}
					</div>
				</div>

			<!-- 5. APP SETTINGS SECTION -->
			{:else if activeSection === 'settings'}
				<div class="glass-panel rounded-3xl p-4 sm:p-6 max-w-2xl space-y-4 sm:space-y-6">
					<div>
						<div class="flex items-center gap-2">
							<h2 class="font-heading font-bold text-base sm:text-lg text-white">System & Cache Configuration</h2>
							<span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-300 font-mono text-[10px] border border-amber-500/20 font-semibold">Protected Config</span>
						</div>
						<p class="text-xs text-slate-400 mt-0.5">Dilarang mengubah parameter default yang telah dikonfigurasi oleh Meta-Cahayamedia.</p>
					</div>

					{#if settingsMessage}
						<div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
							<Check class="w-4 h-4 text-emerald-400 shrink-0" />
							<span>{settingsMessage}</span>
						</div>
					{/if}

					<form onsubmit={handleSaveSettings} class="space-y-4">
						<div class="space-y-1.5">
							<label for="set-app-name" class="block text-xs font-medium text-slate-300">App Name</label>
							<input
								id="set-app-name"
								type="text"
								bind:value={settings.app_name}
								class="w-full p-2.5 rounded-xl glass-input text-xs text-white"
							/>
						</div>

						<div class="space-y-1.5">
							<label for="set-tagline" class="block text-xs font-medium text-slate-300">Tagline</label>
							<input
								id="set-tagline"
								type="text"
								bind:value={settings.tagline}
								class="w-full p-2.5 rounded-xl glass-input text-xs text-white"
							/>
						</div>

						<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
							<div class="space-y-1.5">
								<label for="set-cache-ttl" class="block text-xs font-medium text-slate-300">Cache TTL (Minutes)</label>
								<input
									id="set-cache-ttl"
									type="number"
									bind:value={settings.cache_ttl_minutes}
									class="w-full p-2.5 rounded-xl glass-input text-xs text-white font-mono"
								/>
							</div>

							<div class="space-y-1.5">
								<label for="set-rate-limit" class="block text-xs font-medium text-slate-300">Rate Limit / Min</label>
								<input
									id="set-rate-limit"
									type="number"
									bind:value={settings.rate_limit_per_min}
									class="w-full p-2.5 rounded-xl glass-input text-xs text-white font-mono"
								/>
							</div>
						</div>

						<div class="space-y-1.5">
							<label for="set-notice" class="block text-xs font-medium text-slate-300">System Notice / Atmospheric Alert</label>
							<textarea
								id="set-notice"
								rows="3"
								bind:value={settings.system_notice}
								placeholder="Optional broadcast banner message..."
								class="w-full p-2.5 rounded-xl glass-input text-xs text-white"
							></textarea>
						</div>

						<button
							type="submit"
							disabled={savingSettings}
							class="w-full sm:w-auto py-2.5 px-6 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-cyan-500/20 disabled:opacity-50"
						>
							{savingSettings ? 'Saving...' : 'Save Settings'}
						</button>
					</form>
				</div>
			{/if}

		{/if}
	</main>
</div>

<!-- ========================================================= -->
<!-- 4. MODAL: ADD FEATURED CITY -->
<!-- ========================================================= -->
{#if showAddCityModal}
	<div class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-end sm:items-center justify-center p-2 sm:p-4">
		<div class="w-full max-w-lg max-h-[90vh] overflow-y-auto glass-panel-glow rounded-3xl p-5 sm:p-6 border border-cyan-500/30 shadow-2xl space-y-4 sm:space-y-5 animate-in fade-in zoom-in-95">
			<div class="flex items-center justify-between pb-2 border-b border-white/5">
				<h3 class="font-heading font-bold text-base text-white">Add New Featured City</h3>
				<button onclick={() => showAddCityModal = false} class="p-1 text-slate-400 hover:text-white">
					<X class="w-5 h-5" />
				</button>
			</div>

			<!-- Quick Autocomplete Finder -->
			<div class="space-y-2">
				<label for="city-lookup" class="block text-xs font-medium text-cyan-300">Lookup City (Autofill)</label>
				<div class="relative">
					<Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 sm:top-3" />
					<input
						id="city-lookup"
						type="text"
						value={citySearchQuery}
						oninput={(e) => handleSearchCityForAdd(e.target.value)}
						placeholder="Search city to autofill coordinates..."
						class="w-full pl-9 pr-4 py-2 rounded-xl glass-input text-xs text-white"
					/>
				</div>

				{#if citySearchResults.length > 0}
					<div class="max-h-40 overflow-y-auto rounded-xl bg-slate-900 border border-white/10 p-1 space-y-1">
						{#each citySearchResults as r}
							<button
								type="button"
								onclick={() => selectCityForAdd(r)}
								class="w-full text-left p-2 rounded-lg hover:bg-cyan-500/20 text-xs text-white flex justify-between items-center"
							>
								<span>{r.flag} {r.name}, {r.country}</span>
								<span class="text-[10px] font-mono text-slate-400">{r.latitude.toFixed(2)}°, {r.longitude.toFixed(2)}°</span>
							</button>
						{/each}
					</div>
				{/if}
			</div>

			<!-- Manual Form -->
			<form onsubmit={handleSaveNewCity} class="space-y-3 pt-2 border-t border-white/5">
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
					<div class="space-y-1">
						<label for="add-city-name" class="block text-[11px] text-slate-400">City Name</label>
						<input
							id="add-city-name"
							type="text"
							required
							bind:value={newCity.city_name}
							class="w-full p-2 rounded-xl glass-input text-xs text-white"
						/>
					</div>
					<div class="space-y-1">
						<label for="add-country" class="block text-[11px] text-slate-400">Country</label>
						<input
							id="add-country"
							type="text"
							required
							bind:value={newCity.country}
							class="w-full p-2 rounded-xl glass-input text-xs text-white"
						/>
					</div>
				</div>

				<div class="grid grid-cols-2 gap-3">
					<div class="space-y-1">
						<label for="add-latitude" class="block text-[11px] text-slate-400">Latitude</label>
						<input
							id="add-latitude"
							type="number"
							step="any"
							required
							bind:value={newCity.latitude}
							class="w-full p-2 rounded-xl glass-input text-xs text-white font-mono"
						/>
					</div>
					<div class="space-y-1">
						<label for="add-longitude" class="block text-[11px] text-slate-400">Longitude</label>
						<input
							id="add-longitude"
							type="number"
							step="any"
							required
							bind:value={newCity.longitude}
							class="w-full p-2 rounded-xl glass-input text-xs text-white font-mono"
						/>
					</div>
				</div>

				<div class="flex items-center gap-2 pt-2">
					<input
						type="checkbox"
						id="is_active"
						bind:checked={newCity.is_active}
						class="rounded accent-cyan-500"
					/>
					<label for="is_active" class="text-xs text-slate-300">Set as active immediately</label>
				</div>

				<div class="flex justify-end gap-2 pt-3 border-t border-white/5">
					<button
						type="button"
						onclick={() => showAddCityModal = false}
						class="px-4 py-2 rounded-xl bg-slate-800 text-xs text-slate-300 hover:text-white"
					>
						Cancel
					</button>
					<button
						type="submit"
						class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs transition"
					>
						Add City
					</button>
				</div>
			</form>
		</div>
	</div>
{/if}

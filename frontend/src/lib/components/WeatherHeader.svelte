<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { page } from '$app/stores';
	import { Search, Compass, Sun, Moon, Sparkles, Shield, CloudSun, Globe, Activity, ChevronDown, Car, Video, Flame } from 'lucide-svelte';
	import LanguageSelectorModal from '$lib/components/LanguageSelectorModal.svelte';

	let { onSearchClick } = $props();
	let languageModalOpen = $state(false);

	let currentPath = $derived($page?.url?.pathname || '/');
</script>

<header class="w-full z-40 relative px-4 sm:px-6 lg:px-8 py-4">
	<div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
		<!-- Brand Logo -->
		<div class="flex items-center gap-3">
			<a href="/" class="flex items-center gap-2.5 group">
				<div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5 shadow-lg shadow-cyan-500/20 group-hover:shadow-cyan-500/40 transition-all duration-300">
					<div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
						<CloudSun class="w-5 h-5 text-cyan-400 group-hover:scale-110 transition duration-300" />
					</div>
				</div>
				<div>
					<div class="flex items-center gap-1.5">
						<span class="font-heading font-extrabold text-xl tracking-tight bg-gradient-to-r from-white via-cyan-100 to-sky-300 bg-clip-text text-transparent">
							Nuvora
						</span>
						<span class="font-heading font-semibold text-xl text-cyan-400">Weather</span>
					</div>
					<div class="hidden sm:block text-[10px] text-slate-400 tracking-wider font-medium uppercase">
						{i18n.t('tagline', 'KNOW YOUR WEATHER. PLAN YOUR DAY.')}
					</div>
				</div>
			</a>
		</div>

		<!-- Desktop Navigation Tabs -->
		<nav class="hidden md:flex items-center gap-1 p-1 bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-2xl relative">
			<a
				href="/"
				class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200 {currentPath === '/' && weatherStore.activeTab === 'overview' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				onclick={() => { weatherStore.activeTab = 'overview'; }}
			>
				{i18n.t('nav_overview', 'Beranda')}
			</a>
			<a
				href="/"
				class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200 {currentPath === '/' && weatherStore.activeTab === 'forecast' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				onclick={() => { weatherStore.activeTab = 'forecast'; }}
			>
				{i18n.t('nav_forecast', 'Prakiraan')}
			</a>
			<a
				href="/"
				class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200 {currentPath === '/' && weatherStore.activeTab === 'map' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				onclick={() => { weatherStore.activeTab = 'map'; }}
			>
				{i18n.t('nav_radar', 'Radar & Peta')}
			</a>

			<!-- Monitoring Dropdown Trigger -->
			<div class="relative group">
				<button
					class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200 flex items-center gap-1.5 {['/traffic', '/cctv', '/disaster', '/monitoring'].includes(currentPath) ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
					aria-expanded="false"
				>
					<Activity class="w-3.5 h-3.5 text-cyan-400" />
					<span>Monitoring</span>
					<ChevronDown class="w-3 h-3 transition-transform duration-200 group-hover:rotate-180" />
				</button>

				<!-- Dropdown Menu -->
				<div class="absolute left-0 top-full mt-2 w-56 p-1.5 bg-slate-900/95 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl shadow-black/60 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-1 group-hover:translate-y-0 z-50">
					<a
						href="/traffic"
						class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-cyan-500/15 transition duration-150 {currentPath === '/traffic' ? 'bg-cyan-500/20 text-cyan-300 font-semibold' : ''}"
					>
						<Car class="w-4 h-4 text-emerald-400" />
						<div>
							<div class="font-medium">Lalu Lintas</div>
							<div class="text-[10px] text-slate-400">Kondisi jalan & kemacetan</div>
						</div>
					</a>
					<a
						href="/cctv"
						class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-cyan-500/15 transition duration-150 {currentPath === '/cctv' ? 'bg-cyan-500/20 text-cyan-300 font-semibold' : ''}"
					>
						<Video class="w-4 h-4 text-cyan-400" />
						<div>
							<div class="font-medium">CCTV Indonesia</div>
							<div class="text-[10px] text-slate-400">Live feed ATCS / Dishub</div>
						</div>
					</a>
					<a
						href="/disaster"
						class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-cyan-500/15 transition duration-150 {currentPath === '/disaster' ? 'bg-rose-500/20 text-rose-300 font-semibold' : ''}"
					>
						<Flame class="w-4 h-4 text-rose-400" />
						<div>
							<div class="font-medium">Bencana & Gempa</div>
							<div class="text-[10px] text-slate-400">BMKG Gempa & Banjir</div>
						</div>
					</a>
					<div class="h-px bg-white/10 my-1"></div>
					<a
						href="/monitoring"
						class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-cyan-300 hover:text-white hover:bg-cyan-500/25 transition duration-150 font-medium"
					>
						<Activity class="w-4 h-4 text-cyan-400" />
						<span>Pusat Monitoring Hub</span>
					</a>
				</div>
			</div>

			<a
				href="/"
				class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all duration-200 {currentPath === '/' && weatherStore.activeTab === 'cities' ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-md shadow-cyan-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'}"
				onclick={() => { weatherStore.activeTab = 'cities'; }}
			>
				{i18n.t('nav_cities', 'Kota')}
			</a>
		</nav>

		<!-- Right Controls (Search bar trigger, Unit toggle, Theme toggle, Admin) -->
		<div class="flex items-center gap-2 sm:gap-3">
			<!-- Search Button Trigger -->
			<button
				onclick={onSearchClick}
				class="flex items-center gap-2.5 px-3.5 py-2 rounded-2xl glass-panel text-slate-300 hover:text-white hover:border-cyan-500/40 transition duration-200 text-xs shadow-sm group"
				aria-label="Search City"
			>
				<Search class="w-4 h-4 text-cyan-400 group-hover:scale-110 transition duration-200" />
				<span class="hidden sm:inline font-medium">
					{weatherStore.data ? `${weatherStore.data.location.city}, ${weatherStore.data.location.country}` : 'Search City...'}
				</span>
				<kbd class="hidden lg:inline-block px-1.5 py-0.5 text-[10px] font-mono bg-white/10 rounded text-slate-400">⌘K</kbd>
			</button>

			<!-- Geolocation trigger -->
			<button
				onclick={() => weatherStore.detectLocation()}
				title="Detect My Location"
				class="p-2.5 rounded-2xl glass-panel text-slate-300 hover:text-cyan-400 hover:border-cyan-500/40 transition duration-200"
				aria-label="Current Location"
			>
				<Compass class="w-4 h-4 text-cyan-400" />
			</button>

			<!-- Temperature Unit Toggle (°C / °F) -->
			<button
				onclick={() => weatherStore.toggleUnit()}
				class="px-2.5 py-1.5 rounded-2xl glass-panel text-xs font-mono font-bold text-cyan-300 hover:text-white hover:border-cyan-500/40 transition duration-200"
				title="Toggle Celsius / Fahrenheit"
			>
				°{weatherStore.unit}
			</button>

			<!-- Dark / Light Theme Toggle -->
			<button
				onclick={() => weatherStore.toggleTheme()}
				class="p-2.5 rounded-2xl glass-panel text-slate-300 hover:text-amber-400 hover:border-cyan-500/40 transition duration-200"
				title="Toggle Theme"
			>
				{#if weatherStore.theme === 'dark'}
					<Sun class="w-4 h-4 text-amber-300" />
				{:else}
					<Moon class="w-4 h-4 text-indigo-400" />
				{/if}
			</button>

			<!-- World Language Selector Trigger -->
			<button
				onclick={() => languageModalOpen = true}
				class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-2xl glass-panel text-xs text-slate-300 hover:text-cyan-300 hover:border-cyan-500/40 transition duration-200 shadow-sm"
				title={i18n.t('select_language', 'Pilih Bahasa Dunia')}
				aria-label="Select World Language"
			>
				<Globe class="w-4 h-4 text-cyan-400" />
				<span class="text-xs font-semibold">{i18n.activeLangObj.flag}</span>
				<span class="hidden md:inline font-mono text-[10px] font-bold uppercase text-cyan-300">{i18n.current}</span>
			</button>

			<!-- Admin Dashboard Link -->
			<a
				href="/admin/dashboard"
				class="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-2xl bg-cyan-950/40 hover:bg-cyan-900/50 border border-cyan-500/30 text-cyan-300 hover:text-cyan-100 transition duration-200 text-xs font-medium"
				title="Admin Control Room"
			>
				<Shield class="w-3.5 h-3.5 text-cyan-400" />
				<span>Admin</span>
			</a>
		</div>
	</div>
</header>

<!-- Global Language Selector Modal -->
<LanguageSelectorModal bind:open={languageModalOpen} />


<script>
	import { onMount } from 'svelte';
	import { searchCities, fetchIndonesiaRegions } from '$lib/api.js';
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Search, MapPin, X, Compass, Clock, Check, Loader2, Globe, Shield, Sparkles, Navigation } from 'lucide-svelte';

	let { open = $bindable(false), onSelect } = $props();

	let searchQuery = $state('');
	let results = $state([]);
	let loading = $state(false);
	let recentCities = $state([]);
	let indonesiaRegions = $state([]);
	let activeTab = $state('indonesia'); // 'search' | 'indonesia' | 'recent' | 'global'
	let debounceTimer = null;
	let inputRef = $state(null);

	onMount(async () => {
		try {
			indonesiaRegions = await fetchIndonesiaRegions();
		} catch (e) {
			console.warn('Could not load remote Indonesian regions:', e);
		}
	});

	$effect(() => {
		if (open) {
			loadRecent();
			setTimeout(() => inputRef?.focus(), 50);
		}
	});

	function loadRecent() {
		if (typeof localStorage !== 'undefined') {
			try {
				const saved = localStorage.getItem('nuvora_recent_searches');
				if (saved) recentCities = JSON.parse(saved);
			} catch (e) {}
		}
	}

	function saveRecent(cityObj) {
		if (typeof localStorage !== 'undefined') {
			try {
				const list = [cityObj, ...recentCities.filter(c => c.name !== cityObj.name)].slice(0, 8);
				recentCities = list;
				localStorage.setItem('nuvora_recent_searches', JSON.stringify(list));
			} catch (e) {}
		}
	}

	function handleInput(e) {
		const val = e.target.value;
		searchQuery = val;

		if (debounceTimer) clearTimeout(debounceTimer);

		if (val.trim().length < 2) {
			results = [];
			loading = false;
			return;
		}

		loading = true;
		debounceTimer = setTimeout(async () => {
			try {
				results = await searchCities(val);
			} catch (err) {
				console.error('Search error:', err);
				results = [];
			} finally {
				loading = false;
			}
		}, 260);
	}

	function selectCity(city) {
		saveRecent({
			name: city.name,
			country: city.country || 'Indonesia',
			country_code: city.country_code || 'ID',
			flag: city.flag || '🇮🇩',
			admin1: city.admin1 || '',
			latitude: city.latitude,
			longitude: city.longitude
		});

		weatherStore.loadWeather(city.latitude, city.longitude, city.name, city.country || 'Indonesia');
		open = false;
		searchQuery = '';
		results = [];
		if (onSelect) onSelect(city);
	}

	function handleGeoClick() {
		open = false;
		weatherStore.detectLocation();
	}
</script>

{#if open}
	<!-- Backdrop -->
	<div 
		class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-start justify-center p-3 sm:p-6 md:pt-14 transition-all duration-300"
		role="dialog"
		aria-modal="true"
	>
		<!-- Modal Container -->
		<div 
			class="w-full max-w-2xl glass-panel-glow rounded-3xl border border-cyan-500/25 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]"
		>
			<!-- Header / Search input bar -->
			<div class="relative p-4 md:p-5 border-b border-white/10 flex items-center gap-3 shrink-0">
				<Search class="w-5 h-5 text-cyan-400 shrink-0" />
				<input
					bind:this={inputRef}
					type="text"
					value={searchQuery}
					oninput={handleInput}
					placeholder={i18n.t('search_placeholder', 'Cari kota, wilayah, pulau di dunia...')}
					class="w-full bg-transparent text-white placeholder-slate-400 text-sm sm:text-base md:text-lg focus:outline-none"
				/>
				
				{#if loading}
					<Loader2 class="w-5 h-5 text-cyan-400 animate-spin shrink-0" />
				{:else if searchQuery}
					<button 
						onclick={() => { searchQuery = ''; results = []; }}
						class="p-1 text-slate-400 hover:text-white rounded-lg hover:bg-white/10 transition"
					>
						<X class="w-4 h-4" />
					</button>
				{/if}

				<button 
					onclick={() => open = false}
					class="p-1.5 text-slate-400 hover:text-white rounded-xl hover:bg-white/10 transition text-xs font-mono px-2.5 py-1 border border-white/10 hidden sm:block"
				>
					ESC
				</button>
			</div>

			<!-- Quick Actions Bar & Category Switcher -->
			<div class="px-4 py-2 bg-slate-900/80 border-b border-white/5 flex flex-wrap items-center justify-between gap-2 text-xs shrink-0">
				<button 
					onclick={handleGeoClick}
					class="inline-flex items-center gap-1.5 text-cyan-300 hover:text-cyan-100 transition py-1 px-2 rounded-lg bg-cyan-500/10 border border-cyan-500/20"
				>
					<Compass class="w-3.5 h-3.5 text-cyan-400 animate-spin-slow" />
					<span class="font-medium">{i18n.t('use_gps', 'Lokasi GPS Saya')}</span>
				</button>

				<!-- Sub-tabs if not searching -->
				{#if !searchQuery}
					<div class="flex items-center gap-1 bg-slate-950/80 p-0.5 rounded-xl border border-white/10">
						<button
							onclick={() => activeTab = 'indonesia'}
							class="px-2.5 py-1 rounded-lg transition {activeTab === 'indonesia' ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'}"
						>
							🇮🇩 {i18n.t('regions_indonesia', 'Daerah 3T & Terluar')}
						</button>
						<button
							onclick={() => activeTab = 'recent'}
							class="px-2.5 py-1 rounded-lg transition {activeTab === 'recent' ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'}"
						>
							🕒 {i18n.t('recent_searches', 'Terakhir')}
						</button>
						<button
							onclick={() => activeTab = 'global'}
							class="px-2.5 py-1 rounded-lg transition {activeTab === 'global' ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white'}"
						>
							🌍 {i18n.t('global_cities', 'Dunia')}
						</button>
					</div>
				{:else}
					<span class="text-slate-400 font-mono text-[11px]">
						{i18n.t('search_database_hint', 'Menelusuri direktori wilayah global & Indonesia')}
					</span>
				{/if}
			</div>

			<!-- Search Results / Directory Content -->
			<div class="flex-1 overflow-y-auto p-3 space-y-1 divide-y divide-white/5">
				{#if results.length > 0}
					<div class="px-2 py-1.5 text-xs font-semibold text-cyan-400 uppercase tracking-wider flex items-center justify-between">
						<span>{i18n.t('search_results', 'Hasil Pencarian')} ({results.length})</span>
						<span class="text-[10px] text-slate-400 lowercase font-normal font-sans">{i18n.t('click_to_select', 'klik untuk pantau cuaca')}</span>
					</div>

					{#each results as city}
						<button
							onclick={() => selectCity(city)}
							class="w-full text-left p-3 rounded-2xl hover:bg-cyan-500/10 transition flex items-center justify-between group border border-transparent hover:border-cyan-500/30"
						>
							<div class="flex items-start gap-3">
								<span class="text-2xl pt-0.5">{city.flag || '📍'}</span>
								<div class="space-y-0.5">
									<div class="font-bold text-sm text-white group-hover:text-cyan-300 transition flex items-center gap-2">
										<span>{city.name}</span>
										{#if city.badge}
											<span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
												{city.badge}
											</span>
										{/if}
									</div>
									<div class="text-xs text-slate-300 font-sans">
										{city.admin1 || city.country}
									</div>
									<div class="text-[11px] text-slate-500 font-mono">
										Lat: {city.latitude.toFixed(4)}°, Lon: {city.longitude.toFixed(4)}°
									</div>
								</div>
							</div>

							<div class="text-cyan-400 opacity-0 group-hover:opacity-100 transition text-xs font-medium px-3 py-1.5 rounded-xl bg-cyan-500/20 border border-cyan-500/40 shrink-0">
								{i18n.t('select_weather', 'Pilih Cuaca')}
							</div>
						</button>
					{/each}

				{:else if searchQuery && !loading}
					<div class="text-center py-12 text-slate-400 space-y-2">
						<MapPin class="w-10 h-10 text-slate-500 mx-auto opacity-50" />
						<p class="text-sm font-medium text-slate-300">{i18n.t('no_match', 'Tidak ada wilayah yang cocok dengan')} "{searchQuery}"</p>
						<p class="text-xs text-slate-500 max-w-sm mx-auto">
							{i18n.t('search_hint', 'Coba ketik nama pulau, kabupaten, atau kota di dunia.')}
						</p>
					</div>

				{:else if activeTab === 'indonesia'}
					<!-- Curated Outermost & Remote Regions Directory -->
					<div class="space-y-3 p-1">
						<div class="flex items-center justify-between px-1">
							<div class="text-xs font-semibold text-cyan-400 uppercase tracking-wider flex items-center gap-1.5">
								<Sparkles class="w-3.5 h-3.5" />
								<span>{i18n.t('regions_indonesia_full', 'Daftar Wilayah Terluar & Daerah 3T')} ({indonesiaRegions.length})</span>
							</div>
							<span class="text-[10px] text-slate-400 font-mono">{i18n.t('quick_access', '1-Klik Akses')}</span>
						</div>

						<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
							{#each indonesiaRegions as region}
								<button
									onclick={() => selectCity(region)}
									class="flex items-start gap-2.5 p-3 rounded-2xl bg-slate-900/50 hover:bg-cyan-500/15 border border-white/5 hover:border-cyan-500/40 text-left transition group"
								>
									<span class="text-xl pt-0.5">{region.flag || '🇮🇩'}</span>
									<div class="flex-1 min-w-0">
										<div class="flex items-center justify-between gap-1">
											<span class="text-xs font-bold text-white group-hover:text-cyan-300 transition truncate">
												{region.name}
											</span>
											{#if region.type}
												<span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 shrink-0">
													{region.type}
												</span>
											{/if}
										</div>
										<div class="text-[11px] text-slate-400 truncate">
											{region.admin1}
										</div>
										<div class="text-[10px] text-slate-500 font-mono mt-0.5">
											{region.latitude.toFixed(2)}°, {region.longitude.toFixed(2)}°
										</div>
									</div>
								</button>
							{/each}
						</div>
					</div>

				{:else if activeTab === 'recent' && recentCities.length > 0}
					<div class="space-y-1 p-1">
						<div class="px-2 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
							<Clock class="w-3.5 h-3.5" />
							<span>{i18n.t('recent_searches_title', 'Pencarian Terakhir Anda')}</span>
						</div>
						{#each recentCities as recent}
							<button
								onclick={() => selectCity(recent)}
								class="w-full text-left p-3 rounded-2xl hover:bg-white/10 transition flex items-center justify-between group"
							>
								<div class="flex items-center gap-3">
									<span class="text-2xl">{recent.flag || '📍'}</span>
									<div>
										<div class="font-bold text-sm text-slate-200 group-hover:text-cyan-300 transition">
											{recent.name}
										</div>
										<div class="text-xs text-slate-400">
											{recent.admin1 || recent.country}
										</div>
									</div>
								</div>
								<MapPin class="w-4 h-4 text-slate-500 group-hover:text-cyan-400 transition" />
							</button>
						{/each}
					</div>

				{:else}
					<!-- Global Metropolitan Cities -->
					<div class="space-y-2 p-1">
						<div class="px-2 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
							<Globe class="w-3.5 h-3.5" />
							<span>{i18n.t('global_metropolitan_cities', 'Kota Metropolitan Dunia & Indonesia')}</span>
						</div>
						<div class="grid grid-cols-2 gap-2">
							{#each [
								{ name: 'Bandung', country: 'Indonesia', flag: '🇮🇩', admin1: 'Jawa Barat', latitude: -6.9175, longitude: 107.6191 },
								{ name: 'Jakarta', country: 'Indonesia', flag: '🇮🇩', admin1: 'DKI Jakarta', latitude: -6.2088, longitude: 106.8456 },
								{ name: 'Surabaya', country: 'Indonesia', flag: '🇮🇩', admin1: 'Jawa Timur', latitude: -7.2575, longitude: 112.7521 },
								{ name: 'Medan', country: 'Indonesia', flag: '🇮🇩', admin1: 'Sumatera Utara', latitude: 3.5952, longitude: 98.6722 },
								{ name: 'Makassar', country: 'Indonesia', flag: '🇮🇩', admin1: 'Sulawesi Selatan', latitude: -5.1477, longitude: 119.4327 },
								{ name: 'Jayapura', country: 'Indonesia', flag: '🇮🇩', admin1: 'Papua', latitude: -2.5337, longitude: 140.7181 },
								{ name: 'Tokyo', country: 'Japan', flag: '🇯🇵', admin1: 'Kanto', latitude: 35.6762, longitude: 139.6503 },
								{ name: 'Singapore', country: 'Singapore', flag: '🇸🇬', admin1: 'Singapore', latitude: 1.3521, longitude: 103.8198 },
								{ name: 'London', country: 'United Kingdom', flag: '🇬🇧', admin1: 'England', latitude: 51.5074, longitude: -0.1278 },
								{ name: 'New York', country: 'United States', flag: '🇺🇸', admin1: 'New York', latitude: 40.7128, longitude: -74.0060 }
							] as pop}
								<button
									onclick={() => selectCity(pop)}
									class="flex items-center gap-2.5 p-3 rounded-2xl bg-slate-900/40 hover:bg-cyan-500/15 border border-white/5 hover:border-cyan-500/30 text-left transition"
								>
									<span class="text-xl">{pop.flag}</span>
									<div>
										<div class="text-xs font-bold text-slate-200">{pop.name}</div>
										<div class="text-[11px] text-slate-400">{pop.admin1}, {pop.country}</div>
									</div>
								</button>
							{/each}
						</div>
					</div>
				{/if}
			</div>

			<!-- Footer info -->
			<div class="p-3.5 bg-slate-900/70 border-t border-white/5 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
				<span class="flex items-center gap-1.5">
					<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
					<span>Meta-CahayaMedia Global Satellite Stream</span>
				</span>
				<button onclick={() => open = false} class="hover:text-white font-medium transition">
					{i18n.t('close', 'Tutup')}
				</button>
			</div>
		</div>
	</div>
{/if}

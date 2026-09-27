<script>
	import { onMount } from 'svelte';
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherBackground from '$lib/components/WeatherBackground.svelte';
	import WeatherHeader from '$lib/components/WeatherHeader.svelte';
	import LocationSelector from '$lib/components/LocationSelector.svelte';
	import CurrentWeather from '$lib/components/CurrentWeather.svelte';
	import WeatherStats from '$lib/components/WeatherStats.svelte';
	import HourlyForecast from '$lib/components/HourlyForecast.svelte';
	import DailyForecast from '$lib/components/DailyForecast.svelte';
	import SunriseSunset from '$lib/components/SunriseSunset.svelte';
	import WeatherMap from '$lib/components/WeatherMap.svelte';
	import FeaturedCitiesBar from '$lib/components/FeaturedCitiesBar.svelte';
	import BottomNavigation from '$lib/components/BottomNavigation.svelte';
	import LoadingSkeleton from '$lib/components/LoadingSkeleton.svelte';
	import ErrorState from '$lib/components/ErrorState.svelte';

	let searchModalOpen = $state(false);

	onMount(() => {
		weatherStore.init();
		// Initial location detection or fallback
		weatherStore.detectLocation();

		// Keyboard shortcut ⌘K or / to open search
		const handleKeydown = (e) => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
				e.preventDefault();
				searchModalOpen = true;
			}
			if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
				e.preventDefault();
				searchModalOpen = true;
			}
		};

		window.addEventListener('keydown', handleKeydown);
		return () => window.removeEventListener('keydown', handleKeydown);
	});

	let theme = $derived(weatherStore.data?.current?.background_theme || 'sunny');
	let isDay = $derived(weatherStore.data?.current?.is_day ?? true);
</script>

<!-- Dynamic Atmospheric Weather Backdrop -->
<WeatherBackground {theme} {isDay} />

<div class="relative min-h-screen flex flex-col justify-between pb-24 md:pb-12">
	<!-- Top Navigation Header -->
	<WeatherHeader onSearchClick={() => searchModalOpen = true} />

	<!-- Main Weather Content -->
	<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
		{#if weatherStore.loading && !weatherStore.data}
			<LoadingSkeleton />
		{:else if weatherStore.error && !weatherStore.data}
			<ErrorState 
				message={weatherStore.error} 
				onRetry={() => weatherStore.loadWeather(weatherStore.lastCity.lat, weatherStore.lastCity.lon, weatherStore.lastCity.name, weatherStore.lastCity.country)} 
			/>
		{:else if weatherStore.data}
			<!-- Tab Content View Switching for Desktop & Mobile -->
			{#if weatherStore.activeTab === 'overview'}
				<!-- Hero Current Weather -->
				<section aria-label="Current Weather">
					<CurrentWeather />
				</section>

				<!-- 6-Metric Highlights Grid -->
				<section aria-label="Weather Highlights">
					<WeatherStats />
				</section>

				<!-- 24-Hour Forecast Timeline Carousel -->
				<section aria-label="Hourly Forecast">
					<HourlyForecast />
				</section>

				<!-- 2-Column Section: 7-Day Outlook & Daylight/Radar -->
				<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
					<!-- Left: 7-Day Extended Forecast (7 cols) -->
					<section class="lg:col-span-7" aria-label="Weekly Forecast">
						<DailyForecast />
					</section>

					<!-- Right: Sun & Daylight Arc + Weather Radar Map (5 cols) -->
					<div class="lg:col-span-5 space-y-6">
						<section aria-label="Sun and Daylight">
							<SunriseSunset />
						</section>

						<section aria-label="Interactive Weather Map">
							<WeatherMap />
						</section>
					</div>
				</div>

				<!-- Featured Global Cities Explorer -->
				<section aria-label="Featured Global Cities">
					<FeaturedCitiesBar />
				</section>

			{:else if weatherStore.activeTab === 'forecast'}
				<!-- Extended Forecast dedicated tab -->
				<div class="space-y-6">
					<HourlyForecast />
					<DailyForecast />
				</div>

			{:else if weatherStore.activeTab === 'map'}
				<!-- Dedicated Full Weather Radar Map Tab -->
				<div class="space-y-6">
					<WeatherMap />
					<WeatherStats />
				</div>

			{:else if weatherStore.activeTab === 'cities'}
				<!-- Dedicated Cities Tab -->
				<div class="space-y-6">
					<FeaturedCitiesBar />
					<div class="text-center py-6">
						<button
							onclick={() => searchModalOpen = true}
							class="px-6 py-3 rounded-2xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold transition shadow-lg shadow-cyan-500/20"
						>
							{i18n.t('search_worldwide', 'Cari Stasiun Cuaca Dunia')}
						</button>
					</div>
				</div>
			{/if}
		{/if}
	</main>

	<!-- Footer -->
	<footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-slate-400 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3">
		<div class="flex items-center gap-2">
			<span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
			<span>Nuvora Weather Intelligence</span>
			<span class="text-slate-400">• Powered by Meta-CahayaMedia</span>
		</div>
		<div class="flex items-center gap-4 text-[11px]">
			<button onclick={() => weatherStore.activeTab = 'overview'} class="hover:text-white transition">{i18n.t('tab_overview', 'Ringkasan')}</button>
			<button onclick={() => weatherStore.activeTab = 'forecast'} class="hover:text-white transition">{i18n.t('tab_forecast', 'Prakiraan')}</button>
			<button onclick={() => weatherStore.activeTab = 'map'} class="hover:text-white transition">{i18n.t('tab_radar', 'Radar Cuaca')}</button>
			<a href="/admin/login" class="hover:text-cyan-300 transition flex items-center gap-1">
				<span>Admin Portal</span>
			</a>
		</div>
	</footer>

	<!-- Mobile Bottom Bar Navigation -->
	<BottomNavigation onSearchClick={() => searchModalOpen = true} />

	<!-- Global Search & City Autocomplete Modal -->
	<LocationSelector bind:open={searchModalOpen} />
</div>

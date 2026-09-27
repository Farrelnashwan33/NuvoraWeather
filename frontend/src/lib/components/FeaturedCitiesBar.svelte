<script>
	import { onMount } from 'svelte';
	import { fetchFeaturedCities } from '$lib/api.js';
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherIcon from './WeatherIcon.svelte';
	import { Globe2, Sparkles } from 'lucide-svelte';

	let cities = $state([]);
	let loading = $state(true);

	onMount(async () => {
		try {
			cities = await fetchFeaturedCities();
		} catch (e) {
			console.warn('Could not load featured cities:', e);
		} finally {
			loading = false;
		}
	});

	function selectCity(c) {
		weatherStore.loadWeather(c.latitude, c.longitude, c.city_name, c.country);
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}
</script>

<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4">
	<div class="flex items-center justify-between">
		<div class="flex items-center gap-2">
			<Globe2 class="w-4 h-4 text-cyan-400" />
			<h2 class="font-heading font-bold text-base sm:text-lg text-white">
				{i18n.t('featured_cities', 'Kota Unggulan')}
			</h2>
		</div>
		<span class="text-xs text-slate-400 font-mono">{i18n.t('live_stations', 'Stasiun Langsung')}</span>
	</div>

	{#if loading}
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
			{#each Array(5) as _}
				<div class="h-24 rounded-2xl bg-slate-800/40 animate-pulse border border-white/5"></div>
			{/each}
		</div>
	{:else if cities.length > 0}
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
			{#each cities as c}
				{@const isCurrent = weatherStore.data?.location.city === c.city_name}
				<button
					onclick={() => selectCity(c)}
					class="p-3.5 rounded-2xl text-left transition-all duration-200 flex flex-col justify-between gap-2.5 {isCurrent ? 'bg-cyan-500/20 border border-cyan-400/50 shadow-lg shadow-cyan-500/20' : 'bg-slate-900/40 hover:bg-slate-800/60 border border-white/5 hover:border-cyan-500/30'}"
				>
					<div class="flex items-center justify-between">
						<span class="text-xs font-semibold text-white truncate">
							{c.city_name}
						</span>
						<WeatherIcon name={c.icon} size={22} />
					</div>

					<div class="flex items-baseline justify-between pt-1">
						<span class="text-lg font-bold font-mono text-cyan-300">
							{c.temperature !== null ? weatherStore.formatTemp(c.temperature) : '--'}
						</span>
						<span class="text-[10px] text-slate-400 truncate max-w-[65px]">
							{c.country}
						</span>
					</div>
				</button>
			{/each}
		</div>
	{/if}
</div>

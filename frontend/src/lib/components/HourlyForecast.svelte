<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherIcon from './WeatherIcon.svelte';
	import { Clock, Droplets, Wind, ChevronLeft, ChevronRight } from 'lucide-svelte';

	let hourly = $derived(weatherStore.data?.hourly || []);
	let scrollContainer = $state(null);

	function scroll(direction) {
		if (scrollContainer) {
			const amount = direction === 'left' ? -280 : 280;
			scrollContainer.scrollBy({ left: amount, behavior: 'smooth' });
		}
	}
</script>

{#if hourly.length > 0}
	<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4">
		<!-- Header -->
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-2">
				<Clock class="w-4 h-4 text-cyan-400" />
				<h2 class="font-heading font-bold text-base sm:text-lg text-white">
					{i18n.t('hourly_forecast', 'Prakiraan Per Jam')}
				</h2>
				<span class="text-xs text-slate-400 font-mono hidden sm:inline">({i18n.t('hours_24', '24 Jam')})</span>
			</div>

			<!-- Scroll controls for desktop -->
			<div class="hidden sm:flex items-center gap-1.5">
				<button
					onclick={() => scroll('left')}
					class="p-1.5 rounded-xl bg-slate-800/60 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 border border-white/5 transition"
					aria-label="Scroll left"
				>
					<ChevronLeft class="w-4 h-4" />
				</button>
				<button
					onclick={() => scroll('right')}
					class="p-1.5 rounded-xl bg-slate-800/60 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 border border-white/5 transition"
					aria-label="Scroll right"
				>
					<ChevronRight class="w-4 h-4" />
				</button>
			</div>
		</div>

		<!-- Horizontal Scrollable Container -->
		<div
			bind:this={scrollContainer}
			class="flex items-center gap-3 overflow-x-auto pb-2 pt-1 no-scrollbar scroll-smooth snap-x"
		>
			{#each hourly as item, i}
				<div
					class="snap-start shrink-0 w-24 sm:w-28 p-3.5 rounded-2xl flex flex-col items-center justify-between gap-3 text-center transition-all duration-200 {i === 0 ? 'bg-gradient-to-b from-cyan-500/20 to-blue-600/10 border border-cyan-400/30 shadow-lg shadow-cyan-500/10' : 'bg-slate-900/40 hover:bg-slate-800/50 border border-white/5'}"
				>
					<!-- Time -->
					<div class="text-xs font-mono font-medium {i === 0 ? 'text-cyan-300 font-bold' : 'text-slate-300'}">
						{i === 0 ? i18n.t('now', 'Sekarang') : item.display_time}
					</div>

					<!-- Weather Icon -->
					<div class="my-1">
						<WeatherIcon name={item.icon} size={32} />
					</div>

					<!-- Temperature -->
					<div class="text-base sm:text-lg font-bold font-heading text-white">
						{weatherStore.formatTemp(item.temperature)}
					</div>

					<!-- Precipitation Probability -->
					<div class="flex items-center gap-1 text-[11px] {item.precipitation_probability > 30 ? 'text-cyan-400 font-medium' : 'text-slate-500'}">
						<Droplets class="w-3 h-3" />
						<span>{item.precipitation_probability}%</span>
					</div>

					<!-- Wind Speed -->
					<div class="text-[10px] text-slate-400 font-mono">
						{weatherStore.formatSpeed(item.wind_speed)}
					</div>
				</div>
			{/each}
		</div>
	</div>
{/if}

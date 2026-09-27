<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherIcon from './WeatherIcon.svelte';
	import { CalendarDays, Droplets, ArrowUp, ArrowDown } from 'lucide-svelte';

	let daily = $derived(weatherStore.data?.daily || []);

	// Global min and max across the 7 days to scale the temperature bars proportionally
	let minTempOverall = $derived(
		daily.length > 0 ? Math.min(...daily.map(d => d.temp_min)) : 10
	);
	let maxTempOverall = $derived(
		daily.length > 0 ? Math.max(...daily.map(d => d.temp_max)) : 35
	);

	function getBarPosition(min, max) {
		const range = maxTempOverall - minTempOverall || 1;
		const left = Math.max(0, ((min - minTempOverall) / range) * 100);
		const width = Math.min(100 - left, Math.max(12, ((max - min) / range) * 100));
		return { left: `${left}%`, width: `${width}%` };
	}
</script>

{#if daily.length > 0}
	<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4">
		<!-- Section Header -->
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-2">
				<CalendarDays class="w-4 h-4 text-cyan-400" />
				<h2 class="font-heading font-bold text-base sm:text-lg text-white">
					{i18n.t('daily_forecast', 'Prakiraan 7 Hari')}
				</h2>
			</div>
			<span class="text-xs text-slate-400 font-mono">{i18n.t('weekly_outlook', 'Prakiraan Mingguan')}</span>
		</div>

		<!-- Daily List -->
		<div class="space-y-2.5">
			{#each daily as day, idx}
				{@const bar = getBarPosition(day.temp_min, day.temp_max)}
				<div 
					class="p-3.5 sm:p-4 rounded-2xl transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {idx === 0 ? 'bg-cyan-950/30 border border-cyan-500/20' : 'bg-slate-900/40 hover:bg-slate-800/40 border border-white/5'}"
				>
					<!-- Day Label & Date -->
					<div class="flex items-center justify-between sm:justify-start sm:w-36 gap-3">
						<div>
							<div class="font-semibold text-sm text-white {idx === 0 ? 'text-cyan-300' : ''}">
								{i18n.translateDayLabel(day.day_label)}
							</div>
							<div class="text-[11px] text-slate-400">
								{day.full_day}
							</div>
						</div>

						<!-- Rain Probability Badge for Mobile -->
						{#if day.precipitation_probability > 20}
							<div class="sm:hidden flex items-center gap-1 text-xs text-cyan-400">
								<Droplets class="w-3 h-3" />
								<span>{day.precipitation_probability}%</span>
							</div>
						{/if}
					</div>

					<!-- Weather Condition & Icon -->
					<div class="flex items-center gap-3 sm:w-44">
						<WeatherIcon name={day.icon} size={28} />
						<span class="text-xs sm:text-sm text-slate-300 font-medium truncate">
							{i18n.translateCondition(day.condition)}
						</span>
					</div>

					<!-- Precipitation Probability (Desktop) -->
					<div class="hidden sm:flex items-center gap-1.5 w-20 text-xs text-slate-400">
						<Droplets class="w-3.5 h-3.5 {day.precipitation_probability > 30 ? 'text-cyan-400' : 'text-slate-600'}" />
						<span class="{day.precipitation_probability > 30 ? 'text-cyan-300 font-medium' : ''}">
							{day.precipitation_probability}%
						</span>
					</div>

					<!-- Min / Bar / Max Temperature -->
					<div class="flex items-center gap-3 w-full sm:w-56">
						<span class="text-xs font-mono text-slate-400 w-9 text-right">
							{weatherStore.formatTemp(day.temp_min)}
						</span>

						<!-- Proportional Temperature Range Visual Bar -->
						<div class="relative flex-1 h-2 bg-slate-800/80 rounded-full overflow-hidden">
							<div
								class="absolute top-0 bottom-0 rounded-full bg-gradient-to-r from-sky-400 via-cyan-400 to-amber-400 shadow-sm"
								style="left: {bar.left}; width: {bar.width};"
							></div>
						</div>

						<span class="text-xs font-mono font-bold text-white w-9">
							{weatherStore.formatTemp(day.temp_max)}
						</span>
					</div>
				</div>
			{/each}
		</div>
	</div>
{/if}

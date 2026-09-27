<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import WeatherIcon from './WeatherIcon.svelte';
	import { MapPin, ArrowUp, ArrowDown, Sparkles, RefreshCw, Calendar } from 'lucide-svelte';

	let data = $derived(weatherStore.data);
	let current = $derived(data?.current);
	let location = $derived(data?.location);
</script>

{#if current && location}
	<div class="relative overflow-hidden rounded-3xl glass-panel-glow p-6 sm:p-8 md:p-10 transition-all duration-300">
		<!-- Background subtle radial glow -->
		<div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-cyan-500/15 blur-3xl pointer-events-none"></div>

		<div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
			<!-- Left column: Greeting, Location, Condition, High/Low -->
			<div class="space-y-4">
				<!-- Greeting Tag & Date -->
				<div class="flex flex-wrap items-center gap-2 text-xs font-medium">
					<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-300">
						<Sparkles class="w-3.5 h-3.5 text-cyan-400" />
						<span>{i18n.getGreeting()}</span>
					</div>
					<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800/60 border border-white/5 text-slate-300">
						<Calendar class="w-3.5 h-3.5 text-slate-400" />
						<span>{i18n.formatDate()}</span>
					</div>
				</div>

				<!-- City and Country Title -->
				<div>
					<h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold font-heading text-white tracking-tight flex items-center gap-3">
						<span>{location.city}</span>
						<span class="text-lg sm:text-xl font-normal text-cyan-400/90 font-sans">
							{location.country}
						</span>
					</h1>
					<p class="text-sm sm:text-base text-slate-300 mt-1 font-sans">
						{i18n.translateCondition(current.condition)} • {i18n.t('feels_like', 'Terasa seperti')} {weatherStore.formatTemp(current.feels_like)}
					</p>
				</div>

				<!-- High / Low & Feels Like chips -->
				<div class="flex flex-wrap items-center gap-3 pt-2 text-xs sm:text-sm">
					<div class="px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-slate-200">
						{i18n.t('feels_like', 'Terasa seperti')} <span class="font-bold text-white font-mono">{weatherStore.formatTemp(current.feels_like)}</span>
					</div>
					<div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 font-mono">
						<span class="flex items-center text-rose-400">
							<ArrowUp class="w-3.5 h-3.5 mr-0.5" />
							{weatherStore.formatTemp(current.temp_max)}
						</span>
						<span class="text-slate-600">|</span>
						<span class="flex items-center text-sky-400">
							<ArrowDown class="w-3.5 h-3.5 mr-0.5" />
							{weatherStore.formatTemp(current.temp_min)}
						</span>
					</div>
				</div>
			</div>

			<!-- Right Column: Hero Temperature and Dynamic Weather Icon -->
			<div class="flex items-center justify-between sm:justify-end gap-6 md:gap-8">
				<!-- Weather Icon with float animation -->
				<div class="animate-float">
					<WeatherIcon name={current.icon} size={84} />
				</div>

				<!-- Main Large Temperature display -->
				<div class="text-right">
					<div class="text-6xl sm:text-7xl md:text-8xl font-extrabold font-heading tracking-tighter text-white drop-shadow-[0_0_25px_rgba(56,189,248,0.35)]">
						{weatherStore.formatTemp(current.temperature)}
					</div>
					<div class="text-lg sm:text-xl font-semibold text-cyan-300 mt-1 font-heading">
						{i18n.translateCondition(current.condition)}
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}

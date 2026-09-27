<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Droplets, Wind, Gauge, Eye, SunMedium, CloudFog, Navigation } from 'lucide-svelte';

	let current = $derived(weatherStore.data?.current);
</script>

{#if current}
	<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
		<!-- 1. Humidity -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('humidity', 'Kelembaban')}</span>
				<Droplets class="w-4 h-4 text-cyan-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{current.humidity}%
				</div>
				<div class="text-[11px] text-cyan-300 font-medium mt-0.5">
					{i18n.translateStatus(current.humidity_status)}
				</div>
			</div>
			<!-- Progress Mini Bar -->
			<div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
				<div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-full rounded-full transition-all duration-500" style="width: {current.humidity}%"></div>
			</div>
		</div>

		<!-- 2. Wind -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('wind_speed', 'Angin')}</span>
				<Wind class="w-4 h-4 text-sky-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{weatherStore.formatSpeed(current.wind_speed)}
				</div>
				<div class="flex items-center gap-1.5 text-[11px] text-slate-300 font-medium mt-0.5">
					<Navigation 
						class="w-3 h-3 text-cyan-400 transition-transform duration-500" 
						style="transform: rotate({current.wind_direction_deg}deg);" 
					/>
					<span>{current.wind_direction_cardinal} ({current.wind_direction_deg}°)</span>
				</div>
			</div>
			<div class="text-[10px] text-slate-500 truncate">
				{i18n.t('surface_wind', 'Angin permukaan 10m')}
			</div>
		</div>

		<!-- 3. Pressure -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('pressure', 'Tekanan')}</span>
				<Gauge class="w-4 h-4 text-indigo-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{current.pressure} <span class="text-xs font-normal text-slate-400">hPa</span>
				</div>
				<div class="text-[11px] text-indigo-300 font-medium mt-0.5">
					{i18n.translateStatus(current.pressure_status)}
				</div>
			</div>
			<div class="text-[10px] text-slate-500 truncate">
				{i18n.t('barometric_pressure', 'Tekanan barometrik')}
			</div>
		</div>

		<!-- 4. Visibility -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('visibility', 'Jarak Pandang')}</span>
				<Eye class="w-4 h-4 text-emerald-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{current.visibility_km} <span class="text-xs font-normal text-slate-400">km</span>
				</div>
				<div class="text-[11px] text-emerald-300 font-medium mt-0.5">
					{i18n.translateStatus(current.visibility_status)}
				</div>
			</div>
			<div class="text-[10px] text-slate-500 truncate">
				{i18n.t('horizon_clarity', 'Kejernihan cakrawala')}
			</div>
		</div>

		<!-- 5. UV Index -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('uv_index', 'Indeks UV')}</span>
				<SunMedium class="w-4 h-4 text-amber-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{current.uv_index}
				</div>
				<div class="text-[11px] font-semibold mt-0.5 text-amber-400">
					{i18n.translateStatus(current.uv_level.level)}
				</div>
			</div>
			<div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
				<div class="bg-gradient-to-r from-emerald-400 via-amber-400 to-rose-500 h-full rounded-full transition-all duration-500" style="width: {Math.min(100, (current.uv_index / 12) * 100)}%"></div>
			</div>
		</div>

		<!-- 6. Dew Point -->
		<div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between">
			<div class="flex items-center justify-between text-slate-400">
				<span class="text-xs font-medium uppercase tracking-wider">{i18n.t('dew_point', 'Titik Embun')}</span>
				<CloudFog class="w-4 h-4 text-teal-400" />
			</div>
			<div class="my-2">
				<div class="text-2xl sm:text-3xl font-bold font-mono text-white">
					{weatherStore.formatTemp(current.dew_point)}
				</div>
				<div class="text-[11px] text-teal-300 font-medium mt-0.5">
					{i18n.t('condensation_temp', 'Suhu kondensasi')}
				</div>
			</div>
			<div class="text-[10px] text-slate-500 truncate">
				{i18n.t('moisture_saturation', 'Saturasi kelembaban')}
			</div>
		</div>
	</div>
{/if}

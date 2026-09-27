<script>
	import { Activity, Radio, AlertTriangle, CheckCircle, ShieldAlert, Waves, MapPin, Clock, Eye, ExternalLink, ArrowRight } from 'lucide-svelte';
	import { i18n } from '$lib/stores/i18n.svelte.js';

	let { earthquake, onOpenShakemap } = $props();

	let mag = $derived(earthquake?.magnitude ?? 0);
	let isTsunami = $derived(earthquake?.is_tsunami_potential ?? false);

	let magColorClass = $derived(
		mag >= 5.0 ? 'from-rose-500 to-red-600 text-white' :
		mag >= 4.0 ? 'from-amber-500 to-orange-600 text-white' :
		'from-blue-500 to-cyan-600 text-white'
	);
</script>

{#if earthquake}
	<div class="glass-panel-glow rounded-3xl p-6 sm:p-8 border border-white/10 relative overflow-hidden space-y-6">
		<!-- Subtle ambient background glow based on magnitude -->
		<div class="absolute -right-20 -top-20 w-80 h-80 rounded-full {mag >= 5.0 ? 'bg-rose-600/20' : mag >= 4.0 ? 'bg-amber-600/15' : 'bg-cyan-600/15'} blur-[100px] pointer-events-none"></div>

		<!-- Top status bar -->
		<div class="flex flex-wrap items-center justify-between gap-3 relative z-10">
			<div class="flex items-center gap-2.5">
				<span class="relative flex h-3 w-3">
					<span class="animate-ping absolute inline-flex h-full w-full rounded-full {mag >= 5.0 ? 'bg-rose-400' : 'bg-cyan-400'} opacity-75"></span>
					<span class="relative inline-flex rounded-full h-3 w-3 {mag >= 5.0 ? 'bg-rose-500' : 'bg-cyan-500'}"></span>
				</span>
				<span class="text-xs font-heading font-bold text-white uppercase tracking-wider">
					{i18n.t('latest_earthquake_title', 'Gempa Bumi Terbaru')}
				</span>
			</div>

			<div class="flex items-center gap-2">
				<span class="text-[11px] font-mono px-3 py-1 rounded-full bg-slate-900/80 border border-white/10 text-slate-300">
					{earthquake.time_wib}
				</span>
			</div>
		</div>

		<!-- Main Hero Details -->
		<div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center relative z-10">
			<!-- Magnitude Big Badge (4 cols) -->
			<div class="md:col-span-4 flex flex-col items-center justify-center p-6 rounded-3xl bg-slate-950/70 border border-white/10 text-center shadow-xl">
				<span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
					{i18n.t('magnitude', 'Magnitudo')}
				</span>
				<div class="text-6xl sm:text-7xl font-extrabold font-heading tracking-tight bg-gradient-to-br {magColorClass} bg-clip-text text-transparent drop-shadow-[0_0_20px_rgba(244,63,94,0.3)]">
					{earthquake.magnitude.toFixed(1)}
				</div>
				<div class="mt-2 text-xs font-bold px-3 py-0.5 rounded-full {mag >= 5.0 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : mag >= 4.0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30'}">
					STATUS: {i18n.translateStatus(earthquake.level)}
				</div>
			</div>

			<!-- Details & Location (8 cols) -->
			<div class="md:col-span-8 space-y-4">
				<!-- Location title -->
				<div>
					<h3 class="text-lg sm:text-2xl font-bold font-heading text-white leading-snug">
						{earthquake.location}
					</h3>
					<div class="flex flex-wrap items-center gap-3 text-xs text-slate-300 mt-2 font-mono">
						<span class="flex items-center gap-1 text-cyan-300">
							<MapPin class="w-3.5 h-3.5" />
							{earthquake.coordinates}
						</span>
						<span class="text-slate-600">•</span>
						<span class="text-slate-300">{i18n.t('depth', 'Kedalaman')}: <strong class="text-white">{earthquake.depth}</strong></span>
						{#if earthquake.distance_km}
							<span class="text-slate-600">•</span>
							<span class="text-emerald-400 font-semibold">{earthquake.distance_km} {i18n.t('km_from_you', 'km dari Anda')}</span>
						{/if}
					</div>
				</div>

				<!-- Tsunami Status Official Box -->
				<div class="p-3.5 rounded-2xl flex items-start gap-3 {isTsunami ? 'bg-rose-500/20 border border-rose-500/40 text-rose-200' : 'bg-slate-900/80 border border-emerald-500/30 text-slate-200'}">
					{#if isTsunami}
						<AlertTriangle class="w-5 h-5 text-rose-400 shrink-0 mt-0.5 animate-bounce" />
					{:else}
						<CheckCircle class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
					{/if}
					<div>
						<div class="text-xs font-semibold uppercase tracking-wider {isTsunami ? 'text-rose-300' : 'text-emerald-300'}">
							{i18n.t('tsunami_potential', 'Status Potensi Tsunami')}
						</div>
						<div class="text-xs font-medium mt-0.5">
							{isTsunami ? i18n.t('tsunami_yes', 'Berpotensi tsunami') : i18n.t('tsunami_no', 'Tidak berpotensi tsunami')}
						</div>
					</div>
				</div>

				<!-- Felt / Dirasakan status (if available) -->
				{#if earthquake.felt_status}
					<div class="flex items-center gap-2 text-xs text-amber-300 bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20">
						<Waves class="w-4 h-4 shrink-0 text-amber-400" />
						<span>{i18n.t('filter_felt', 'Dirasakan')}: <strong class="text-white">{earthquake.felt_status}</strong></span>
					</div>
				{/if}

				<!-- Shakemap Button if available -->
				{#if earthquake.shakemap_url}
					<div class="pt-1">
						<button
							onclick={() => onOpenShakemap(earthquake.shakemap_url)}
							class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 text-xs font-medium transition"
						>
							<Eye class="w-4 h-4" />
							<span>{i18n.t('view_shakemap', 'Lihat Peta Guncangan (Shakemap)')}</span>
							<ArrowRight class="w-3.5 h-3.5" />
						</button>
					</div>
				{/if}
			</div>
		</div>

		<!-- Attribution Footer -->
		<div class="pt-3 border-t border-white/5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
			<span>{i18n.t('data_source', 'Sumber Data')}: <strong>{earthquake.source}</strong></span>
			<span>{i18n.t('updated', 'Pembaruan')}: {earthquake.display_time}</span>
		</div>
	</div>
{/if}

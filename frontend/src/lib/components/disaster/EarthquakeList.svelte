<script>
	import { Activity, RefreshCw, Filter, Waves, AlertTriangle, CheckCircle, MapPin, Clock } from 'lucide-svelte';
	import { i18n } from '$lib/stores/i18n.svelte.js';

	let { earthquakes = [], onRefresh, loading = false } = $props();

	let activeFilter = $state('all'); // 'all' | 'm3' | 'm4' | 'm5' | 'felt'

	let filteredList = $derived(
		earthquakes.filter(eq => {
			if (activeFilter === 'm5') return eq.magnitude >= 5.0;
			if (activeFilter === 'm4') return eq.magnitude >= 4.0;
			if (activeFilter === 'm3') return eq.magnitude >= 3.0;
			if (activeFilter === 'felt') return !!eq.felt_status && eq.felt_status !== '-';
			return true;
		})
	);
</script>

<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4">
	<!-- Top Bar -->
	<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
		<div class="flex items-center gap-2">
			<Activity class="w-4 h-4 text-cyan-400" />
			<h2 class="font-heading font-bold text-base sm:text-lg text-white">
				{i18n.t('recent_earthquakes_list', 'Daftar Aktivitas Gempa Terkini')}
			</h2>
			<span class="text-xs text-slate-400 font-mono hidden sm:inline">
				({filteredList.length} {i18n.t('events', 'Kejadian')})
			</span>
		</div>

		<!-- Action controls (Filter + Refresh) -->
		<div class="flex flex-wrap items-center gap-2">
			<!-- Filters -->
			<div class="flex items-center bg-slate-900/80 rounded-xl p-0.5 border border-white/10 text-xs">
				<button
					onclick={() => activeFilter = 'all'}
					class="px-2.5 py-1 rounded-lg transition {activeFilter === 'all' ? 'bg-cyan-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('filter_all', 'Semua')}
				</button>
				<button
					onclick={() => activeFilter = 'm3'}
					class="px-2.5 py-1 rounded-lg transition {activeFilter === 'm3' ? 'bg-cyan-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					M 3+
				</button>
				<button
					onclick={() => activeFilter = 'm4'}
					class="px-2.5 py-1 rounded-lg transition {activeFilter === 'm4' ? 'bg-cyan-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					M 4+
				</button>
				<button
					onclick={() => activeFilter = 'm5'}
					class="px-2.5 py-1 rounded-lg transition {activeFilter === 'm5' ? 'bg-cyan-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					M 5+
				</button>
				<button
					onclick={() => activeFilter = 'felt'}
					class="px-2.5 py-1 rounded-lg transition {activeFilter === 'felt' ? 'bg-cyan-500 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white'}"
				>
					{i18n.t('filter_felt', 'Dirasakan')}
				</button>
			</div>

			<!-- Refresh Button -->
			<button
				onclick={onRefresh}
				disabled={loading}
				class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-white/10 transition disabled:opacity-50"
			>
				<RefreshCw class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" />
				<span>{i18n.t('refresh_data', 'Refresh Data')}</span>
			</button>
		</div>
	</div>

	<!-- Content: Desktop Table & Mobile Cards -->
	{#if filteredList.length > 0}
		<!-- Desktop Table -->
		<div class="hidden md:block overflow-x-auto">
			<table class="w-full text-left text-xs">
				<thead class="text-slate-400 border-b border-white/10 uppercase tracking-wider font-mono">
					<tr>
						<th class="py-3 px-3">{i18n.t('time_wib', 'Waktu')}</th>
						<th class="py-3 px-3">{i18n.t('magnitude', 'Magnitudo')}</th>
						<th class="py-3 px-3">{i18n.t('depth', 'Kedalaman')}</th>
						<th class="py-3 px-3">{i18n.t('region_epicenter', 'Wilayah / Episentrum')}</th>
						<th class="py-3 px-3">{i18n.t('distance', 'Jarak')}</th>
						<th class="py-3 px-3 text-right">{i18n.t('potential_status', 'Status Potensi')}</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-white/5">
					{#each filteredList as eq}
						{@const mag = eq.magnitude}
						<tr class="hover:bg-white/5 transition">
							<!-- Time -->
							<td class="py-3 px-3 font-mono text-slate-300 whitespace-nowrap">
								<div>{eq.date}</div>
								<div class="text-[11px] text-slate-500">{eq.time_wib}</div>
							</td>

							<!-- Magnitude -->
							<td class="py-3 px-3">
								<span class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs {mag >= 5.0 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : mag >= 4.0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30'}">
									M {mag.toFixed(1)}
								</span>
							</td>

							<!-- Depth -->
							<td class="py-3 px-3 font-mono text-slate-300 whitespace-nowrap">
								{eq.depth}
							</td>

							<!-- Location -->
							<td class="py-3 px-3 max-w-xs">
								<div class="font-medium text-white leading-snug">{eq.location}</div>
								{#if eq.felt_status}
									<div class="text-[11px] text-amber-300 flex items-center gap-1 mt-0.5">
										<Waves class="w-3 h-3" />
										<span>{i18n.t('filter_felt', 'Dirasakan')}: {eq.felt_status}</span>
									</div>
								{/if}
							</td>

							<!-- Distance -->
							<td class="py-3 px-3 font-mono text-emerald-400 whitespace-nowrap">
								{eq.distance_km ? `${eq.distance_km} km` : '--'}
							</td>

							<!-- Status -->
							<td class="py-3 px-3 text-right whitespace-nowrap">
								<span class="px-2.5 py-1 rounded-full text-[10px] font-medium {eq.is_tsunami_potential ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-slate-800 text-slate-300 border border-white/10'}">
									{eq.is_tsunami_potential ? i18n.t('tsunami_yes', 'Berpotensi tsunami') : i18n.t('tsunami_no', 'Tidak berpotensi tsunami')}
								</span>
							</td>
						</tr>
					{/each}
				</tbody>
			</table>
		</div>

		<!-- Mobile Card Layout -->
		<div class="md:hidden space-y-3">
			{#each filteredList as eq}
				{@const mag = eq.magnitude}
				<div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 space-y-2.5">
					<div class="flex items-center justify-between">
						<div class="flex items-center gap-2">
							<span class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs {mag >= 5.0 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : mag >= 4.0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30'}">
								M {mag.toFixed(1)}
							</span>
							<span class="text-xs font-mono text-slate-400">{i18n.t('depth', 'Kedalaman')}: {eq.depth}</span>
						</div>
						<span class="text-[11px] font-mono text-slate-400">{eq.time_wib}</span>
					</div>

					<div class="text-sm font-semibold text-white leading-snug">
						{eq.location}
					</div>

					{#if eq.felt_status}
						<div class="text-xs text-amber-300 flex items-center gap-1.5 bg-amber-500/10 p-2 rounded-xl">
							<Waves class="w-3.5 h-3.5 shrink-0" />
							<span>{i18n.t('filter_felt', 'Dirasakan')}: {eq.felt_status}</span>
						</div>
					{/if}

					<div class="pt-2 border-t border-white/5 flex items-center justify-between text-xs">
						{#if eq.distance_km}
							<span class="text-emerald-400 font-mono">{eq.distance_km} {i18n.t('km_from_you', 'km dari Anda')}</span>
						{:else}
							<span class="text-slate-500 font-mono">{eq.coordinates}</span>
						{/if}

						<span class="text-[10px] text-slate-300 px-2 py-0.5 rounded-full bg-slate-800 border border-white/5">
							{eq.is_tsunami_potential ? i18n.t('tsunami_yes', 'Berpotensi tsunami') : i18n.t('tsunami_no', 'Tidak berpotensi tsunami')}
						</span>
					</div>
				</div>
			{/each}
		</div>
	{:else}
		<div class="text-center py-12 text-slate-400">
			<Activity class="w-8 h-8 text-slate-500 mx-auto mb-2 opacity-50" />
			<p class="text-sm">Belum ada data monitoring yang sesuai filter.</p>
		</div>
	{/if}
</div>

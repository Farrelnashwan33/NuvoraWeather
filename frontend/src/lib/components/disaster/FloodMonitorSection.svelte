<script>
	import { Droplets, ShieldCheck, AlertTriangle, AlertCircle, Compass, Search, Filter, Clock, MapPin, ExternalLink, RefreshCw } from 'lucide-svelte';
	import { i18n } from '$lib/stores/i18n.svelte.js';

	let { stations = [], userCoords = null, onDetectLocation, onRefresh, loading = false } = $props();

	let selectedProvince = $state('');
	let searchQuery = $state('');

	let provinces = [
		'Semua Provinsi',
		'DKI Jakarta',
		'Jawa Barat',
		'Jawa Tengah',
		'Jawa Timur',
		'Sumatera',
		'Kalimantan',
		'Sulawesi'
	];

	let filteredStations = $derived(
		stations.filter(st => {
			if (selectedProvince && selectedProvince !== 'Semua Provinsi') {
				if (!st.province.toLowerCase().includes(selectedProvince.toLowerCase()) && 
					!st.region.toLowerCase().includes(selectedProvince.toLowerCase())) {
					return false;
				}
			}
			if (searchQuery.trim()) {
				const q = searchQuery.toLowerCase();
				const matchName = st.name.toLowerCase().includes(q);
				const matchRiver = st.river.toLowerCase().includes(q);
				const matchRegion = st.region.toLowerCase().includes(q);
				if (!matchName && !matchRiver && !matchRegion) return false;
			}
			return true;
		})
	);
</script>

<div class="glass-panel rounded-3xl p-5 sm:p-8 space-y-6">
	<!-- Header -->
	<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/5">
		<div>
			<div class="flex items-center gap-2">
				<Droplets class="w-5 h-5 text-cyan-400" />
				<h2 class="font-heading font-bold text-xl sm:text-2xl text-white">
					{i18n.t('flood_monitor_section_title', 'Pemantauan Ketinggian Air & Banjir')}
				</h2>
			</div>
			<p class="text-xs text-slate-400 mt-1 font-sans">
				{i18n.t('water_sensor_desc', 'Data sensor stasiun hidrologi dan balai wilayah sungai')}
			</p>
		</div>

		<!-- Action controls -->
		<div class="flex flex-wrap items-center gap-2">
			<button
				onclick={onDetectLocation}
				class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 text-xs font-medium transition"
			>
				<Compass class="w-3.5 h-3.5 animate-spin-slow" />
				<span>{i18n.t('use_my_location', 'Gunakan Lokasi Saya')}</span>
			</button>

			<button
				onclick={onRefresh}
				disabled={loading}
				class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-white/10 transition disabled:opacity-50"
			>
				<RefreshCw class="w-3.5 h-3.5 {loading ? 'animate-spin' : ''}" />
				<span>{i18n.t('refresh', 'Refresh')}</span>
			</button>
		</div>
	</div>

	<!-- Filter & Search Bar -->
	<div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
		<!-- Search Input (7 cols) -->
		<div class="sm:col-span-7 relative">
			<Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
			<input
				type="text"
				bind:value={searchQuery}
				placeholder={i18n.t('search_stations_placeholder', 'Cari nama pos pantau, pintu air, atau sungai...')}
				class="w-full pl-10 pr-4 py-2 rounded-xl glass-input text-xs text-white placeholder-slate-400"
			/>
		</div>

		<!-- Province Dropdown (5 cols) -->
		<div class="sm:col-span-5 relative">
			<select
				bind:value={selectedProvince}
				class="w-full px-3 py-2 rounded-xl glass-input text-xs text-white bg-slate-900 cursor-pointer focus:outline-none"
			>
				{#each provinces as prov}
					<option value={prov === 'Semua Provinsi' ? '' : prov} class="bg-slate-900 text-white">
						{prov}
					</option>
				{/each}
			</select>
		</div>
	</div>

	<!-- Monitoring Station Grid -->
	{#if filteredStations.length > 0}
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
			{#each filteredStations as station}
				{@const isBahaya = station.status === 'BAHAYA'}
				{@const isSiaga = station.status === 'SIAGA'}
				{@const isWaspada = station.status === 'WASPADA'}
				<div 
					class="p-5 rounded-3xl transition-all duration-200 flex flex-col justify-between gap-4 border {isBahaya ? 'bg-rose-950/30 border-rose-500/40 shadow-lg shadow-rose-500/10' : isSiaga ? 'bg-orange-950/30 border-orange-500/40 shadow-lg shadow-orange-500/10' : isWaspada ? 'bg-amber-950/20 border-amber-500/30' : 'bg-slate-900/50 hover:bg-slate-800/50 border-white/5'}"
				>
					<!-- Top Title & Badge -->
					<div class="space-y-1.5">
						<div class="flex items-start justify-between gap-2">
							<span class="px-2.5 py-1 rounded-full text-[10px] font-bold font-mono uppercase tracking-wider border {station.status_badge_class}">
								{station.status_label}
							</span>

							{#if station.distance_formatted}
								<span class="text-[11px] text-emerald-400 font-mono font-medium whitespace-nowrap">
									{station.distance_formatted}
								</span>
							{/if}
						</div>

						<h3 class="font-bold text-sm text-white leading-snug">
							{station.name}
						</h3>
						<div class="text-xs text-slate-400 font-sans">
							{station.river} • {station.region}, {station.province}
						</div>
					</div>

					<!-- Current Water Level Hero Metric -->
					<div class="p-3.5 rounded-2xl bg-slate-950/60 border border-white/5 flex items-center justify-between">
						<div>
							<div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
								{i18n.t('current_water_level', 'Ketinggian Air Saat Ini')}
							</div>
							<div class="text-2xl font-extrabold font-mono text-white mt-0.5">
								{station.water_level_formatted}
							</div>
						</div>

						<div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
							<Droplets class="w-5 h-5" />
						</div>
					</div>

					<!-- Official Threshold Guidelines -->
					<div class="space-y-1 text-[10px] font-mono text-slate-400 bg-slate-950/30 p-2.5 rounded-xl border border-white/5">
						<div class="font-semibold text-slate-300 mb-1">{i18n.t('official_thresholds', 'Ambang Batas Resmi:')}</div>
						<div class="flex justify-between">
							<span class="text-emerald-400">{i18n.t('status_normal', 'Normal')}: {station.thresholds?.normal ?? '-'}</span>
							<span class="text-amber-400">{i18n.t('status_waspada', 'Waspada')}: {station.thresholds?.waspada ?? '-'}</span>
						</div>
						<div class="flex justify-between">
							<span class="text-orange-400">{i18n.t('status_siaga', 'Siaga')}: {station.thresholds?.siaga ?? '-'}</span>
							<span class="text-rose-400 font-bold">{i18n.t('status_bahaya', 'Bahaya')}: {station.thresholds?.bahaya ?? '-'}</span>
						</div>
					</div>

					<!-- Footer Source & Timestamp -->
					<div class="pt-2 border-t border-white/5 flex items-center justify-between text-[10px] text-slate-400 font-mono">
						<span class="truncate max-w-[180px]" title={station.agency}>{station.agency}</span>
						<span>{station.updated_at}</span>
					</div>
				</div>
			{/each}
		</div>
	{:else}
		<div class="text-center py-12 text-slate-400">
			<Droplets class="w-8 h-8 text-slate-500 mx-auto mb-2 opacity-50" />
			<p class="text-sm">{i18n.t('no_stations_match', 'Belum ada pos pantau yang sesuai dengan filter lokasi.')}</p>
		</div>
	{/if}
</div>

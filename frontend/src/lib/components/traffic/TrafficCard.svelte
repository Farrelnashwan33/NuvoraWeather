<script>
	import { Car, Clock, Gauge, Navigation, AlertTriangle, CheckCircle, Activity, ChevronRight } from 'lucide-svelte';

	let {
		road,
		isSelected = false,
		onClick = null
	} = $props();

	const STATUS_CONFIG = {
		lancar: {
			label: 'Lancar',
			bg: 'bg-emerald-500/15',
			text: 'text-emerald-400',
			border: 'border-emerald-500/30',
			badge: 'bg-emerald-500',
			icon: CheckCircle
		},
		ramai: {
			label: 'Ramai Lancar',
			bg: 'bg-amber-500/15',
			text: 'text-amber-400',
			border: 'border-amber-500/30',
			badge: 'bg-amber-500',
			icon: Activity
		},
		padat: {
			label: 'Padat Merayap',
			bg: 'bg-orange-500/15',
			text: 'text-orange-400',
			border: 'border-orange-500/30',
			badge: 'bg-orange-500',
			icon: AlertTriangle
		},
		macet: {
			label: 'Macet Total',
			bg: 'bg-rose-500/15',
			text: 'text-rose-400',
			border: 'border-rose-500/30',
			badge: 'bg-rose-500',
			icon: AlertTriangle
		},
		unknown: {
			label: 'Tidak Tersedia',
			bg: 'bg-slate-500/15',
			text: 'text-slate-400',
			border: 'border-slate-500/30',
			badge: 'bg-slate-500',
			icon: Activity
		}
	};

	let cfg = $derived(STATUS_CONFIG[road?.status] || STATUS_CONFIG.unknown);
	let StatusIcon = $derived(cfg.icon);
</script>

<button
	onclick={() => { if (onClick) onClick(road); }}
	class="w-full text-left p-4 rounded-2xl glass-panel transition-all duration-200 group relative border {isSelected ? 'border-cyan-400/60 bg-cyan-950/30 shadow-lg shadow-cyan-500/10' : 'border-white/10 hover:border-white/20 hover:bg-white/[0.04]'}"
>
	<div class="flex items-start justify-between gap-3">
		<!-- Road info -->
		<div class="flex-1 min-w-0">
			<div class="flex items-center gap-2 mb-1">
				<span class="w-2 h-2 rounded-full {cfg.badge} {road.status === 'macet' ? 'animate-ping' : ''}"></span>
				<h3 class="font-heading font-semibold text-sm text-white group-hover:text-cyan-300 transition-colors truncate">
					{road.road_name || road.name}
				</h3>
			</div>
			<div class="text-xs text-slate-400 truncate">
				{road.district ? `${road.district}, ` : ''}{road.city}, {road.province}
			</div>
		</div>

		<!-- Status badge -->
		<div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-semibold {cfg.bg} {cfg.text} {cfg.border} border shrink-0">
			<StatusIcon class="w-3.5 h-3.5" />
			<span>{cfg.label}</span>
		</div>
	</div>

	<!-- Telemetry Row -->
	<div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-white/5 text-xs">
		<div class="flex flex-col">
			<div class="text-[10px] text-slate-400 flex items-center gap-1">
				<Gauge class="w-3 h-3 text-cyan-400" />
				<span>Kecepatan</span>
			</div>
			<div class="font-mono font-bold text-slate-200 mt-0.5">
				{road.speed_kmh} <span class="text-[10px] font-normal text-slate-400">km/j</span>
			</div>
		</div>

		<div class="flex flex-col">
			<div class="text-[10px] text-slate-400 flex items-center gap-1">
				<Clock class="w-3 h-3 text-cyan-400" />
				<span>Est. Waktu</span>
			</div>
			<div class="font-mono font-bold text-slate-200 mt-0.5">
				~{road.delay_minutes || 5} <span class="text-[10px] font-normal text-slate-400">mnt</span>
			</div>
		</div>

		<div class="flex flex-col">
			<div class="text-[10px] text-slate-400 flex items-center gap-1">
				<Navigation class="w-3 h-3 text-cyan-400" />
				<span>Kondisi</span>
			</div>
			<div class="font-semibold text-xs {cfg.text} truncate mt-0.5">
				{road.status ? road.status.toUpperCase() : '-'}
			</div>
		</div>
	</div>

	<!-- Updated timestamp -->
	<div class="flex items-center justify-between mt-2 pt-2 text-[10px] text-slate-500">
		<span>Sumber: RTMC / Dishub & Sensor Lalu Lintas</span>
		<div class="flex items-center gap-1 text-cyan-400 group-hover:translate-x-0.5 transition-transform">
			<span>Lihat di Peta</span>
			<ChevronRight class="w-3 h-3" />
		</div>
	</div>
</button>

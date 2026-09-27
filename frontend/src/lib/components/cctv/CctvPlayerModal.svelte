<script>
	import { onDestroy } from 'svelte';
	import {
		X,
		Video,
		CheckCircle,
		AlertTriangle,
		ExternalLink,
		MapPin,
		Clock,
		ShieldCheck,
		Maximize2,
		Radio
	} from 'lucide-svelte';

	let {
		camera = null,
		onClose = null
	} = $props();

	let videoRef = $state(null);
	let isPlaying = $state(false);
	let streamError = $state(false);

	function handleClose() {
		if (videoRef) {
			try {
				videoRef.pause();
				videoRef.src = '';
			} catch (e) {}
		}
		if (onClose) onClose();
	}

	onDestroy(() => {
		if (videoRef) {
			try {
				videoRef.pause();
				videoRef.src = '';
			} catch (e) {}
		}
	});
</script>

{#if camera}
	<!-- Backdrop -->
	<div
		class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xl animate-fade-in"
		onclick={(e) => { if (e.target === e.currentTarget) handleClose(); }}
		onkeydown={(e) => { if (e.key === 'Escape') handleClose(); }}
		role="dialog"
		aria-modal="true"
		tabindex="0"
	>
		<!-- Modal Box -->
		<div class="relative w-full max-w-2xl bg-slate-900/95 border border-white/15 rounded-3xl shadow-2xl shadow-black/80 overflow-hidden flex flex-col max-h-[90vh]">
			<!-- Header -->
			<div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-slate-950/50">
				<div class="flex items-center gap-2.5">
					<div class="p-2 rounded-xl bg-cyan-500/15 border border-cyan-500/30 text-cyan-400">
						<Video class="w-4 h-4" />
					</div>
					<div>
						<h2 class="font-heading font-bold text-sm sm:text-base text-white truncate max-w-[280px] sm:max-w-md">
							{camera.name}
						</h2>
						<div class="text-[11px] text-slate-400 flex items-center gap-1.5">
							<MapPin class="w-3 h-3 text-cyan-400" />
							<span>{camera.road ? `${camera.road}, ` : ''}{camera.city}, {camera.province}</span>
						</div>
					</div>
				</div>

				<button
					onclick={handleClose}
					class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition"
					aria-label="Tutup"
				>
					<X class="w-5 h-5" />
				</button>
			</div>

			<!-- Video Stream Player Container -->
			<div class="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden">
				{#if camera.status === 'online' && camera.stream_url && !streamError}
					<!-- Live Video / Stream -->
					<video
						bind:this={videoRef}
						src={camera.stream_url}
						poster={camera.thumbnail_url || undefined}
						autoplay
						playsinline
						muted
						controls
						onerror={() => { streamError = true; }}
						class="w-full h-full object-contain"
					></video>

					<!-- Live Badge Indicator -->
					<div class="absolute top-3 left-3 z-10 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-600/90 text-white font-bold text-[10px] tracking-wider uppercase backdrop-blur-md shadow-lg shadow-rose-600/30">
						<span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
						<Radio class="w-3 h-3" />
						<span>LIVE ATCS</span>
					</div>
				{:else if camera.thumbnail_url && !streamError}
					<!-- Live Snapshot Image Mode (for servers providing refreshed snapshot JPGs) -->
					<img
						src={camera.thumbnail_url}
						alt={camera.name}
						onerror={() => { streamError = true; }}
						class="w-full h-full object-contain"
					/>
					<div class="absolute top-3 left-3 z-10 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cyan-600/90 text-white font-bold text-[10px] tracking-wider uppercase backdrop-blur-md">
						<Clock class="w-3 h-3" />
						<span>SNAPSHOT</span>
					</div>
				{:else}
					<!-- Offline / Stream Unavailable State -->
					<div class="flex flex-col items-center justify-center p-6 text-center space-y-3">
						<div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-white/10 flex items-center justify-center text-slate-400">
							<AlertTriangle class="w-6 h-6 text-amber-400" />
						</div>
						<div class="font-heading font-semibold text-sm text-white">
							Feed CCTV publik tidak tersedia
						</div>
						<p class="text-xs text-slate-400 max-w-sm">
							Kamera saat ini sedang offline, dalam perbaikan pemeliharaan jaringan, atau dibatasi oleh server dinas perhubungan setempat.
						</p>
					</div>
				{/if}
			</div>

			<!-- Metadata & Source Info -->
			<div class="p-5 sm:p-6 space-y-4 bg-slate-900/80 overflow-y-auto">
				<!-- Status & Timestamps -->
				<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
					<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5 space-y-1">
						<div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Status Kamera</div>
						<div class="flex items-center gap-1.5 font-bold text-xs">
							{#if camera.status === 'online'}
								<CheckCircle class="w-4 h-4 text-emerald-400" />
								<span class="text-emerald-400">ONLINE</span>
							{:else}
								<AlertTriangle class="w-4 h-4 text-rose-400" />
								<span class="text-rose-400">OFFLINE</span>
							{/if}
						</div>
					</div>

					<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5 space-y-1">
						<div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Sumber Resmi</div>
						<div class="flex items-center gap-1 text-cyan-300 font-semibold text-xs truncate">
							<ShieldCheck class="w-3.5 h-3.5 text-cyan-400 shrink-0" />
							<span class="truncate">{camera.source_name || 'Dishub ATCS'}</span>
						</div>
					</div>

					<div class="p-3 rounded-2xl bg-white/[0.03] border border-white/5 space-y-1 col-span-2 sm:col-span-1">
						<div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Update Terakhir</div>
						<div class="text-slate-300 font-mono text-xs">
							{camera.last_checked_at ? new Date(camera.last_checked_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 'Real-time'} WIB
						</div>
					</div>
				</div>

				<!-- Official Portal Source Link -->
				{#if camera.source_url}
					<div class="flex items-center justify-between p-3 rounded-2xl bg-cyan-950/30 border border-cyan-500/20 text-xs">
						<div class="text-slate-300">
							Buka feed langsung di portal resmi {camera.source_name}
						</div>
						<a
							href={camera.source_url}
							target="_blank"
							rel="noopener noreferrer"
							class="flex items-center gap-1 px-3 py-1.5 rounded-xl bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 font-semibold transition"
						>
							<span>Kunjungi Portal</span>
							<ExternalLink class="w-3.5 h-3.5" />
						</a>
					</div>
				{/if}
			</div>
		</div>
	</div>
{/if}

<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { AlertTriangle, RefreshCw, Compass } from 'lucide-svelte';

	let { message = 'Unable to connect to weather data stream', onRetry } = $props();
</script>

<div class="glass-panel rounded-3xl p-8 sm:p-12 text-center max-w-lg mx-auto my-12 border border-rose-500/20 shadow-2xl">
	<div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center mx-auto mb-4 text-rose-400">
		<AlertTriangle class="w-8 h-8 animate-bounce" />
	</div>

	<h2 class="text-xl font-heading font-bold text-white">
		Weather Station Offline
	</h2>
	<p class="text-sm text-slate-300 mt-2 font-sans">
		{message}
	</p>

	<div class="flex flex-wrap items-center justify-center gap-3 mt-6">
		<button
			onclick={onRetry}
			class="flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-semibold text-sm transition shadow-lg shadow-cyan-500/20"
		>
			<RefreshCw class="w-4 h-4" />
			<span>Retry Connection</span>
		</button>

		<button
			onclick={() => weatherStore.loadWeather(-6.9175, 107.6191, 'Bandung', 'Indonesia')}
			class="flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-sm border border-white/10 transition"
		>
			<Compass class="w-4 h-4 text-cyan-400" />
			<span>Load Default (Bandung)</span>
		</button>
	</div>
</div>

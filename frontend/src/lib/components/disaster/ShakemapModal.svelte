<script>
	import { X, ExternalLink, Download } from 'lucide-svelte';
	import { i18n } from '$lib/stores/i18n.svelte.js';

	let { url = '', open = $bindable(false) } = $props();
</script>

{#if open && url}
	<div 
		class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in"
		role="dialog"
		aria-modal="true"
	>
		<div class="relative w-full max-w-2xl glass-panel-glow rounded-3xl p-6 border border-cyan-500/30 shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
			<!-- Header -->
			<div class="flex items-center justify-between pb-3 border-b border-white/10 shrink-0">
				<div>
					<h3 class="font-heading font-bold text-base text-white">
						{i18n.t('shakemap_title', 'Peta Estimasi Guncangan (Shakemap)')}
					</h3>
					<p class="text-xs text-slate-400">
						{i18n.t('shakemap_desc', 'Visualisasi intensitas guncangan gempa resmi')}
					</p>
				</div>
				<button 
					onclick={() => open = false}
					class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition"
				>
					<X class="w-5 h-5" />
				</button>
			</div>

			<!-- Image container -->
			<div class="flex-1 overflow-auto rounded-2xl bg-slate-950 border border-white/10 flex items-center justify-center p-2">
				<img 
					src={url} 
					alt="BMKG Shakemap Guncangan"
					class="max-w-full max-h-[65vh] object-contain rounded-xl shadow-lg"
					loading="lazy"
				/>
			</div>

			<!-- Footer -->
			<div class="flex items-center justify-between pt-2 border-t border-white/5 text-xs text-slate-400 shrink-0">
				<span>{i18n.t('data_source', 'Sumber Data')}: <strong>BMKG Open Data (TEWS)</strong></span>
				<a 
					href={url} 
					target="_blank" 
					class="flex items-center gap-1.5 text-cyan-300 hover:text-cyan-100 transition font-medium"
				>
					<span>{i18n.t('open_full_size', 'Buka Ukuran Penuh')}</span>
					<ExternalLink class="w-3.5 h-3.5" />
				</a>
			</div>
		</div>
	</div>
{/if}

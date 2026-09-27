<script>
	/**
	 * High-tech glassmorphism skeleton loader
	 */
</script>

<div class="w-full space-y-6 animate-pulse">
	<!-- Hero Card Skeleton -->
	<div class="h-64 sm:h-72 rounded-3xl bg-slate-900/60 border border-white/5 p-6 sm:p-8 flex flex-col justify-between">
		<div class="flex justify-between items-start">
			<div class="space-y-3">
				<div class="w-28 h-6 bg-slate-800 rounded-full"></div>
				<div class="w-56 h-10 bg-slate-800 rounded-xl"></div>
				<div class="w-40 h-4 bg-slate-800/60 rounded-lg"></div>
			</div>
			<div class="w-20 h-20 bg-slate-800 rounded-2xl"></div>
		</div>
		<div class="flex gap-3">
			<div class="w-32 h-8 bg-slate-800 rounded-xl"></div>
			<div class="w-24 h-8 bg-slate-800 rounded-xl"></div>
		</div>
	</div>

	<!-- Stats Grid Skeleton -->
	<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
		{#each Array(6) as _}
			<div class="h-32 rounded-2xl bg-slate-900/40 border border-white/5 p-4 flex flex-col justify-between">
				<div class="w-16 h-3 bg-slate-800 rounded"></div>
				<div class="w-20 h-7 bg-slate-800 rounded"></div>
				<div class="w-full h-1.5 bg-slate-800 rounded-full"></div>
			</div>
		{/each}
	</div>

	<!-- Hourly Carousel Skeleton -->
	<div class="h-44 rounded-3xl bg-slate-900/40 border border-white/5 p-5 space-y-4">
		<div class="w-36 h-4 bg-slate-800 rounded"></div>
		<div class="flex gap-3 overflow-hidden">
			{#each Array(8) as _}
				<div class="w-24 h-24 shrink-0 rounded-2xl bg-slate-800/40"></div>
			{/each}
		</div>
	</div>
</div>

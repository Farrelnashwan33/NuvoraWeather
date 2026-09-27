<script>
	import { weatherStore } from '$lib/stores/weatherState.svelte.js';
	import { i18n } from '$lib/stores/i18n.svelte.js';
	import { Sunrise, Sunset, Sun, Moon, Clock } from 'lucide-svelte';

	let sun = $derived(weatherStore.data?.sun);
	let current = $derived(weatherStore.data?.current);

	// Arc gauge calculations
	let pct = $derived(sun?.progress_percent ?? 50);
	// Angle between 180 (left/sunrise) and 360/0 (right/sunset) along top semicircle
	let angleRad = $derived(Math.PI * (1 - pct / 100));
	let sunX = $derived(150 + 110 * Math.cos(angleRad));
	let sunY = $derived(130 - 110 * Math.sin(angleRad));
</script>

{#if sun}
	<div class="glass-panel rounded-3xl p-5 sm:p-6 space-y-4 flex flex-col justify-between">
		<!-- Header -->
		<div class="flex items-center justify-between">
			<div class="flex items-center gap-2">
				<Sunrise class="w-4 h-4 text-amber-400" />
				<h2 class="font-heading font-bold text-base sm:text-lg text-white">
					{i18n.t('sun_daylight', 'Matahari & Siang')}
				</h2>
			</div>
			<span class="text-xs text-amber-300/90 font-medium px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20">
				{sun.status_text}
			</span>
		</div>

		<!-- Daylight Celestial Arc Graphic -->
		<div class="relative flex flex-col items-center justify-center my-2">
			<svg viewBox="0 0 300 150" class="w-full max-w-[280px] overflow-visible">
				<defs>
					<!-- Gradient for daylight path -->
					<linearGradient id="sunArcGrad" x1="0%" y1="0%" x2="100%" y2="0%">
						<stop offset="0%" stop-color="#fbbf24" stop-opacity="0.3"/>
						<stop offset="50%" stop-color="#38bdf8" stop-opacity="0.9"/>
						<stop offset="100%" stop-color="#f43f5e" stop-opacity="0.3"/>
					</linearGradient>

					<filter id="sunGlow" x="-50%" y="-50%" width="200%" height="200%">
						<feGaussianBlur in="SourceGraphic" stdDeviation="4"/>
					</filter>
				</defs>

				<!-- Background Dotted Semicircle Track -->
				<path
					d="M 40,130 A 110,110 0 0,1 260,130"
					fill="none"
					stroke="rgba(255, 255, 255, 0.12)"
					stroke-width="3"
					stroke-dasharray="4 6"
				/>

				<!-- Horizon Base line -->
				<line
					x1="20" y1="130" x2="280" y2="130"
					stroke="rgba(255, 255, 255, 0.15)"
					stroke-width="1.5"
				/>

				<!-- Glowing Active Progress Arc -->
				{#if sun.is_sun_up && pct > 0}
					<path
						d="M 40,130 A 110,110 0 0,1 {sunX},{sunY}"
						fill="none"
						stroke="url(#sunArcGrad)"
						stroke-width="4"
						stroke-linecap="round"
					/>
				{/if}

				<!-- Sun position indicator orb -->
				{#if sun.is_sun_up}
					<!-- Ambient sun glow -->
					<circle
						cx={sunX}
						cy={sunY}
						r="12"
						fill="#fbbf24"
						filter="url(#sunGlow)"
						opacity="0.6"
					/>
					<circle
						cx={sunX}
						cy={sunY}
						r="6"
						fill="#ffffff"
						stroke="#fbbf24"
						stroke-width="3"
					/>
				{:else}
					<!-- Night Moon Indicator positioned below horizon -->
					<circle
						cx="150"
						cy="130"
						r="6"
						fill="#818cf8"
						stroke="#c7d2fe"
						stroke-width="2"
					/>
				{/if}
			</svg>

			<!-- Daylight Duration Badge in center of arc -->
			<div class="text-center -mt-8">
				<div class="text-xs text-slate-400 font-medium">{i18n.t('daylight', 'Durasi Siang')}</div>
				<div class="text-lg font-bold font-mono text-white tracking-wide">
					{sun.daylight_duration}
				</div>
			</div>
		</div>

		<!-- Sunrise and Sunset Times Footer -->
		<div class="grid grid-cols-2 gap-3 pt-2 border-t border-white/5">
			<!-- Sunrise Card -->
			<div class="flex items-center gap-3 p-3 rounded-2xl bg-amber-500/5 border border-amber-500/10">
				<div class="p-2 rounded-xl bg-amber-500/20 text-amber-300">
					<Sunrise class="w-4 h-4" />
				</div>
				<div>
					<div class="text-[11px] text-slate-400 font-medium">{i18n.t('sunrise', 'Terbit')}</div>
					<div class="text-sm sm:text-base font-bold font-mono text-white">
						{sun.sunrise}
					</div>
				</div>
			</div>

			<!-- Sunset Card -->
			<div class="flex items-center gap-3 p-3 rounded-2xl bg-rose-500/5 border border-rose-500/10">
				<div class="p-2 rounded-xl bg-rose-500/20 text-rose-300">
					<Sunset class="w-4 h-4" />
				</div>
				<div>
					<div class="text-[11px] text-slate-400 font-medium">{i18n.t('sunset', 'Terbenam')}</div>
					<div class="text-sm sm:text-base font-bold font-mono text-white">
						{sun.sunset}
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}

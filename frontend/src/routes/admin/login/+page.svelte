<script>
	import { goto } from '$app/navigation';
	import { adminLogin } from '$lib/api.js';
	import { Shield, Key, Mail, ArrowRight, AlertCircle, CloudSun, Loader2 } from 'lucide-svelte';

	let email = $state('admin@nuvora.com');
	let password = $state('password123');
	let error = $state('');
	let loading = $state(false);

	async function handleSubmit(e) {
		e.preventDefault();
		error = '';
		loading = true;

		try {
			await adminLogin(email, password);
			goto('/admin/dashboard');
		} catch (err) {
			error = err.message || 'Invalid admin credentials.';
		} finally {
			loading = false;
		}
	}
</script>

<svelte:head>
	<title>Admin Login — Nuvora Weather</title>
</svelte:head>

<div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center p-4 relative overflow-hidden">
	<!-- Ambient Background Glows -->
	<div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-cyan-600/20 blur-[130px] pointer-events-none"></div>
	<div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-blue-600/20 blur-[130px] pointer-events-none"></div>

	<!-- Back to Public Dashboard -->
	<a href="/" class="absolute top-6 left-6 text-xs text-slate-400 hover:text-cyan-300 transition flex items-center gap-1.5 p-2 rounded-xl bg-slate-900/60 border border-white/5">
		<CloudSun class="w-4 h-4 text-cyan-400" />
		<span>Back to Public Weather</span>
	</a>

	<!-- Login Card -->
	<div class="w-full max-w-md glass-panel-glow rounded-3xl p-8 sm:p-10 border border-cyan-500/20 shadow-2xl relative z-10 space-y-6">
		<!-- Header -->
		<div class="text-center space-y-2">
			<div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 p-0.5 shadow-xl shadow-cyan-500/30 mx-auto">
				<div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
					<Shield class="w-7 h-7 text-cyan-400" />
				</div>
			</div>
			<h1 class="text-2xl font-bold font-heading text-white">
				Admin Control Portal
			</h1>
			<p class="text-xs text-slate-400 font-sans">
				Authorized administrative access to Nuvora backend systems
			</p>
		</div>

		<!-- Error Alert -->
		{#if error}
			<div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
				<AlertCircle class="w-4 h-4 shrink-0 text-rose-400" />
				<span>{error}</span>
			</div>
		{/if}

		<!-- Form -->
		<form onsubmit={handleSubmit} class="space-y-4">
			<div class="space-y-1.5">
				<label for="admin-email" class="block text-xs font-medium text-slate-300 uppercase tracking-wider">
					Admin Email
				</label>
				<div class="relative">
					<Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
					<input
						id="admin-email"
						type="email"
						bind:value={email}
						required
						placeholder="admin@nuvora.com"
						class="w-full pl-10 pr-4 py-2.5 rounded-2xl glass-input text-sm text-white placeholder-slate-500"
					/>
				</div>
			</div>

			<div class="space-y-1.5">
				<label for="admin-password" class="block text-xs font-medium text-slate-300 uppercase tracking-wider">
					Password
				</label>
				<div class="relative">
					<Key class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
					<input
						id="admin-password"
						type="password"
						bind:value={password}
						required
						placeholder="••••••••"
						class="w-full pl-10 pr-4 py-2.5 rounded-2xl glass-input text-sm text-white placeholder-slate-500 font-mono"
					/>
				</div>
			</div>

			<button
				type="submit"
				disabled={loading}
				class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold text-sm transition shadow-lg shadow-cyan-500/25 flex items-center justify-center gap-2 disabled:opacity-50"
			>
				{#if loading}
					<Loader2 class="w-4 h-4 animate-spin text-slate-950" />
					<span>Authenticating...</span>
				{:else}
					<span>Access Dashboard</span>
					<ArrowRight class="w-4 h-4" />
				{/if}
			</button>
		</form>

		<!-- Default Dev Credentials Hint -->
		<div class="p-3 rounded-xl bg-slate-900/60 border border-white/5 text-[11px] text-slate-400 text-center space-y-0.5">
			<div class="font-medium text-slate-300">Default Seed Credentials:</div>
			<div class="font-mono text-cyan-300">admin@nuvora.com / password123</div>
		</div>
	</div>
</div>

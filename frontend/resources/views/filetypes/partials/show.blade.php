<div
    class="bg-[var(--bg-card)] border border-[var(--border)] w-full max-sm:max-w-xs max-w-sm rounded-xl overflow-hidden p-6 relative">
    <button class="absolute top-4 right-4 text-[var(--fg-muted)] hover:text-white text-2xl"
        onclick="closeAllModals()">&times;</button>

    <div class="text-center mb-6">
        <h3 class="text-xl font-bold font-display mb-1">{{ $filetype->name }}</h3>
        <p class="text-[var(--fg-muted)]">File Type ID: {{ $filetype->id }}</p>
    </div>

    <div class="space-y-3">
        <div class="flex justify-between py-2 border-b border-[var(--border)] text-sm">
            <span class="text-[var(--fg-muted)]">Status</span>
            <span>{{ $filetype->status == 1 ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>

    <button class="btn-ghost w-full mt-6 p-2" onclick="closeAllModals()">Close</button>
</div>
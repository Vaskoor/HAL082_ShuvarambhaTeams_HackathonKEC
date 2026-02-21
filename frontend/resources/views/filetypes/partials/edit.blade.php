<div
    class="bg-[var(--bg-card)] border border-[var(--border)] w-full max-sm:max-w-xs max-w-sm rounded-xl overflow-hidden p-6 relative">
    <button class="absolute top-4 right-4 text-[var(--fg-muted)] hover:text-white text-2xl"
        onclick="closeAllModals()">&times;</button>

    <form id="editForm" class="space-y-4" action="{{ route('filetypes.update', $filetype->id) }}" method="POST">
        @csrf
        @method('PUT') <!-- Use PUT or PATCH depending on your route -->

        <div>
            <label class="text-sm text-[var(--fg-muted)] font-medium">Name</label>
            <input type="text" name="name" id="editName" class="input-field mt-1" value="{{ $filetype->name }}" required>
        </div>

        <div>
            <label class="text-sm text-[var(--fg-muted)] font-medium">Status</label>
            <select name="status" id="editStatus" class="input-field mt-1">
                <option value="1" {{ $filetype->status == 1 ? 'selected' : '' }}>Active</option>
                <option value="0" {{ $filetype->status == 0 ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" class="btn-ghost flex-1 p-2" onclick="closeAllModals()">Cancel</button>
            <button type="submit" class="btn-primary flex-1">Save Changes</button>
        </div>
    </form>
</div>
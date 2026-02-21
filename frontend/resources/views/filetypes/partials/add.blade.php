<div class="bg-[var(--bg-card)] border border-[var(--border)] w-full max-sm:max-w-xs max-w-sm rounded-xl overflow-hidden p-6 relative">
    <button class="absolute top-4 right-4 text-[var(--fg-muted)] hover:text-white text-2xl" onclick="closeModal()">&times;</button>

    <form id="modalForm" class="space-y-4" action="{{ route('filetypes.store') }}" method="POST">
        @csrf

        <div>
            <label class="text-sm text-[var(--fg-muted)] font-medium">Name</label>
            <input type="text" name="name" id="fileTypeName" class="input-field mt-1" required>
        </div>

        <div>
            <label class="text-sm text-[var(--fg-muted)] font-medium">Status</label>
            <select name="status" id="fileTypeStatus" class="input-field mt-1">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <div class="flex gap-3 pt-4">
            <button type="button" class="btn-ghost flex-1 p-2" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn-primary flex-1">Save</button>
        </div>
    </form>
</div>
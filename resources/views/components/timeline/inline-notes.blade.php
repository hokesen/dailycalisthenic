@props(['model', 'notes', 'updateRoute'])

<div
    x-data="{
        editing: false,
        notes: @js($notes ?? ''),
        saving: false,
        saveNotes() {
            this.saving = true;
            fetch('{{ route($updateRoute, $model) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: this.notes })
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to save notes');
                }
                return response.json().catch(() => ({}));
            })
            .then(() => {
                this.saving = false;
                this.editing = false;
            })
            .catch((error) => {
                console.error('Error:', error);
                this.saving = false;
                alert('Failed to save notes. Please try again.');
            });
        }
    }"
    class="mt-2"
>
    <div x-show="!editing" class="flex items-start justify-between gap-3 cursor-pointer group">
        <div @click="editing = true">
            <p class="text-xs uppercase tracking-wide text-white/40 mb-1">Notes</p>
            <p
                class="text-sm text-white/60 italic group-hover:text-white/80"
                x-text="notes || 'Add notes...'"
            ></p>
        </div>
        <button
            type="button"
            @click.stop="editing = true"
            class="text-xs font-semibold uppercase tracking-wide text-emerald-300 hover:text-emerald-200"
        >
            <span x-text="notes ? 'Edit' : 'Add'"></span>
        </button>
    </div>

    <div x-show="editing" class="space-y-2">
        <textarea
            x-model="notes"
            class="app-input px-3 py-2.5 text-sm sm:text-base"
            rows="3"
            placeholder="Add notes about this practice..."
            @keydown.escape="editing = false"
        ></textarea>
        <div class="flex gap-2">
            <button
                @click="saveNotes()"
                :disabled="saving"
                class="app-btn app-btn-primary"
            >
                <span x-show="!saving">Save</span>
                <span x-show="saving">Saving...</span>
            </button>
            <button
                @click="editing = false; notes = @js($notes ?? '')"
                :disabled="saving"
                class="app-btn app-btn-secondary"
            >
                Cancel
            </button>
        </div>
    </div>
</div>

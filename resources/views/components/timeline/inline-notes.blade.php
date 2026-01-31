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
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notes: this.notes })
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
    <div x-show="!editing" @click="editing = true" class="cursor-pointer group">
        <p
            class="text-sm text-gray-600 dark:text-gray-400 italic group-hover:text-gray-800 dark:group-hover:text-gray-300"
            x-text="notes || 'Add notes...'"
        ></p>
    </div>

    <div x-show="editing" class="space-y-2">
        <textarea
            x-model="notes"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent"
            rows="3"
            placeholder="Add notes about this practice..."
            @keydown.escape="editing = false"
        ></textarea>
        <div class="flex gap-2">
            <button
                @click="saveNotes()"
                :disabled="saving"
                class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!saving">Save</span>
                <span x-show="saving">Saving...</span>
            </button>
            <button
                @click="editing = false; notes = @js($notes ?? '')"
                :disabled="saving"
                class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Cancel
            </button>
        </div>
    </div>
</div>

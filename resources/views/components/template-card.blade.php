@props(['template', 'allExercises'])

<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col" x-data="{ editingName: false }">
    <div class="mb-2">
        <div x-show="!editingName" class="flex items-center justify-between gap-2">
            <h5 class="font-semibold text-gray-900 dark:text-white text-lg">{{ $template->name }}</h5>
            @if ($template->user_id === auth()->id())
                <button @click="editingName = true" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">Edit</button>
            @endif
        </div>
        <div x-show="editingName" class="space-y-2">
            <form action="{{ route('templates.update-name', $template) }}" method="POST" class="flex gap-2" @submit.prevent="
                const formData = new FormData($el);
                fetch($el.action, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: formData.get('name') })
                }).then(() => location.reload())
            ">
                @csrf
                @method('PATCH')
                <input type="text" name="name" value="{{ $template->name }}" class="flex-grow border-2 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg px-3 py-1.5 text-base focus:border-blue-500 dark:focus:border-blue-600 focus:outline-none">
                <button type="submit" class="bg-blue-600 dark:bg-blue-700 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 font-medium text-sm">Save</button>
                <button type="button" @click="editingName = false" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium text-sm">Cancel</button>
            </form>
            <form action="{{ route('templates.destroy', $template) }}" method="POST" @submit.prevent="
                if(confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                    fetch($el.action, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(() => location.reload())
                }
            ">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-red-600 dark:bg-red-700 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 dark:hover:bg-red-600 font-medium text-sm transition-colors">Delete Template</button>
            </form>
        </div>
    </div>
    @if ($template->description)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $template->description }}</p>
    @endif

    @if ($template->exercises->isNotEmpty())
        <div class="mb-4 flex-grow">
            <p class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Exercises:</p>
            <div class="space-y-3">
                @foreach ($template->exercises as $exercise)
                    <x-exercise-item :template="$template" :exercise="$exercise" :allExercises="$allExercises" />
                @endforeach
            </div>

            <x-add-exercise :template="$template" :allExercises="$allExercises" />
        </div>
        @php
            $duration = $template->calculateDurationMinutes();
        @endphp
        @if ($duration > 0)
            <p class="text-base font-medium text-gray-600 dark:text-gray-400 mb-4">~{{ $duration }} minutes</p>
        @endif
    @else
        <div class="mb-4 flex-grow">
            <p class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Exercises:</p>
            <p class="text-base text-gray-500 dark:text-gray-400 mb-4">No exercises yet</p>
            <x-add-exercise :template="$template" :allExercises="$allExercises" />
        </div>
    @endif

    <div>
        <a href="{{ route('go.index', ['template' => $template->id]) }}">
            <x-primary-button type="button" class="w-full justify-center">
                Go
            </x-primary-button>
        </a>
    </div>
</div>

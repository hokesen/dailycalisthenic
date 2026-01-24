@props(['template', 'allExercises'])

<div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2 sm:p-4 flex flex-col" x-data="{ editingName: false }">
    <div class="mb-2">
        <div x-show="!editingName" class="flex items-center justify-between gap-2">
            <div class="flex-grow">
                <h5 class="font-semibold text-gray-900 dark:text-white text-lg">{{ $template->name }}</h5>
                @if ($template->user)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">by {{ $template->user->name }}</p>
                @elseif ($template->user_id === null)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Default Template</p>
                @endif
            </div>
            @if ($template->user_id === auth()->id())
                <button @click="editingName = true" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">Edit</button>
            @else
                <form action="{{ route('templates.copy', $template) }}" method="POST" @submit.prevent="
                    fetch($el.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(() => location.reload())
                ">
                    @csrf
                    <button type="submit" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium">Copy</button>
                </form>
            @endif
        </div>
        @if ($template->user_id === auth()->id())
            <div x-show="!editingName" class="flex items-center gap-2 mt-2">
                <form action="{{ route('templates.toggle-visibility', $template) }}" method="POST" @submit.prevent="
                    fetch($el.action, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(() => location.reload())
                ">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $template->is_public ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}" role="switch" aria-checked="{{ $template->is_public ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $template->is_public ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </form>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $template->is_public ? 'Public' : 'Private' }}</span>
            </div>
        @endif
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
            <div class="grid grid-cols-2 gap-2 sm:gap-3">
                @foreach ($template->exercises as $exercise)
                    <x-exercise-item :template="$template" :exercise="$exercise" :allExercises="$allExercises" />
                @endforeach
            </div>

            @if ($template->user_id === auth()->id())
                <x-add-exercise :template="$template" :allExercises="$allExercises" />
            @endif
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
            @if ($template->user_id === auth()->id())
                <x-add-exercise :template="$template" :allExercises="$allExercises" />
            @endif
        </div>
    @endif

    <div>
        <a href="{{ route('go.index', ['template' => $template->id]) }}">
            <x-primary-button type="button" class="w-full justify-center">
                Practice
            </x-primary-button>
        </a>
    </div>
</div>

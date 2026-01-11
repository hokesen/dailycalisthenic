@props(['template', 'exercise', 'allExercises'])

@php
    $easierVariations = $exercise->getEasierVariations();
    $harderVariations = $exercise->getHarderVariations();
@endphp

<div data-exercise-item class="border border-gray-300 dark:border-gray-600 rounded-lg p-2 sm:p-3 bg-white dark:bg-gray-700 hover:border-gray-400 dark:hover:border-gray-500 transition-colors" x-data="{ showSwap: false, showEdit: false }">
    <div class="flex items-start justify-between gap-2 sm:gap-3">
        <div class="flex items-start gap-1.5 sm:gap-2 flex-grow min-w-0">
            @if ($template->user_id === auth()->id())
                <div class="flex flex-col gap-1 flex-shrink-0 justify-end">
                    <button x-show="{{ $exercise->pivot->order > 1 ? 'true' : 'false' }}" data-move-up @click="
                        const currentItem = $el.closest('[data-exercise-item]');
                        const previousItem = currentItem.previousElementSibling;

                        fetch('{{ route('templates.move-exercise-up', $template) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ exercise_id: {{ $exercise->id }} })
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Swap the items in the DOM
                            if (previousItem) {
                                currentItem.parentNode.insertBefore(currentItem, previousItem);

                                // Update order numbers and button visibility
                                const items = currentItem.parentNode.querySelectorAll('[data-exercise-item]');
                                items.forEach((item, index) => {
                                    const orderSpan = item.querySelector('[data-order-number]');
                                    if (orderSpan) orderSpan.textContent = (index + 1) + '.';

                                    // Update button visibility
                                    const upBtn = item.querySelector('[data-move-up]');
                                    const downBtn = item.querySelector('[data-move-down]');
                                    if (upBtn) upBtn.style.display = index === 0 ? 'none' : 'block';
                                    if (downBtn) downBtn.style.display = index === items.length - 1 ? 'none' : 'block';
                                });
                            }
                        })
                        .catch(error => console.error('Error:', error))
                    " class="text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors" title="Move up">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button x-show="{{ $exercise->pivot->order < $template->exercises->count() ? 'true' : 'false' }}" data-move-down @click="
                        const currentItem = $el.closest('[data-exercise-item]');
                        const nextItem = currentItem.nextElementSibling;

                        fetch('{{ route('templates.move-exercise-down', $template) }}', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ exercise_id: {{ $exercise->id }} })
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Swap the items in the DOM
                            if (nextItem) {
                                currentItem.parentNode.insertBefore(nextItem, currentItem);

                                // Update order numbers and button visibility
                                const items = currentItem.parentNode.querySelectorAll('[data-exercise-item]');
                                items.forEach((item, index) => {
                                    const orderSpan = item.querySelector('[data-order-number]');
                                    if (orderSpan) orderSpan.textContent = (index + 1) + '.';

                                    // Update button visibility
                                    const upBtn = item.querySelector('[data-move-up]');
                                    const downBtn = item.querySelector('[data-move-down]');
                                    if (upBtn) upBtn.style.display = index === 0 ? 'none' : 'block';
                                    if (downBtn) downBtn.style.display = index === items.length - 1 ? 'none' : 'block';
                                });
                            }
                        })
                        .catch(error => console.error('Error:', error))
                    " class="text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors" title="Move down">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            @endif
            <span class="text-gray-500 dark:text-gray-400 font-bold text-base sm:text-lg flex-shrink-0" data-order-number>{{ $exercise->pivot->order }}.</span>
            <div class="flex-grow min-w-0">
                <div class="font-bold text-gray-900 dark:text-gray-100 text-base sm:text-lg leading-tight">{{ $exercise->name }}</div>
                <div class="text-gray-600 dark:text-gray-300 mt-1 text-sm sm:text-base" x-show="!showEdit">
                    @if ($exercise->pivot->sets && $exercise->pivot->reps)
                        <span class="font-semibold">{{ $exercise->pivot->sets }} × {{ $exercise->pivot->reps }}</span>
                    @endif
                    @if ($exercise->pivot->duration_seconds)
                        <span class="font-semibold">{{ $exercise->pivot->duration_seconds }}s</span>
                    @endif
                    @if ($exercise->pivot->rest_after_seconds)
                        <span class="text-gray-500">• Rest: {{ $exercise->pivot->rest_after_seconds }}s</span>
                    @endif
                </div>

                <!-- Edit Form -->
                <form x-show="showEdit" action="{{ route('templates.update-exercise', $template) }}" method="POST" class="mt-3 space-y-3" @submit.prevent="
                    const formData = new FormData($el);
                    fetch($el.action, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            exercise_id: {{ $exercise->id }},
                            sets: formData.get('sets'),
                            reps: formData.get('reps'),
                            duration_seconds: formData.get('duration_seconds'),
                            rest_after_seconds: formData.get('rest_after_seconds')
                        })
                    }).then(() => location.reload())
                ">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="exercise_id" value="{{ $exercise->id }}">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Sets</label>
                            <input type="number" name="sets" placeholder="Sets" value="{{ $exercise->pivot->sets }}" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Reps</label>
                            <input type="number" name="reps" placeholder="Reps" value="{{ $exercise->pivot->reps }}" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Duration (seconds)</label>
                            <input type="number" name="duration_seconds" placeholder="Duration" value="{{ $exercise->pivot->duration_seconds }}" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Rest (seconds)</label>
                            <input type="number" name="rest_after_seconds" placeholder="Rest" value="{{ $exercise->pivot->rest_after_seconds }}" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-base">Save</button>
                        <button type="button" @click="showEdit = false" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-medium text-base">Cancel</button>
                    </div>
                </form>

                <!-- Swap Dropdown -->
                <div x-show="showSwap" class="mt-3">
                    @if (count($easierVariations) > 0 || count($harderVariations) > 0 || $allExercises->count() > 0)
                        <form action="{{ route('templates.swap-exercise', $template) }}" method="POST" @submit.prevent="
                            fetch($el.action, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    exercise_id: {{ $exercise->id }},
                                    order: {{ $exercise->pivot->order }},
                                    new_exercise_id: $el.querySelector('select').value
                                })
                            }).then(() => location.reload())
                        ">
                            @csrf
                            <input type="hidden" name="exercise_id" value="{{ $exercise->id }}">
                            <select name="new_exercise_id" @change="if($event.target.value) $event.target.form.requestSubmit()" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 focus:border-blue-500 focus:outline-none">
                                <option value="">Select exercise...</option>
                                @if (count($easierVariations) > 0)
                                    <optgroup label="Easier">
                                        @foreach ($easierVariations as $easier)
                                            <option value="{{ $easier->id }}">{{ $easier->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if (count($harderVariations) > 0)
                                    <optgroup label="Harder">
                                        @foreach ($harderVariations as $harder)
                                            <option value="{{ $harder->id }}">{{ $harder->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($allExercises->count() > 0)
                                    <optgroup label="Something else">
                                        @foreach ($allExercises as $allEx)
                                            @if ($allEx->id !== $exercise->id)
                                                <option value="{{ $allEx->id }}">{{ $allEx->name }}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </form>
                    @endif
                    <button @click="showSwap = false" class="text-sm text-gray-600 mt-2 hover:text-gray-800 underline">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        @if ($template->user_id === auth()->id())
            <div class="flex gap-1 sm:gap-2 flex-shrink-0" x-show="!showSwap && !showEdit">
                <button @click="showSwap = !showSwap" class="bg-blue-100 text-blue-700 px-2 py-1 sm:px-3 sm:py-1.5 rounded text-sm font-medium hover:bg-blue-200 transition-colors">Swap</button>
                <button @click="showEdit = !showEdit" class="bg-green-100 text-green-700 px-2 py-1 sm:px-3 sm:py-1.5 rounded text-sm font-medium hover:bg-green-200 transition-colors">Edit</button>
                <form action="{{ route('templates.remove-exercise', $template) }}" method="POST" @submit.prevent="
                    if(confirm('Remove this exercise?')) {
                        fetch($el.action, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ exercise_id: {{ $exercise->id }} })
                        }).then(() => location.reload())
                    }
                ">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="exercise_id" value="{{ $exercise->id }}">
                    <button type="submit" class="bg-red-100 text-red-700 px-2 py-1 sm:px-2.5 sm:py-1.5 rounded font-bold text-sm hover:bg-red-200 transition-colors">✕</button>
                </form>
            </div>
        @endif
    </div>
</div>

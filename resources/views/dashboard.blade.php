<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-2xl font-bold mb-6">Welcome, {{ auth()->user()->name }}!</h3>

                    @if ($templates->isEmpty())
                        <p class="text-gray-600">No workout templates available yet.</p>
                    @else
                        <div class="space-y-4">
                            <h4 class="text-lg font-semibold text-gray-700">Available Templates</h4>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($templates as $template)
                                    <div class="border border-gray-200 rounded-lg p-4 flex flex-col" x-data="{ editingName: false }">
                                        <div class="mb-2">
                                            <div x-show="!editingName" class="flex items-center justify-between gap-2">
                                                <h5 class="font-semibold text-gray-900 text-lg">{{ $template->name }}</h5>
                                                @if ($template->user_id === auth()->id())
                                                    <button @click="editingName = true" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</button>
                                                @endif
                                            </div>
                                            <form x-show="editingName" action="{{ route('templates.update-name', $template) }}" method="POST" class="flex gap-2" @submit.prevent="
                                                const formData = new FormData($el);
                                                fetch($el.action, {
                                                    method: 'PATCH',
                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                                    body: JSON.stringify({ name: formData.get('name') })
                                                }).then(() => location.reload())
                                            ">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="name" value="{{ $template->name }}" class="flex-grow border-2 border-gray-300 rounded-lg px-3 py-1.5 text-base focus:border-blue-500 focus:outline-none">
                                                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium text-sm">Save</button>
                                                <button type="button" @click="editingName = false" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-300 font-medium text-sm">Cancel</button>
                                            </form>
                                        </div>
                                        @if ($template->description)
                                            <p class="text-sm text-gray-600 mb-2">{{ $template->description }}</p>
                                        @endif

                                        @if ($template->exercises->isNotEmpty())
                                            <div class="mb-4 flex-grow">
                                                <p class="text-lg font-bold text-gray-800 mb-4">Exercises:</p>
                                                <div class="space-y-3">
                                                    @foreach ($template->exercises as $exercise)
                                                        @php
                                                            $easierVariations = $exercise->getEasierVariations();
                                                            $harderVariations = $exercise->getHarderVariations();
                                                        @endphp
                                                        <div class="border-2 border-gray-300 rounded-lg p-4 bg-white hover:border-gray-400 transition-colors" x-data="{ showSwap: false, showEdit: false }">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-start gap-3 flex-grow min-w-0">
                                                                    <span class="text-gray-500 font-bold text-lg flex-shrink-0 mt-0.5">{{ $exercise->pivot->order }}.</span>
                                                                    <div class="flex-grow min-w-0">
                                                                        <div class="font-bold text-gray-900 text-lg">{{ $exercise->name }}</div>
                                                                        <div class="text-gray-600 mt-1.5 text-base" x-show="!showEdit">
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
                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Sets</label>
                                                                                    <input type="number" name="sets" placeholder="Sets" value="{{ $exercise->pivot->sets }}" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-base focus:border-blue-500 focus:outline-none">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Reps</label>
                                                                                    <input type="number" name="reps" placeholder="Reps" value="{{ $exercise->pivot->reps }}" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-base focus:border-blue-500 focus:outline-none">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Duration (seconds)</label>
                                                                                    <input type="number" name="duration_seconds" placeholder="Duration" value="{{ $exercise->pivot->duration_seconds }}" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-base focus:border-blue-500 focus:outline-none">
                                                                                </div>
                                                                                <div>
                                                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Rest (seconds)</label>
                                                                                    <input type="number" name="rest_after_seconds" placeholder="Rest" value="{{ $exercise->pivot->rest_after_seconds }}" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 text-base focus:border-blue-500 focus:outline-none">
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
                                                                                            new_exercise_id: $el.querySelector('select').value
                                                                                        })
                                                                                    }).then(() => location.reload())
                                                                                ">
                                                                                    @csrf
                                                                                    <input type="hidden" name="exercise_id" value="{{ $exercise->id }}">
                                                                                    <select name="new_exercise_id" @change="if($event.target.value) $event.target.form.requestSubmit()" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none">
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
                                                                <div class="flex gap-2 flex-shrink-0" x-show="!showSwap && !showEdit">
                                                                    <button @click="showSwap = !showSwap" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 font-medium text-base transition-colors">Swap</button>
                                                                    <button @click="showEdit = !showEdit" class="bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 font-medium text-base transition-colors">Edit</button>
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
                                                                        <button type="submit" class="bg-red-100 text-red-700 px-3 py-2 rounded-lg hover:bg-red-200 font-bold text-lg transition-colors">✕</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <!-- Add Exercise -->
                                                <div class="mt-4" x-data="{ showCustom: false, customName: '' }">
                                                    <form action="{{ route('templates.add-exercise', $template) }}" method="POST" x-show="!showCustom" @submit.prevent="
                                                        fetch($el.action, {
                                                            method: 'POST',
                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                                            body: JSON.stringify({ exercise_id: $el.querySelector('select').value })
                                                        }).then(() => location.reload())
                                                    ">
                                                        @csrf
                                                        <select name="exercise_id" @change="if($event.target.value === 'custom') { showCustom = true; $event.target.value = ''; } else { $event.target.form.requestSubmit(); }" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none">
                                                            <option value="">+ Add Exercise</option>
                                                            <option value="custom">+ Create Custom Exercise</option>
                                                            @foreach ($allExercises as $allEx)
                                                                <option value="{{ $allEx->id }}">{{ $allEx->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                    <form action="{{ route('templates.add-custom-exercise', $template) }}" method="POST" x-show="showCustom" class="flex gap-2" @submit.prevent="
                                                        fetch($el.action, {
                                                            method: 'POST',
                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                                            body: JSON.stringify({ name: customName })
                                                        }).then(() => location.reload())
                                                    ">
                                                        @csrf
                                                        <input type="text" name="name" x-model="customName" placeholder="Exercise name..." class="flex-grow border-2 border-gray-300 rounded-lg px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none" required>
                                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-base">Add</button>
                                                        <button type="button" @click="showCustom = false; customName = ''" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-medium text-base">Cancel</button>
                                                    </form>
                                                </div>
                                            </div>
                                            @php
                                                $duration = $template->calculateDurationMinutes();
                                            @endphp
                                            @if ($duration > 0)
                                                <p class="text-base font-medium text-gray-600 mb-4">~{{ $duration }} minutes</p>
                                            @endif
                                        @else
                                            <div class="mb-4 flex-grow">
                                                <p class="text-lg font-bold text-gray-800 mb-4">Exercises:</p>
                                                <p class="text-base text-gray-500 mb-4">No exercises yet</p>
                                                <div x-data="{ showCustom: false, customName: '' }">
                                                    <form action="{{ route('templates.add-exercise', $template) }}" method="POST" x-show="!showCustom" @submit.prevent="
                                                        fetch($el.action, {
                                                            method: 'POST',
                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                                            body: JSON.stringify({ exercise_id: $el.querySelector('select').value })
                                                        }).then(() => location.reload())
                                                    ">
                                                        @csrf
                                                        <select name="exercise_id" @change="if($event.target.value === 'custom') { showCustom = true; $event.target.value = ''; } else { $event.target.form.requestSubmit(); }" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none">
                                                            <option value="">+ Add Exercise</option>
                                                            <option value="custom">+ Create Custom Exercise</option>
                                                            @foreach ($allExercises as $allEx)
                                                                <option value="{{ $allEx->id }}">{{ $allEx->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                    <form action="{{ route('templates.add-custom-exercise', $template) }}" method="POST" x-show="showCustom" class="flex gap-2" @submit.prevent="
                                                        fetch($el.action, {
                                                            method: 'POST',
                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                                                            body: JSON.stringify({ name: customName })
                                                        }).then(() => location.reload())
                                                    ">
                                                        @csrf
                                                        <input type="text" name="name" x-model="customName" placeholder="Exercise name..." class="flex-grow border-2 border-gray-300 rounded-lg px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none" required>
                                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-base">Add</button>
                                                        <button type="button" @click="showCustom = false; customName = ''" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-medium text-base">Cancel</button>
                                                    </form>
                                                </div>
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
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

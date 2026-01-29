@props(['template', 'allExercises'])

<div class="mt-4" x-data="{ showCustom: false, customName: '' }">
    <form action="{{ route('templates.add-exercise', $template) }}" method="POST" x-show="!showCustom" @submit.prevent="
        fetch($el.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ exercise_id: $el.querySelector('select').value })
        }).then(response => response.json()).then(() => location.reload()).catch(err => console.error(err))
    ">
        @csrf
        <select name="exercise_id" @change="if($event.target.value === 'custom') { showCustom = true; $event.target.value = ''; } else { $event.target.form.requestSubmit(); }" class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 focus:border-blue-500 focus:outline-none">
            <option value="">+ Add Exercise</option>
            <option value="custom">+ Create Custom Exercise</option>
            @php
                $exercisesByCategory = $allExercises->groupBy(fn($ex) => $ex->category?->label() ?? 'Other');
            @endphp
            @foreach ($exercisesByCategory as $categoryName => $exercises)
                <optgroup label="{{ $categoryName }}">
                    @foreach ($exercises as $allEx)
                        <option value="{{ $allEx->id }}">{{ $allEx->name }}@if($allEx->difficulty_level) ({{ $allEx->difficulty_level->label() }})@endif</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </form>
    <form action="{{ route('templates.add-custom-exercise', $template) }}" method="POST" x-show="showCustom" class="flex gap-2" @submit.prevent="
        fetch($el.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: customName })
        }).then(response => response.json()).then(() => location.reload()).catch(err => console.error(err))
    ">
        @csrf
        <input type="text" name="name" x-model="customName" placeholder="Exercise name..." class="flex-grow border-2 border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2.5 text-base text-gray-900 dark:text-gray-100 dark:bg-gray-700 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 focus:outline-none" required>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-base">Add</button>
        <button type="button" @click="showCustom = false; customName = ''" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 font-medium text-base">Cancel</button>
    </form>
</div>

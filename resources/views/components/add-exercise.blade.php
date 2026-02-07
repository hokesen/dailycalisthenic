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
        <select name="exercise_id" @change="if($event.target.value === 'custom') { showCustom = true; $event.target.value = ''; } else { $event.target.form.requestSubmit(); }" class="w-full rounded-lg px-3 py-2.5 text-base app-field focus:outline-none">
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
        <input type="text" name="name" x-model="customName" placeholder="Exercise name..." class="flex-grow rounded-lg px-3 py-2.5 text-base app-field placeholder:text-white/40 focus:outline-none" required>
        <button type="submit" class="bg-emerald-500 text-white px-4 py-2 rounded-lg hover:bg-emerald-600 font-medium text-base">Add</button>
        <button type="button" @click="showCustom = false; customName = ''" class="bg-white/10 text-white/80 px-4 py-2 rounded-lg hover:bg-white/20 font-medium text-base">Cancel</button>
    </form>
</div>

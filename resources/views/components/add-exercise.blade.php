@props(['template', 'allExercises'])

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

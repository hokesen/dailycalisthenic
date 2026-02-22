@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'app-input px-3 py-2.5 text-sm sm:text-base']) }}>

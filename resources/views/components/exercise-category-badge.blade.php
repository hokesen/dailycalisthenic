@props(['category'])

<span class="text-xs px-1.5 py-0.5 rounded {{ $colorClasses }}">{{ $category->label() }}</span>

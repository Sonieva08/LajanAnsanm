<div class="flex items-center gap-2 text-sm">
    <a href="{{ route('lang.switch', 'ht') }}"
       class="px-2 py-1 rounded {{ app()->getLocale() === 'ht' ? 'bg-blue-700 text-white' : 'text-gray-600' }}">HT</a>
    <a href="{{ route('lang.switch', 'fr') }}"
       class="px-2 py-1 rounded {{ app()->getLocale() === 'fr' ? 'bg-blue-700 text-white' : 'text-gray-600' }}">FR</a>
</div>
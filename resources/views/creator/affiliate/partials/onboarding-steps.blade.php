@php
    $current = $current ?? 1;
@endphp
<ol class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-center sm:gap-4 lg:gap-6">
    <li class="flex items-center gap-2 {{ $current >= 1 ? 'text-primary' : 'text-slate-400' }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $current >= 1 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600' }}">1</span>
        <span class="text-sm font-semibold">Creator profile</span>
    </li>
    <li class="hidden h-px w-6 bg-slate-200 sm:block lg:w-8" aria-hidden="true"></li>
    <li class="flex items-center gap-2 {{ $current >= 2 ? 'text-primary' : 'text-slate-400' }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $current >= 2 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600' }}">2</span>
        <span class="text-sm font-semibold">Your account</span>
    </li>
    <li class="hidden h-px w-6 bg-slate-200 sm:block lg:w-8" aria-hidden="true"></li>
    <li class="flex items-center gap-2 {{ $current >= 3 ? 'text-primary' : 'text-slate-400' }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $current >= 3 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-600' }}">3</span>
        <span class="text-sm font-semibold">Verify email</span>
    </li>
</ol>

@extends('layouts.customer')

@section('title', 'Local rental partners')

@section('content')
<div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-12">
    <nav aria-label="Breadcrumb" class="mb-4 text-body-sm text-on-surface-variant">
        <a href="{{ route('home') }}" class="hover:text-secondary">Home</a> <span class="mx-1">/</span> <span class="text-on-surface">Partners</span>
    </nav>

    <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
        <div>
            <h1 class="text-display-lg font-extrabold tracking-tight text-on-surface">Our rental partners</h1>
            <p class="mt-1 text-body-md text-on-surface-variant">Local shops you can rent from, each with their own vehicles and prices.</p>
        </div>
        <form method="GET" action="{{ route('partners.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="Search shop or city" aria-label="Search shops" class="w-52 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
            @if (count($cities) > 1)
                <select name="city" aria-label="City" class="rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-body-md focus:border-secondary focus:outline-none">
                    <option value="">All cities</option>
                    @foreach ($cities as $c)<option value="{{ $c }}" @selected($c === $city)>{{ $c }}</option>@endforeach
                </select>
            @endif
            <button class="rounded-lg bg-primary px-4 py-2 text-label-md text-on-primary">Search</button>
        </form>
    </div>

    @if ($partners->isEmpty())
        <div class="mt-10 rounded-2xl bg-surface-container-lowest p-10 text-center text-on-surface-variant shadow-sm">
            @if ($q !== '' || $city !== '')
                No shops match your search. <a href="{{ route('partners.index') }}" class="font-semibold text-secondary hover:underline">Show all shops</a>
            @else
                No shops are listed yet. Check back soon.
            @endif
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($partners as $business)
                @include('partials.business-card', ['business' => $business])
            @endforeach
        </div>

        @if ($partners->hasPages())
            <div class="mt-8 flex items-center justify-between gap-3 text-body-sm text-on-surface-variant">
                <span>{{ $partners->firstItem() }}–{{ $partners->lastItem() }} of {{ $partners->total() }}</span>
                <div class="flex gap-2">
                    @if ($partners->onFirstPage()) <span class="rounded-lg bg-surface-container px-4 py-2 opacity-50">Previous</span> @else <a href="{{ $partners->previousPageUrl() }}" class="rounded-lg bg-surface-container-lowest px-4 py-2 shadow-sm hover:bg-surface-container-high">Previous</a> @endif
                    @if ($partners->hasMorePages()) <a href="{{ $partners->nextPageUrl() }}" class="rounded-lg bg-surface-container-lowest px-4 py-2 shadow-sm hover:bg-surface-container-high">Next</a> @else <span class="rounded-lg bg-surface-container px-4 py-2 opacity-50">Next</span> @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
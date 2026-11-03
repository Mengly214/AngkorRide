{{-- Save-to-favourites heart. Needs $vehicle (with business), $saved (bool), $next (path to return to after sign in). --}}
@php $size = $size ?? 'h-9 w-9'; @endphp
@if ($vehicle->business)
    @if (auth()->check() && auth()->user()->role === 'customer')
        <form method="POST" action="{{ route('vehicles.favorite', ['business' => $vehicle->business, 'vehicle' => $vehicle]) }}">
            @csrf
            <button type="submit" aria-pressed="{{ $saved ? 'true' : 'false' }}" aria-label="{{ $saved ? 'Remove from favourites' : 'Save to favourites' }}"
                    class="{{ $size }} flex items-center justify-center rounded-full bg-surface-container-lowest/90 shadow-sm backdrop-blur-md transition-colors hover:bg-surface-container-lowest {{ $saved ? 'text-error' : 'text-on-surface hover:text-error' }}">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' {{ $saved ? 1 : 0 }}">favorite</span>
            </button>
        </form>
    @else
        <a href="{{ route('login', ['next' => $next ?? '/']) }}" aria-label="Sign in to save this vehicle" title="Sign in to save"
           class="{{ $size }} flex items-center justify-center rounded-full bg-surface-container-lowest/90 text-on-surface shadow-sm backdrop-blur-md transition-colors hover:bg-surface-container-lowest hover:text-error">
            <span class="material-symbols-outlined text-lg">favorite</span>
        </a>
    @endif
@endif
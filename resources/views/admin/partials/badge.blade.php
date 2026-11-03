@php
    $map = [
        'pending' => 'bg-secondary-container text-on-secondary-container',
        'approved' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'rejected' => 'bg-error-container text-on-error-container',
        'suspended' => 'bg-error-container text-on-error-container',
        'confirmed' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'ongoing' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'completed' => 'bg-surface-container text-on-surface-variant',
        'paid' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'unpaid' => 'bg-surface-container text-on-surface-variant',
        'active' => 'bg-tertiary-fixed text-on-tertiary-fixed',
    ];
    $k = strtolower($status);
@endphp
<span class="inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-label-sm {{ $map[$k] ?? 'bg-error-container text-on-error-container' }}">{{ $text ?? ucfirst($status) }}</span>
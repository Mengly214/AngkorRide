{{-- Simple bar chart. Needs: $buckets (label,title,<key>), $key, $max, $fmt (closure) --}}
@php $every = count($buckets) > 14 ? 5 : 1; @endphp
<div class="flex">
    <div class="flex h-36 w-11 shrink-0 flex-col justify-between pr-2 text-right text-label-sm text-on-surface-variant" aria-hidden="true">
        <span>{{ $fmt($max) }}</span><span>{{ $fmt($max / 2) }}</span><span>0</span>
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex h-36 items-end gap-[3px] border-b border-l border-surface-container" role="img" aria-label="{{ $label }}">
            @foreach ($buckets as $b)
                <div class="flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $b['title'] }}: {{ $fmt($b[$key]) }}">
                    @if ($b[$key] > 0)<div class="w-full rounded-t-sm {{ $tone ?? 'bg-secondary' }}" style="height:{{ round($b[$key] / $max * 100, 1) }}%"></div>@endif
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex gap-[3px] text-center text-[10px] text-on-surface-variant" aria-hidden="true">
            @foreach ($buckets as $i => $b)
                <span class="min-w-0 flex-1 whitespace-nowrap">{{ ($i % $every === 0 || $i === count($buckets) - 1) ? $b['label'] : '' }}</span>
            @endforeach
        </div>
    </div>
</div>
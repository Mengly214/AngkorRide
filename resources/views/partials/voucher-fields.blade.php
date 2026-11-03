{{-- Voucher form fields, shared by the shop portal and the admin. Needs $values (array), $voucher (?Voucher), $errors. --}}
@php
    $inp = 'w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-body-md focus:border-secondary focus:outline-none';
    $v = fn ($k) => old($k, $values[$k] ?? null);
    $type = $v('type') ?: 'percent';
    $locked = $voucher && $voucher->used_count > 0;
    $err = fn ($k) => $errors->first($k);
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <label for="vf-code" class="mb-1 block text-label-md text-on-surface-variant">Code customers type</label>
        <input id="vf-code" type="text" name="code" value="{{ $v('code') }}" maxlength="20" required @readonly($locked) autocapitalize="characters" autocomplete="off" placeholder="WELCOME10"
               class="{{ $inp }} font-semibold uppercase tracking-wide {{ $locked ? 'bg-surface-container text-on-surface-variant' : '' }}">
        @if ($locked)<p class="mt-1 text-body-sm text-on-surface-variant">Used {{ $voucher->used_count }} {{ $voucher->used_count == 1 ? 'time' : 'times' }}, so the code can no longer change.</p>@endif
        @if ($err('code'))<p class="mt-1 text-body-sm text-error">{{ $err('code') }}</p>@endif
    </div>
    <div>
        <label for="vf-desc" class="mb-1 block text-label-md text-on-surface-variant">Note for yourself <span class="font-normal">(optional)</span></label>
        <input id="vf-desc" type="text" name="description" value="{{ $v('description') }}" maxlength="120" placeholder="Low-season welcome offer" class="{{ $inp }}">
        @if ($err('description'))<p class="mt-1 text-body-sm text-error">{{ $err('description') }}</p>@endif
    </div>

    <fieldset class="sm:col-span-2">
        <legend class="mb-1 text-label-md text-on-surface-variant">Discount</legend>
        <div class="flex flex-wrap items-start gap-3">
            <div class="inline-flex rounded-xl bg-surface-container-high/60 p-1" role="radiogroup" aria-label="Discount type">
                @foreach (['percent' => 'Percentage (%)', 'fixed' => 'Fixed amount ($)'] as $k => $name)
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="{{ $k }}" class="peer sr-only" @checked($type === $k)>
                        <span class="block rounded-lg px-3.5 py-1.5 text-label-md text-on-surface-variant peer-checked:bg-primary peer-checked:text-on-primary peer-checked:shadow-sm">{{ $name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="relative w-40">
                <input id="vf-value" type="number" name="value" value="{{ $v('value') }}" step="0.01" min="0.01" required inputmode="decimal" placeholder="10" aria-label="Discount amount" class="{{ $inp }} pr-9">
                <span id="vf-unit" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-body-md text-on-surface-variant">{{ $type === 'percent' ? '%' : '$' }}</span>
            </div>
        </div>
        @if ($err('value') || $err('type'))<p class="mt-1 text-body-sm text-error">{{ $err('value') ?: $err('type') }}</p>@endif
    </fieldset>

    <div>
        <label for="vf-min" class="mb-1 block text-label-md text-on-surface-variant">Minimum rental value <span class="font-normal">(optional)</span></label>
        <div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">$</span>
            <input id="vf-min" type="number" name="min_subtotal" value="{{ $v('min_subtotal') }}" step="0.01" min="0" inputmode="decimal" placeholder="No minimum" class="{{ $inp }} pl-7"></div>
        @if ($err('min_subtotal'))<p class="mt-1 text-body-sm text-error">{{ $err('min_subtotal') }}</p>@endif
    </div>
    <div>
        <label for="vf-max" class="mb-1 block text-label-md text-on-surface-variant">Total uses allowed <span class="font-normal">(optional)</span></label>
        <input id="vf-max" type="number" name="max_uses" value="{{ $v('max_uses') }}" step="1" min="1" inputmode="numeric" placeholder="Unlimited" class="{{ $inp }}">
        @if ($err('max_uses'))<p class="mt-1 text-body-sm text-error">{{ $err('max_uses') }}</p>@endif
    </div>
    <div>
        <label for="vf-start" class="mb-1 block text-label-md text-on-surface-variant">First day <span class="font-normal">(optional)</span></label>
        <input id="vf-start" type="date" name="starts_on" value="{{ $v('starts_on') }}" class="{{ $inp }}">
        @if ($err('starts_on'))<p class="mt-1 text-body-sm text-error">{{ $err('starts_on') }}</p>@endif
    </div>
    <div>
        <label for="vf-end" class="mb-1 block text-label-md text-on-surface-variant">Last day <span class="font-normal">(optional)</span></label>
        <input id="vf-end" type="date" name="ends_on" value="{{ $v('ends_on') }}" class="{{ $inp }}">
        @if ($err('ends_on'))<p class="mt-1 text-body-sm text-error">{{ $err('ends_on') }}</p>@endif
    </div>

    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="inline-flex cursor-pointer items-center gap-2 text-body-md text-on-surface">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-secondary" @checked((bool) $v('is_active'))> Active (customers can use it)
        </label>
    </div>
</div>

<p id="vf-preview" class="mt-5 rounded-xl bg-surface-container-low px-4 py-3 text-body-md text-on-surface-variant" aria-live="polite"></p>

<script>
(function () {
    var f = document.getElementById('vf-code').form, $ = function (n) { return f.elements[n]; };
    function money(n) { return '$' + (Math.round(n * 100) / 100).toFixed(2).replace(/\.00$/, ''); }
    function day(s) { if (!s) return ''; var d = new Date(s + 'T00:00:00'); return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
    function render() {
        var type = f.querySelector('input[name=type]:checked').value, val = parseFloat($('value').value);
        document.getElementById('vf-unit').textContent = type === 'percent' ? '%' : '$';
        if (!(val > 0)) { document.getElementById('vf-preview').textContent = 'Fill in the discount to see how customers will see it.'; return; }
        var t = 'Customers get ' + (type === 'percent' ? val + '% off' : money(val) + ' off') + ' the rental';
        var min = parseFloat($('min_subtotal').value); if (min > 0) t += ' when it costs ' + money(min) + ' or more';
        var a = day($('starts_on').value), b = day($('ends_on').value);
        if (a && b) t += ', from ' + a + ' to ' + b; else if (a) t += ', from ' + a; else if (b) t += ', until ' + b;
        var m = parseInt($('max_uses').value, 10); if (m > 0) t += '. Limited to ' + m + ' ' + (m === 1 ? 'use' : 'uses') + ' in total';
        document.getElementById('vf-preview').textContent = t + '.';
    }
    f.addEventListener('input', render); f.addEventListener('change', render); render();
})();
</script>
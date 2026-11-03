@extends('portal.layout')

@section('title', 'Add Vehicle')

@section('content')
    <div class="max-w-2xl bg-white rounded-ar-lg border border-primary-100 p-6">

        @if ($errors->any())
            <div class="mb-4 rounded-ar-md bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('portal.vehicles.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                    <input type="text" name="brand" value="{{ old('brand') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ old('model') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle type</label>
                    <select name="vehicle_type_id" required
                            class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                        <option value="">Select...</option>
                        @foreach ($vehicleTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('vehicle_type_id') === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" name="color" value="{{ old('color') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <input type="number" name="year" value="{{ old('year') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plate number</label>
                <input type="text" name="plate_number" value="{{ old('plate_number') }}" required
                       class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price / day (USD)</label>
                    <input type="number" step="0.01" name="price_per_day" value="{{ old('price_per_day') }}" required
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price / hour (USD, optional)</label>
                    <input type="number" step="0.01" name="price_per_hour" value="{{ old('price_per_hour') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refundable deposit (USD, optional)</label>
                    <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount') }}"
                           class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                    <p class="text-xs text-gray-500 mt-1">Customers pay this to you at pickup and get it back on return. Leave empty for none.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photos (optional)</label>
                <input type="file" name="photos[]" id="photoInput" multiple accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-ar-md file:border-0 file:bg-primary-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-300/40">
                <div id="photoPreviews" class="mt-3 grid grid-cols-4 gap-2"></div>
                <p class="text-xs text-gray-500 mt-1">Up to {{ \App\Models\VehiclePhoto::MAX_PER_VEHICLE }} photos (JPG, PNG or WebP, 5 MB each). The first one is the cover. You can reorder and add more later. Large photos are shrunk automatically.</p>
                @error('photos') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('photos.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                <details class="mt-2 text-sm">
                    <summary class="cursor-pointer text-gray-500">Or paste an image link instead</summary>
                    <input type="url" name="photo_url" value="{{ old('photo_url') }}" placeholder="https://..."
                           class="mt-2 w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                </details>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pickup location (optional)</label>
                <input type="text" name="pick_up_location" value="{{ old('pick_up_location') }}"
                       class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Condition notes (optional)</label>
                <textarea name="condition_notes" rows="2"
                          class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">{{ old('condition_notes') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500">
                    <option value="available" selected>Available</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="retired">Retired</option>
                </select>
            </div>

            <button type="submit"
                    class="rounded-ar-md bg-primary-500 text-white font-semibold px-5 py-2.5 text-sm hover:bg-primary-700">
                Add Vehicle
            </button>
        </form>
    </div>

@include('portal.partials.photo-script')
<script>
(function () {
    var input = document.getElementById('photoInput'); if (!input) return;
    var box = document.getElementById('photoPreviews');
    input.addEventListener('change', function () {
        window.arShrinkFiles(input, function (files) {
            box.innerHTML = '';
            files.forEach(function (f, i) {
                var d = document.createElement('div');
                d.className = 'relative aspect-[4/3] overflow-hidden rounded-ar-md border border-gray-200 bg-gray-50';
                var im = document.createElement('img'); im.src = URL.createObjectURL(f); im.className = 'h-full w-full object-cover'; d.appendChild(im);
                if (i === 0) { var t = document.createElement('span'); t.textContent = 'Cover'; t.className = 'absolute left-1 top-1 rounded bg-primary-500 px-1.5 py-0.5 text-[10px] font-semibold text-white'; d.appendChild(t); }
                box.appendChild(d);
            });
        });
    });
})();
</script>
@endsection
@extends('portal.layout')

@section('title', 'Edit Vehicle')

@section('header-actions')
    <a href="{{ route('portal.vehicles.index') }}" class="text-sm font-medium text-primary-700 hover:underline">&larr; Back to vehicles</a>
@endsection

@php
    $input = 'w-full rounded-ar-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-primary-500';
    $v = fn ($key) => old($key, $vehicle->{$key});
    $currentStatus = old('status', $vehicle->status);
@endphp

@section('content')
    <div class="max-w-2xl space-y-4">

        @if ($errors->any())
            <div role="alert" class="rounded-ar-md bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if ($errors->has('status') && $activeBookings > 0)
                    <a href="{{ route('portal.bookings.index') }}" class="mt-2 inline-block font-semibold underline">Open Bookings</a>
                @endif
            </div>
        @endif

        <div class="bg-white rounded-ar-lg border border-primary-100 p-6">
            <h2 class="mb-4 text-lg font-bold text-primary-900">{{ trim($vehicle->brand . ' ' . $vehicle->model) ?: 'Vehicle' }}</h2>

            <form method="POST" action="{{ route('portal.vehicles.update', $vehicle) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                        <input type="text" name="brand" value="{{ $v('brand') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                        <input type="text" name="model" value="{{ $v('model') }}" class="{{ $input }}">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle type</label>
                        <select name="vehicle_type_id" required class="{{ $input }}">
                            @foreach ($vehicleTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('vehicle_type_id', $vehicle->vehicle_type_id) === $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="text" name="color" value="{{ $v('color') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <input type="number" name="year" value="{{ $v('year') }}" class="{{ $input }}">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plate number</label>
                        <input type="text" name="plate_number" value="{{ $v('plate_number') }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mileage (km, optional)</label>
                        <input type="number" min="0" name="mileage" value="{{ $v('mileage') }}" class="{{ $input }}">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price / day (USD)</label>
                        <input type="number" step="0.01" min="0" name="price_per_day" value="{{ $v('price_per_day') }}" required class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price / hour (USD, optional)</label>
                        <input type="number" step="0.01" min="0" name="price_per_hour" value="{{ $v('price_per_hour') }}" class="{{ $input }}">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Refundable deposit (USD, optional)</label>
                        <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ $v('deposit_amount') }}" class="{{ $input }}">
                        <p class="text-xs text-gray-500 mt-1">New prices apply to new bookings only. Bookings already made keep the price the customer agreed to.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pickup location (optional)</label>
                    <input type="text" name="pick_up_location" value="{{ $v('pick_up_location') }}" class="{{ $input }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition notes (optional)</label>
                    <textarea name="condition_notes" rows="2" class="{{ $input }}">{{ $v('condition_notes') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="{{ $input }}">
                        <option value="available" @selected($currentStatus === 'available')>Available &mdash; customers can find and book it</option>
                        <option value="maintenance" @selected($currentStatus === 'maintenance')>Hidden (maintenance) &mdash; not shown in search, cannot be booked</option>
                        <option value="retired" @selected($currentStatus === 'retired')>Retired &mdash; removed from the site for good</option>
                        @if ($vehicle->status === 'rented')
                            <option value="rented" @selected($currentStatus === 'rented')>Rented (set earlier) &mdash; cannot be booked</option>
                        @endif
                    </select>
                    @if ($activeBookings > 0)
                        <p class="text-xs text-amber-700 mt-1">This vehicle has {{ $activeBookings }} active or upcoming booking{{ $activeBookings === 1 ? '' : 's' }}. You can't hide or retire it until they are completed or cancelled.</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Hidden vehicles can be set back to Available at any time. Retired vehicles keep their booking history.</p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-ar-md bg-primary-500 text-white font-semibold px-5 py-2.5 text-sm hover:bg-primary-700">Save changes</button>
                    <a href="{{ route('portal.vehicles.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </div>

        <div class="flex items-center justify-between rounded-ar-lg border border-primary-100 bg-white p-4 text-sm">
            <span class="text-gray-600">Photos: {{ $vehicle->photos_count }} of {{ \App\Models\VehiclePhoto::MAX_PER_VEHICLE }}</span>
            <a href="{{ route('portal.vehicles.photos.index', $vehicle) }}" class="font-semibold text-primary-700 hover:underline">Manage photos</a>
        </div>
    </div>
@endsection
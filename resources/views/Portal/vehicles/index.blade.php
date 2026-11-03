@extends('portal.layout')

@section('title', 'Vehicles')

@section('header-actions')
    <a href="{{ route('portal.vehicles.create') }}"
       class="rounded-ar-md bg-primary-500 text-white text-sm font-semibold px-4 py-2 hover:bg-primary-700">
        + Add Vehicle
    </a>
@endsection

@section('content')
    <div class="bg-white rounded-ar-lg border border-primary-100 overflow-hidden">
        @if ($vehicles->isEmpty())
            <div class="p-10 text-center text-gray-500 text-sm">
                No vehicles yet.
                <a href="{{ route('portal.vehicles.create') }}" class="text-primary-700 font-medium hover:underline">Add your first one</a>.
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-primary-100/50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Photo</th>
                        <th class="px-4 py-3">Vehicle</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Plate</th>
                        <th class="px-4 py-3">Price / day</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-primary-100">
                    @foreach ($vehicles as $vehicle)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="h-12 w-16 overflow-hidden rounded-ar-md bg-gray-100 flex items-center justify-center text-gray-300">
                                    @if ($vehicle->photo_url)<img src="{{ $vehicle->photo_url }}" alt="" class="h-full w-full object-cover">@else<span class="text-[10px]">No photo</span>@endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium text-primary-900">{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $vehicle->vehicleType->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $vehicle->plate_number }}</td>
                            <td class="px-4 py-3 text-gray-600">${{ number_format($vehicle->price_per_day, 2) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusLabel = ['available' => 'Available', 'maintenance' => 'Hidden · maintenance', 'retired' => 'Retired', 'rented' => 'Rented'][$vehicle->status] ?? ucfirst($vehicle->status);
                                    $statusClass = $vehicle->status === 'available' ? 'bg-primary-100 text-primary-700' : ($vehicle->status === 'retired' ? 'bg-gray-200 text-gray-600' : 'bg-amber-100 text-amber-800');
                                @endphp
                                <span class="text-xs px-2 py-0.5 rounded-ar-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('portal.vehicles.edit', $vehicle) }}" class="mr-3 text-xs font-semibold text-primary-700 hover:underline">Edit</a>
                                <a href="{{ route('portal.vehicles.photos.index', $vehicle) }}" class="text-xs font-medium text-primary-700 hover:underline">
                                    {{ $vehicle->photos_count > 0 ? 'Manage photos (' . $vehicle->photos_count . ')' : 'Add photos' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
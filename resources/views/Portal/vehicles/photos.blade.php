@extends('portal.layout')

@section('title', 'Vehicle photos')

@section('header-actions')
    <a href="{{ route('portal.vehicles.index') }}" class="text-sm font-medium text-primary-700 hover:underline">&larr; Back to vehicles</a>
@endsection

@php
    $photos = $vehicle->photos;
    $count = $photos->count();
    $room = $max - $count;
    $name = trim($vehicle->brand . ' ' . $vehicle->model);
@endphp

@section('content')
    <div class="max-w-4xl space-y-6">
        <div>
            <h2 class="text-xl font-bold text-primary-900">{{ $name }} &middot; Photos</h2>
            <p class="text-sm text-gray-500">{{ $count }} of {{ $max }} photos. The first photo is the cover customers see in search results.</p>
        </div>

        {{-- Upload --}}
        <div class="rounded-ar-lg border border-primary-100 bg-white p-5">
            @if ($room > 0)
                <form method="POST" action="{{ route('portal.vehicles.photos.store', $vehicle) }}" enctype="multipart/form-data" id="uploadForm" class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">Add photos <span class="font-normal text-gray-500">(you can pick several at once, {{ $room }} more allowed)</span></label>
                    <input type="file" name="photos[]" id="photoInput" multiple accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-ar-md file:border-0 file:bg-primary-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-300/40">
                    <div id="photoPreviews" class="grid grid-cols-4 gap-2"></div>
                    @error('photos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-500">JPG, PNG or WebP, up to 5 MB each. Large photos are shrunk automatically.</p>
                    <button type="submit" id="uploadBtn" class="rounded-ar-md bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50">Upload</button>
                </form>
            @else
                <p class="text-sm text-gray-600">You have reached the limit of {{ $max }} photos. Delete one to add another.</p>
            @endif
        </div>

        {{-- Current photos --}}
        @if ($count === 0)
            <div class="rounded-ar-lg border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">No photos yet. Add some above.</div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                @foreach ($photos as $i => $photo)
                    <div class="overflow-hidden rounded-ar-lg border border-primary-100 bg-white">
                        <div class="relative aspect-[4/3] bg-gray-100">
                            <img src="{{ $photo->url }}" alt="Photo {{ $i + 1 }} of {{ $name }}" class="h-full w-full object-cover">
                            @if ($i === 0)
                                <span class="absolute left-2 top-2 rounded bg-primary-500 px-2 py-0.5 text-[11px] font-semibold text-white">Cover</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-2 p-2.5 text-xs">
                            @if ($i === 0)
                                <span class="text-gray-400">Cover photo</span>
                            @else
                                <form method="POST" action="{{ route('portal.vehicles.photos.cover', [$vehicle, $photo]) }}">
                                    @csrf
                                    <button class="font-medium text-primary-700 hover:underline">Make cover</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('portal.vehicles.photos.destroy', [$vehicle, $photo]) }}" onsubmit="return confirm('Delete this photo?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @include('portal.partials.photo-script')
    <script>
    (function () {
        var input = document.getElementById('photoInput'); if (!input) return;
        var box = document.getElementById('photoPreviews'), btn = document.getElementById('uploadBtn');
        input.addEventListener('change', function () {
            btn.disabled = true;
            window.arShrinkFiles(input, function (files) {
                btn.disabled = false; box.innerHTML = '';
                files.forEach(function (f) {
                    var d = document.createElement('div'); d.className = 'aspect-[4/3] overflow-hidden rounded-ar-md border border-gray-200 bg-gray-50';
                    var im = document.createElement('img'); im.src = URL.createObjectURL(f); im.className = 'h-full w-full object-cover'; d.appendChild(im); box.appendChild(d);
                });
            });
        });
        document.getElementById('uploadForm').addEventListener('submit', function () { setTimeout(function () { btn.disabled = true; btn.textContent = 'Uploading…'; }, 0); });
    })();
    </script>
@endsection
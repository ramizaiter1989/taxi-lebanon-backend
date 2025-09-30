@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
    <div id="map" style="height: 600px; width: 100%;"></div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/map.js') <!-- your script above -->
@endpush

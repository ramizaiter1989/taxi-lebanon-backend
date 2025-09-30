@extends('layouts.app')

@section('content')
<div class="container">
    <h2>My Rides</h2>

    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <tr>
            <th>ID</th>
            <th>Pickup (Lat, Lng)</th>
            <th>Destination (Lat, Lng)</th>
            <th>Status</th>
            <th>Requested At</th>
        </tr>
        @forelse($rides as $ride)
            <tr>
                <td>{{ $ride->id }}</td>
                <td>{{ $ride->current_lat }}, {{ $ride->current_lng }}</td>
                <td>{{ $ride->destination_lat }}, {{ $ride->destination_lng }}</td>
                <td>{{ ucfirst($ride->status) }}</td>
                <td>{{ $ride->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No rides found.</td>
            </tr>
        @endforelse
    </table>
</div>
@endsection

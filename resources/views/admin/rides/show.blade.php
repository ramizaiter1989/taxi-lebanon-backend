@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Ride #{{ $ride->id }}</h1>

    <p><strong>Passenger:</strong> {{ $ride->passenger->name ?? 'N/A' }}</p>
    <p><strong>Driver:</strong> {{ $ride->driver->user->name ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($ride->status) }}</p>

    <form action="{{ route('admin.rides.update', $ride->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                @foreach(['pending','ongoing','completed','cancelled'] as $status)
                    <option value="{{ $status }}" {{ $ride->status === $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Update Ride</button>
        <a href="{{ route('admin.rides.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection

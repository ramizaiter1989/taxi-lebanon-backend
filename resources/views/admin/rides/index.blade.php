@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Rides</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Passenger</th>
                <th>Driver</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rides as $ride)
            <tr>
                <td>{{ $ride->id }}</td>
                <td>{{ $ride->passenger->name ?? 'N/A' }}</td>
                <td>{{ $ride->driver->user->name ?? 'N/A' }}</td>
                <td>{{ ucfirst($ride->status) }}</td>
                <td>
                    <a href="{{ route('admin.rides.show', $ride->id) }}" class="btn btn-info btn-sm">View</a>

                    <form action="{{ route('admin.rides.destroy', $ride->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $rides->links() }}
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Passenger Profile</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card p-4">
        <form action="{{ route('passenger.updateProfile') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control">
                @error('name') <small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="mb-3">
                <label>Email (readonly)</label>
                <input type="text" value="{{ $user->email }}" class="form-control" readonly>
            </div>

            <div class="mb-3">
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                @error('phone') <small class="text-danger">{{ $message }}</small>@enderror
            </div>

            <div class="mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="male" {{ $user->gender === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ $user->gender === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Profile Photo</label>
                <input type="file" name="profile_photo" class="form-control">
                @if($user->profile_photo)
                    <img src="{{ asset($user->profile_photo) }}" alt="Profile Photo" width="100" class="mt-2">
                @endif
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>
@endsection

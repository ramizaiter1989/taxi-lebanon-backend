@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white shadow-2xl rounded-2xl">
        
        {{-- Header Bar with Action Button --}}
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
            <h1 class="text-3xl font-extrabold text-gray-800">All Users</h1>
            {{-- This button was commented out to fix the "RouteNotFoundException" for admin.users.create. Uncomment and define the route when ready. --}}
            {{-- <a href="{{ route('admin.users.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-md text-white bg-green-600 hover:bg-green-700 transition duration-150 ease-in-out">
                + New User
            </a> --}}
        </div>

        {{-- Success Message Display --}}
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-6 rounded-lg" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if($users->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354c.319-.32.684-.575 1.077-.798A9.957 9.957 0 0115 3a10 10 0 11-10 10c0-1.284.348-2.527 1.026-3.648.393-.223.758-.478 1.077-.798M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Users</h3>
                <p class="mt-1 text-sm text-gray-500">The user database is currently empty.</p>
            </div>
        @else
            <div class="p-6">
                <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="p-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th> {{-- Avatar --}}
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name (ID)</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Email</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Phone</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Gender</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Status</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Locked</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($users as $user)
                            <tr class="hover:bg-indigo-50 transition duration-150 ease-in-out">
                                {{-- Avatar --}}
                                <td class="p-4 text-sm font-medium text-gray-900">
                                    @if(isset($user->profile_photo) && $user->profile_photo)
                                        <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="h-8 w-8 rounded-full object-cover">
                                    @else
                                        {{-- Default placeholder icon (User SVG) --}}
                                        <svg class="h-8 w-8 text-gray-400 rounded-full bg-gray-100 p-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    @endif
                                </td>
                                
                                {{-- Name & ID --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    <div class="flex flex-col items-start">
                                        {{ $user->name }}
                                        <span class="text-xs text-gray-400">#{{ $user->id }}</span>
                                    </div>
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">{{ $user->email }}</td>
                                
                                {{-- Phone --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $user->phone ?? 'N/A' }}</td>

                                {{-- Gender --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden xl:table-cell">{{ ucfirst($user->gender ?? 'N/A') }}</td>
                                
                                {{-- Role --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                    @php
                                        $roleClass = [
                                            'admin' => 'bg-red-100 text-red-800',
                                            'driver' => 'bg-blue-100 text-blue-800',
                                            'passenger' => 'bg-green-100 text-green-800',
                                        ];
                                        $class = $roleClass[strtolower($user->role)] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full {{ $class }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center hidden sm:table-cell">
                                    @php
                                        $status = strtolower($user->status ?? 'unknown');
                                        $statusClass = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'inactive' => 'bg-yellow-100 text-yellow-800',
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            'unknown' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $status_class = $statusClass[$status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-medium rounded-full {{ $status_class }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>

                                {{-- Locked Status (Icon) --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center hidden lg:table-cell">
                                    @if($user->is_locked)
                                        <svg class="h-5 w-5 text-red-500 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" title="Locked">
                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2h2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2h2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-green-500 mx-auto" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" title="Unlocked">
                                            <path d="M10 2a5 5 0 00-5 5v2H4a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-1V7a5 5 0 00-5-5zM9 7v2h2V7a1 1 0 00-2 0z" />
                                        </svg>
                                    @endif
                                </td>
                                
                                {{-- Actions --}}
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('admin.users.edit', $user->id) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out">
                                            Edit
                                        </a>

                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 transition duration-150 ease-in-out">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Pagination Links --}}
            <div class="mt-6 px-6 pb-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

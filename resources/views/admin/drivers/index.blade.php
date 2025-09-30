@extends('layouts.app') {{-- Assuming you have an admin layout --}}

@section('content')
<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-2">All Drivers</h1>

        {{-- Success/Error Message Placeholder (You should add session message display here if needed) --}}
        {{-- @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif --}}

        @if($drivers->isEmpty())
            <div class="text-center py-10 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.727A8 8 0 016.343 6.273A8 8 0 0117.657 16.727zm0 0l-1.35 1.35m1.35-1.35a8 8 0 11-11.314-11.314 8 8 0 0111.314 11.314z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Drivers</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new driver profile.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver Name</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Contact</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Vehicle</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lock Status</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($drivers as $driver)
                            <tr class="hover:bg-indigo-50 transition duration-150 ease-in-out">
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $driver->id }}</td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $driver->user->name ?? 'N/A' }}
                                    <div class="text-xs text-gray-500 truncate sm:hidden">{{ $driver->user->phone ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">
                                    {{ $driver->user->email ?? 'N/A' }}
                                    <div class="text-xs text-gray-400">{{ $driver->user->phone ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                    {{ $driver->vehicle_type }} ({{ $driver->vehicle_number ?? 'N/A' }})
                                    <div class="text-xs text-gray-400">License: {{ $driver->license_number ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $driver->rating }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                    @if($driver->availability_status)
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Unavailable
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm text-center">
                                    @if($driver->user && $driver->user->is_locked)
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-600 text-white">
                                            Locked
                                        </span>
                                    @else
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('admin.drivers.edit', $driver) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out">
                                            Edit
                                        </a>
                                        
                                        @if($driver->user)
                                            {{-- Lock/Unlock Button --}}
                                            <form action="{{ route('admin.drivers.toggleLock', $driver->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" 
                                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white {{ $driver->user->is_locked ? 'bg-green-500 hover:bg-green-600' : 'bg-yellow-500 hover:bg-yellow-600' }} transition duration-150 ease-in-out">
                                                    {{ $driver->user->is_locked ? 'Unlock' : 'Lock' }}
                                                </button>
                                            </form>
                                        @endif
            
                                        {{-- Delete Button --}}
                                        <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 transition duration-150 ease-in-out">
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
            {{-- Assuming $drivers is a paginator instance --}}
            {{-- <div class="mt-4">
                {{ $drivers->links() }}
            </div> --}}
        @endif
    </div>
</div>
@endsection

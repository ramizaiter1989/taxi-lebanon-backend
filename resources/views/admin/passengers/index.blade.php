@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white shadow-xl rounded-2xl p-6 my-8">
        <h2 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-2">All Passengers</h2>

        {{-- Success Message Display --}}
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#ID</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Email</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Phone</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Registered At</th>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach($passengers as $passenger)
                    <tr class="hover:bg-indigo-50 transition duration-150 ease-in-out">
                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $passenger->id }}</td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $passenger->name }}
                            <div class="text-xs text-gray-500 truncate sm:hidden">{{ $passenger->phone }}</div>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">{{ $passenger->email }}</td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">{{ $passenger->phone }}</td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $passenger->gender === 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                {{ ucfirst($passenger->gender) }}
                            </span>
                        </td>
                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">{{ $passenger->created_at->format('Y-m-d') }}</td>
                        
                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('admin.passengers.edit', $passenger->id) }}" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out">
                                    Edit
                                </a>
                                
                                {{-- Lock/Unlock Button --}}
                                @if(!$passenger->is_locked)
                                    <form action="{{ route('admin.passengers.lock', $passenger->id) }}" method="POST" class="inline-block">
                                        @csrf @method('PATCH')
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-yellow-500 hover:bg-yellow-600 transition duration-150 ease-in-out">
                                            Lock
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.passengers.unlock', $passenger->id) }}" method="POST" class="inline-block">
                                        @csrf @method('PATCH')
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-green-500 hover:bg-green-600 transition duration-150 ease-in-out">
                                            Unlock
                                        </button>
                                    </form>
                                @endif

                                {{-- Delete Button --}}
                                <form action="{{ route('admin.passengers.destroy', $passenger->id) }}" method="POST" class="inline-block">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 transition duration-150 ease-in-out">
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

        {{-- Pagination Links --}}
        <div class="mt-4">
            {{ $passengers->links() }}
        </div>
    </div>
</div>
@endsection

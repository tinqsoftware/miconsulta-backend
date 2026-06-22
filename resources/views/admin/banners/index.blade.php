@extends('admin.layout')

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">Banners Publicitarios</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Gestiona los banners que se muestran en la app móvil.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            + Añadir Banner
        </a>
    </div>
    
    <div class="border-t border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enlace</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Acciones</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($banners as $banner)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="{{ $banner->imagen_url }}" alt="Banner" class="h-16 w-auto object-cover rounded border">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $banner->titulo }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                        @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" target="_blank" class="hover:underline">{{ $banner->link_url }}</a>
                        @else
                            <span class="text-gray-400">Sin enlace</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($banner->estado)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $banner->created_at->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('admin.banners.edit', $banner->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                        <form action="{{ route('admin.banners.estado', $banner->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-{{ $banner->estado ? 'red' : 'green' }}-600 hover:text-{{ $banner->estado ? 'red' : 'green' }}-900">
                                {{ $banner->estado ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No hay banners registrados en el sistema.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 sm:px-6">
        {{ $banners->links() }}
    </div>
</div>
@endsection

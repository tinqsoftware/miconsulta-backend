@extends('admin.layout')

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg max-w-3xl mx-auto">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Editar Banner</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Modifica la información o imagen del banner publicitario.</p>
    </div>

    <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6">
        @csrf
        <div class="grid grid-cols-1 gap-6">
            
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título / Descripción interna</label>
                <input type="text" name="titulo" id="titulo" value="{{ $banner->titulo }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div>
                <label for="imagen" class="block text-sm font-medium text-gray-700">Imagen del Banner (Dejar en blanco para mantener la actual)</label>
                <div class="mt-2 mb-4">
                    <img src="{{ $banner->imagen_url }}" alt="Banner actual" class="h-32 object-contain border rounded">
                </div>
                <input type="file" name="imagen" id="imagen" accept="image/*" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">Recomendado: 1200x300 px, formatos JPG/PNG.</p>
            </div>

            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700">Enlace (Opcional)</label>
                <input type="text" name="link_url" id="link_url" value="{{ $banner->link_url }}" placeholder="Ej: /sintomas, miconsulta://sintomas, https://google.com" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="estado" name="estado" type="checkbox" {{ $banner->estado ? 'checked' : '' }} class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="estado" class="font-medium text-gray-700">Activo</label>
                    <p class="text-gray-500">El banner será visible en la app inmediatamente.</p>
                </div>
            </div>

        </div>

        <div class="mt-8 pt-5 border-t border-gray-200 flex justify-end">
            <a href="{{ route('admin.banners') }}" class="bg-white border border-gray-300 rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancelar
            </a>
            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection

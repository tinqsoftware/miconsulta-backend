@extends('admin.layout')

@section('content')
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
        <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Configuraciones Globales de la App
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Edita los enlaces dinámicos y ajustes generales de la aplicación móvil.
            </p>
        </div>
    </div>
    
    <div class="px-4 py-5 sm:p-6">
        <form action="{{ route('admin.configuraciones.update') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label for="link_telemedicina" class="block text-sm font-medium text-gray-700">Enlace de Botón "Telemedicina"</label>
                    <div class="mt-1">
                        <input type="text" name="link_telemedicina" id="link_telemedicina" value="{{ old('link_telemedicina', $config['link_telemedicina']->valor ?? '') }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Ej: /sintomas, miconsulta://sintomas, https://google.com">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $config['link_telemedicina']->descripcion ?? 'Enlace dinámico para el botón de Próximas Citas - Telemedicina' }}
                    </p>
                    @error('link_telemedicina')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 pt-5">
                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

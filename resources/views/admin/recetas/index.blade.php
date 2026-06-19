@extends('admin.layout')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Directorio de Recetas</h1>
        <a href="{{ route('admin.recetas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            + Emitir Receta
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($recetas as $receta)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-sm font-medium text-blue-600 truncate">
                                Receta: {{ $receta->numero_receta }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Paciente: {{ $receta->paciente->nombres }} {{ $receta->paciente->apellido_paterno }}
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex flex-col items-end">
                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                {{ ucfirst($receta->estado) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 font-bold">
                                Emitida: {{ $receta->fecha_emision }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500">
                                Emitido por: Dr. {{ $receta->profesional->apellidos }} ({{ $receta->especialidad->nombre }})
                            </p>
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        <div class="px-4 py-3 bg-gray-50">
            {{ $recetas->links() }}
        </div>
    </div>
</div>
@endsection

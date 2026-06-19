@extends('admin.layout')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Directorio de Pacientes</h1>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($pacientes as $paciente)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-sm font-medium text-blue-600 truncate">
                                {{ $paciente->nombres }} {{ $paciente->apellido_paterno }} {{ $paciente->apellido_materno }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                DNI: {{ $paciente->usuario->dni }} | Cel: {{ $paciente->celular }}
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex">
                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Activo
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500">
                                IPRESS: {{ $paciente->ipress->nombre ?? 'No asignada' }}
                            </p>
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
        <div class="px-4 py-3 bg-gray-50">
            {{ $pacientes->links() }}
        </div>
    </div>
</div>
@endsection

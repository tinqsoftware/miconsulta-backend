@extends('admin.layout')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Panel de Resumen</h1>
        <form method="POST" action="{{ route('admin.reset.db') }}" onsubmit="return confirm('¿Estás seguro de que deseas resetear la Base de Datos a su estado original? Toda la información nueva se perderá.');">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">
                ⚠️ Resetear Base de Datos
            </button>
        </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pacientes Registrados</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $totalPacientes }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <a href="{{ route('admin.pacientes') }}" class="text-sm text-blue-700 hover:text-blue-900">Ver pacientes</a>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Citas Generadas</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $totalCitas }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <a href="{{ route('admin.citas') }}" class="text-sm text-blue-700 hover:text-blue-900">Ver citas</a>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Citas para Hoy</dt>
                            <dd class="text-3xl font-semibold text-green-600">{{ $citasHoy }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

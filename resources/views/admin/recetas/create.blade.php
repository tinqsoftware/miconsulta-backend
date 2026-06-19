@extends('admin.layout')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Emitir Nueva Receta</h1>
        <a href="{{ route('admin.recetas') }}" class="text-gray-500 hover:text-gray-700">Volver</a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md p-6">
        <form method="POST" action="{{ route('admin.recetas.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Paciente</label>
                    <select name="id_paciente" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Seleccione un paciente</option>
                        @foreach($pacientes as $paciente)
                            <option value="{{ $paciente->id }}">{{ $paciente->nombres }} {{ $paciente->apellido_paterno }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Profesional Emisor</label>
                    <select name="id_profesional" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Seleccione doctor</option>
                        @foreach($profesionales as $profesional)
                            <option value="{{ $profesional->id }}">Dr. {{ $profesional->apellidos }} - {{ $profesional->nombres }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Especialidad</label>
                    <select name="id_especialidad" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Seleccione especialidad</option>
                        @foreach($especialidades as $especialidad)
                            <option value="{{ $especialidad->id }}">{{ $especialidad->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">IPRESS Emisora</label>
                    <select name="id_ipress" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Seleccione hospital/clínica</option>
                        @foreach($ipress as $ip)
                            <option value="{{ $ip->id }}">{{ $ip->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Acto Médico (Descripción)</label>
                    <input type="text" name="acto_medico" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Ej. Consulta externa por dolor de cabeza" required>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                    Guardar Receta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

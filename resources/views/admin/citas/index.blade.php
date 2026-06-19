@extends('admin.layout')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Gestión de Citas</h1>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($citas as $cita)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-sm font-medium text-blue-600 truncate">
                                {{ $cita->paciente->nombres }} {{ $cita->paciente->apellido_paterno }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ ucfirst($cita->tipo_cita) }} - Dr. {{ $cita->profesional->apellidos }}
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex flex-col items-end">
                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($cita->estado == 'programada' || $cita->estado == 'confirmada') bg-green-100 text-green-800 
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($cita->estado) }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 font-bold">
                                {{ $cita->fecha }} {{ \Carbon\Carbon::parse($cita->hora)->format('g:i A') }}
                            </p>
                            <div class="mt-2 flex space-x-2">
                                @if($cita->estado != 'completada' && $cita->estado != 'cancelada')
                                <form method="POST" action="{{ route('admin.citas.estado', $cita->id) }}">
                                    @csrf
                                    <input type="hidden" name="estado" value="completada">
                                    <button type="submit" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">✔ Completar</button>
                                </form>
                                <form method="POST" action="{{ route('admin.citas.estado', $cita->id) }}">
                                    @csrf
                                    <input type="hidden" name="estado" value="cancelada">
                                    <button type="submit" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">✖ Cancelar</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($cita->motivo_consulta)
                    <div class="mt-2 text-sm text-gray-600 italic">
                        "{{ $cita->motivo_consulta }}"
                    </div>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        <div class="px-4 py-3 bg-gray-50">
            {{ $citas->links() }}
        </div>
    </div>
</div>
@endsection

<?php

return [
    'modo_prueba' => (bool) env('CITAS_MODO_PRUEBA', false),
    'dias_agenda_demo' => (int) env('CITAS_DIAS_AGENDA_DEMO', 14),
    'hora_inicio_demo' => 7,
    'hora_fin_demo' => 20,
    'duracion_slot_demo' => 15,
];

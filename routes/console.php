<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\AgendaDemoService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('agenda:demo', function (AgendaDemoService $agenda): void {
    $creados = 0;
    foreach (['presencial', 'telemedicina'] as $tipoCita) {
        $creados += $agenda->asegurar($tipoCita, force: true);
    }

    $this->info("Agenda demo lista. Horarios creados: {$creados}.");
})->purpose('Prepara una agenda idempotente para pruebas locales');

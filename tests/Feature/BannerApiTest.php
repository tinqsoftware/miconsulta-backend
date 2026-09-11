<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BannerApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
            $table->string('imagen_url');
            $table->string('imagen_popup_url')->nullable();
            $table->string('link_url')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function test_active_banners_return_popup_image_and_null_is_supported(): void
    {
        DB::table('banners')->insert([
            [
                'titulo' => 'Con popup',
                'imagen_url' => '/uploads/banners/rectangular.jpg',
                'imagen_popup_url' => '/uploads/banners/cuadrada.jpg',
                'link_url' => ' /sintomas ',
                'estado' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'titulo' => 'Banner antiguo',
                'imagen_url' => '/uploads/banners/legacy.jpg',
                'imagen_popup_url' => null,
                'link_url' => '',
                'estado' => true,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
        ]);

        $response = $this->getJson('/api/v1/banners/activos');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.imagen_popup_url', url('/uploads/banners/cuadrada.jpg'))
            ->assertJsonPath('data.0.link_url', '/sintomas')
            ->assertJsonPath('data.1.imagen_popup_url', null)
            ->assertJsonPath('data.1.link_url', null);
    }
}

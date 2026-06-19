<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receta;
use Barryvdh\DomPDF\Facade\Pdf;

class RecetaController extends Controller
{
    public function misRecetas(Request $request)
    {
        $paciente = $request->user()->paciente;

        if (!$paciente) {
            return response()->json(['data' => []]);
        }

        $recetas = Receta::with(['profesional', 'ipress', 'especialidad', 'medicamentos'])
            ->where('id_paciente', $paciente->id)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        return response()->json(['data' => $recetas]);
    }

    public function descargarPdf(Request $request, $id)
    {
        $paciente = $request->user()->paciente;
        $receta = Receta::with(['profesional', 'ipress', 'especialidad', 'medicamentos'])
            ->where('id', $id)
            ->where('id_paciente', $paciente->id)
            ->firstOrFail();

        // En MVP, generamos un HTML simple en memoria y lo pasamos al PDF
        $html = '
        <html>
        <head>
            <style>
                body { font-family: sans-serif; }
                .header { text-align: center; border-bottom: 2px solid #1A3C8F; padding-bottom: 10px; margin-bottom: 20px;}
                .title { color: #1A3C8F; font-size: 24px; font-weight: bold; }
                .info { margin-bottom: 20px; }
                .info p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #1A3C8F; color: white; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666;}
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">EsSalud - Receta Médica</div>
                <p>N° de Receta: ' . $receta->numero_receta . '</p>
            </div>
            <div class="info">
                <p><strong>Paciente:</strong> ' . $paciente->nombres . ' ' . $paciente->apellido_paterno . '</p>
                <p><strong>DNI:</strong> ' . $request->user()->dni . '</p>
                <p><strong>IPRESS:</strong> ' . $receta->ipress->nombre . '</p>
                <p><strong>Profesional:</strong> Dr. ' . $receta->profesional->nombres . ' ' . $receta->profesional->apellidos . '</p>
                <p><strong>Fecha Emisión:</strong> ' . $receta->fecha_emision . '</p>
                <p><strong>Vigencia hasta:</strong> ' . $receta->fecha_vigencia . '</p>
            </div>
            <h3>Medicamentos Prescritos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Cantidad</th>
                        <th>Indicaciones</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($receta->medicamentos as $med) {
            $nombre = $med->medicamento->nombre_generico . ' ' . $med->medicamento->concentracion;
            $html .= '<tr>
                        <td>' . $nombre . '</td>
                        <td>' . $med->cantidad . ' ' . $med->unidad_formato . '</td>
                        <td>' . $med->indicacion . '</td>
                      </tr>';
        }

        $html .= '</tbody>
            </table>
            <div class="footer">
                <p>Documento electrónico generado por MiConsulta MVP</p>
                <!-- Placeholder for QR -->
                <p>[ Código QR de validación ]</p>
            </div>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($html);
        return $pdf->download('receta_'.$receta->numero_receta.'.pdf');
    }
}

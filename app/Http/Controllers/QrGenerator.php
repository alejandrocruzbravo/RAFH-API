<?php


namespace App\Http\Controllers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QrGenerator extends Controller
{

    public function generarQrPdf($cantidad)
    {
        // Validar manualmente
        $validator = \Validator::make(['cantidad' => $cantidad], [
            'cantidad' => 'required|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Cantidad inválida. Debe ser entre 1 y 100.'
            ], 400);
        }

        // Si pasa la validación, generar los QR
        $qrCodes = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $qrCodes[] = QrCode::size(150)->generate('Codigo-' . $i);
        }


        $html = view('qr-pdf', compact('qrCodes'))->render();
        $pdf = Pdf::loadHTML($html);
        // return $pdf->download('codigos_qr.pdf');
        return $html;
    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class FacturacionController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function index(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);

        $baseSql = "SELECT s.vsolNroSol as id, 
                           s.vsolNroSol as nro_factura, 
                           s.vsolFecSol as fecha, 
                           COALESCE(s.vsolDestin, u.vuntNombre, 'HOSPITAL GENERAL') as razon_social,
                           '102938475' as nit, 
                           CAST(s.vsolCantml * 2.5 AS decimal(10,2)) as monto_total, 
                           '8A-B2-C3-D4' as codigo_control
                    FROM vamSolicit s
                    LEFT JOIN vamUniTran u ON s.vuntCodUnt = u.vuntCodUnt";

        $result = $this->db->paginate($baseSql, [], $page, $perPage, "s.vsolNroSol DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function createInvoice(Request $request)
    {
        $razonSocial = trim($request->input('razon_social', 'CLIENTE PARTICULAR'));
        $nit = trim($request->input('nit', '0'));
        $total = (float)$request->input('total', 250.0);

        $nroFactura = rand(5000, 9999);
        $codigoControl = strtoupper(substr(md5(uniqid()), 0, 2) . '-' . substr(md5(uniqid()), 2, 2) . '-' . substr(md5(uniqid()), 4, 2) . '-' . substr(md5(uniqid()), 6, 2));

        $qrData = "458291024|{$nroFactura}|781923019001|" . date('d/m/Y') . "|{$total}|{$total}|{$codigoControl}|{$nit}|0|0|0|0";

        return response()->json([
            'success' => true,
            'message' => 'Factura / Comprobante emitido exitosamente',
            'factura' => [
                'nro_factura' => $nroFactura,
                'razon_social' => $razonSocial,
                'nit' => $nit,
                'fecha' => date('Y-m-d H:i'),
                'total' => $total,
                'codigo_control' => $codigoControl,
                'qr_data' => $qrData
            ]
        ]);
    }
}

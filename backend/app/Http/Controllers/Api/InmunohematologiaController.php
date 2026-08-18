<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class InmunohematologiaController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getPendingRequests()
    {
        $sql = "SELECT TOP 50 s.vsolNroSol as id, 
                       s.vsolFecSol as fecha, 
                       s.vsolDestin as paciente, 
                       p.vproDescri as producto, 
                       u.vuntNombre as servicio_transfusion, 
                       s.vsolCantml as volumen, 
                       s.vsolUrgent as urgente
                FROM vamSolicit s
                LEFT JOIN vamProduct p ON s.vproNroPro = p.vproNroPro
                LEFT JOIN vamUniTran u ON s.vuntCodUnt = u.vuntCodUnt
                ORDER BY s.vsolNroSol DESC";

        $requests = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'result' => [
                'data' => $requests
            ]
        ]);
    }

    public function savePcc(Request $request)
    {
        $solId = (int)$request->input('solicitud_id');
        $unitId = (int)$request->input('unit_id');
        $mayorComp = (bool)$request->input('pcc_mayor_compatible', true);
        $menorComp = (bool)$request->input('pcc_menor_compatible', true);

        $isCompatible = $mayorComp && $menorComp;

        if ($isCompatible) {
            $this->db->update("UPDATE vamSolicit SET vsolPCoomb = 'N', vsolProtra = '1' WHERE vsolNroSol = ?", [$solId]);
        }

        return response()->json([
            'success' => true,
            'compatible' => $isCompatible,
            'message' => $isCompatible ? 'PCC COMPATIBLE. Unidad asignada y reservada.' : 'PCC INCOMPATIBLE. Se rechaza la unidad para este receptor.'
        ]);
    }
}

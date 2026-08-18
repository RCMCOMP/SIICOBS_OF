<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class PortalClinicasController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getMyRequests(Request $request)
    {
        $sql = "SELECT TOP 50 s.vsolNroSol as id, 
                       s.vsolFecSol as fecha, 
                       s.vsolDestin as paciente, 
                       p.vproDescri as producto, 
                       'A+' as grupo_solicitado, 
                       s.vsolCantml as cantidad, 
                       'Cirugía / Anemia' as diagnostico, 
                       CASE WHEN s.vsolProtra = '1' THEN 'DESPACHADO' ELSE 'PENDIENTE' END as estado
                FROM vamSolicit s
                LEFT JOIN vamProduct p ON s.vproNroPro = p.vproNroPro
                ORDER BY s.vsolNroSol DESC";

        $requests = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    public function createRequest(Request $request)
    {
        $prodId = (int)$request->input('producto_id', 3);
        $grpId = (int)$request->input('grupo_sanguineo_id', 1);
        $qty = (int)$request->input('cantidad', 1);
        $paciente = trim($request->input('nombre_paciente', ''));
        $diagnostico = trim($request->input('diagnostico', ''));
        $prioridad = trim($request->input('prioridad', 'URGENTE'));

        $maxRow = $this->db->selectOne("SELECT MAX(vsolNroSol) as max_id FROM vamSolicit");
        $nextSol = (int)($maxRow['max_id'] ?? 0) + 1;

        $sql = "INSERT INTO vamSolicit (vsolNroSol, vuntCodUnt, vpacCodPac, vsolFecSol, vproNroPro, vsolCantml, vsolUrgent, vsolDestin, vsolProtra)
                VALUES (?, 1, 1, GETDATE(), ?, ?, ?, ?, '0')";

        $this->db->insert($sql, [$nextSol, $prodId, $qty, $prioridad === 'URGENTE' || $prioridad === 'EMERGENCIA' ? 1 : 0, $paciente]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada exitosamente al Banco de Sangre con N° #' . $nextSol,
            'solicitud_id' => $nextSol
        ]);
    }
}

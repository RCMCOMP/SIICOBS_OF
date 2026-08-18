<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class DespachoController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getAvailableUnits(Request $request)
    {
        $prodId = (int)$request->input('producto_id', 3);
        $grpId = (int)$request->input('grupo_id', 0);

        $sql = "SELECT TOP 50 f.vfraNroFra as id, p.vproDescri as producto,
                       g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                       f.vfraFecVen as fecha_vencimiento,
                       f.vfraCantiml as volumen_ml
                FROM vamFraccio f
                LEFT JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                LEFT JOIN vamGrupSan g ON f.vgrsCodGrs = g.vgrsCodGrs
                WHERE f.vfraEstFra = '1' AND f.vproNroPro = ?";

        $params = [$prodId];
        if ($grpId > 0) {
            $sql .= " AND f.vgrsCodGrs = ?";
            $params[] = $grpId;
        }

        $sql .= " ORDER BY f.vfraFecVen ASC";
        $units = $this->db->select($sql, $params);

        return response()->json([
            'success' => true,
            'units' => $units
        ]);
    }

    public function getTransfusionCenters()
    {
        $sql = "SELECT vuntCodUnt as id, vuntNombre as nombre, vuntDirecc as direccion, vuntTelefo as telefono FROM vamUniTran ORDER BY vuntNombre ASC";
        $centers = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'data' => $centers
        ]);
    }

    public function deliverUnits(Request $request)
    {
        $untId = (int)$request->input('servicio_transfusion_id');
        $paciente = trim($request->input('nombre_paciente', ''));
        $unitIds = (array)$request->input('unit_ids', []);

        foreach ($unitIds as $uId) {
            $this->db->update("UPDATE vamFraccio SET vfraEstFra = '3' WHERE vfraNroFra = ?", [(int)$uId]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Despacho registrado correctamente. ' . count($unitIds) . ' unidades entregadas a ' . $paciente
        ]);
    }
}

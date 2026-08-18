<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class AlmacenController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getInventory(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);
        $estado = $request->input('estado_almacen', '1');

        $baseSql = "SELECT f.vfraNroFra as id, 
                           p.vproDescri as producto,
                           g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                           f.vfraFecFra as fecha_fraccionamiento, 
                           f.vfraFecVen as fecha_vencimiento,
                           f.vfraCantiml as volumen_ml, 
                           f.vfraEstFra as estado_almacen,
                           CASE 
                             WHEN f.vfraFecVen < GETDATE() THEN 'VENCIDO'
                             WHEN DATEDIFF(day, GETDATE(), f.vfraFecVen) <= 3 THEN 'POR_VENCER'
                             ELSE 'VIGENTE'
                           END as estado_vigencia
                    FROM vamFraccio f
                    LEFT JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                    LEFT JOIN vamGrupSan g ON f.vgrsCodGrs = g.vgrsCodGrs";

        $params = [];
        if ($estado !== 'all') {
            $baseSql .= " WHERE f.vfraEstFra = ?";
            $params = [$estado];
        }

        $result = $this->db->paginate($baseSql, $params, $page, $perPage, "f.vfraNroFra DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function releaseUnit(Request $request)
    {
        $unitId = (int)$request->input('unit_id');
        $this->db->update("UPDATE vamFraccio SET vfraEstFra = '1' WHERE vfraNroFra = ?", [$unitId]);

        return response()->json([
            'success' => true,
            'message' => 'Unidad liberada exitosamente para uso clínico.'
        ]);
    }
}

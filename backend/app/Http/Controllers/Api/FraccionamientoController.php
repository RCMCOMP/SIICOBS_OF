<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class FraccionamientoController extends Controller
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

        $baseSql = "SELECT f.vfraNroFra as id, 
                           f.vexdNroExd as extraccion_id, 
                           p.vproDescri as producto,
                           g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                           f.vfraFecFra as fecha_fraccionamiento, 
                           f.vfraFecVen as fecha_vencimiento,
                           f.vfraCantiml as volumen_ml, 
                           f.vfraEstFra as estado_almacen
                    FROM vamFraccio f
                    LEFT JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                    LEFT JOIN vamGrupSan g ON f.vgrsCodGrs = g.vgrsCodGrs";

        $result = $this->db->paginate($baseSql, [], $page, $perPage, "f.vfraNroFra DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function fractionate(Request $request)
    {
        $extId = (int)$request->input('extraccion_id');
        $components = (array)$request->input('components', []);

        $ext = $this->db->selectOne("SELECT vexdNroExd, vgrsCodGrs FROM vamExtDona WHERE vexdNroExd = ?", [$extId]);
        if (!$ext) {
            return response()->json(['success' => false, 'message' => 'Número de extracción no encontrado'], 404);
        }

        $grpId = $ext['vgrsCodGrs'] ?? 1;
        $createdUnits = [];

        foreach ($components as $c) {
            $prodId = (int)$c['producto_id'];
            $vol = (int)($c['volumen_ml'] ?? 250);
            $days = (int)($c['dias_vencimiento'] ?? 35);

            $maxRow = $this->db->selectOne("SELECT MAX(vfraNroFra) as max_id FROM vamFraccio");
            $nextFra = (int)($maxRow['max_id'] ?? 0) + 1;

            $sql = "INSERT INTO vamFraccio (vfraNroFra, vexdNroExd, vproNroPro, vgrsCodGrs, vfraFecFra, vfraFecVen, vfraCantiml, vfraEstFra, vresCodRes)
                    VALUES (?, ?, ?, ?, GETDATE(), DATEADD(day, ?, GETDATE()), ?, '0', 1)";

            $this->db->insert($sql, [$nextFra, $extId, $prodId, $grpId, $days, $vol]);
            $createdUnits[] = $nextFra;
        }

        return response()->json([
            'success' => true,
            'message' => 'Lote de hemocomponentes generado exitosamente en CUARENTENA',
            'units' => $createdUnits
        ]);
    }
}

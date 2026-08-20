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

        $sql = "SELECT f.vfraNroFra as id, p.vproDescri as producto,
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

        $sql .= " ORDER BY f.vfraFecVen ASC LIMIT 50";
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

    public function getNotaRemision($codigo)
    {
        $nroVen = (int)$codigo;
        
        $sqlCabecera = "SELECT s.vvenNroVen, s.vvenSolUnt as nro_nota, s.vvenFecSol as fecha,
                               u.vuntNombre as hospital, s.vnombrepaciente as paciente,
                               s.vvenCINomRec as ci_receptor, s.vvenNomRec as recibe,
                               s.vvenTotGrl as total, s.vvenDiag as diagnostico,
                               r.vresNombre as responsable
                        FROM vamVenSoli s
                        LEFT JOIN vamUniTran u ON s.vuntCodUnt = u.vuntCodUnt
                        LEFT JOIN vamRespons r ON s.vresCodRes = r.vresCodRes
                        WHERE s.vvenNroVen = ?";
        
        $nota = $this->db->selectOne($sqlCabecera, [$nroVen]);
        
        if (!$nota) {
            return response()->json([
                'success' => false,
                'message' => 'Código de Despacho no encontrado.'
            ], 404);
        }
        
        $sqlDetalle = "SELECT h.vvenSecHemo, p.vproDescri as producto,
                              g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                              h.vexdTubula as tubuladura, h.vexdNroExd as codigo_extraccion,
                              h.vproPrecio as precio
                       FROM vamVenHemo h
                       LEFT JOIN vamProduct p ON h.vproNroPro = p.vproNroPro
                       LEFT JOIN vamProdGrs pg ON h.vprgCodPrg = pg.vprgCodPrg
                       LEFT JOIN vamGrupSan g ON pg.vgrsCodGrs = g.vgrsCodGrs
                       WHERE h.vvenNroVen = ?
                       ORDER BY h.vvenSecHemo ASC";
        
        $items = $this->db->select($sqlDetalle, [$nroVen]);
        
        return response()->json([
            'success' => true,
            'nota' => $nota,
            'items' => $items
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class ReportesController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getSnisData()
    {
        $prodSql = "SELECT p.vproDescri as producto, COUNT(*) as total
                    FROM vamFraccio f
                    INNER JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                    GROUP BY p.vproDescri
                    ORDER BY total DESC";
        $prodData = $this->db->select($prodSql);

        $groupSql = "SELECT g.vgrsGruABO + g.vgrsTipoRH as grupo, COUNT(*) as total
                     FROM vamExtDona e
                     INNER JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                     GROUP BY g.vgrsGruABO, g.vgrsTipoRH
                     ORDER BY total DESC";
        $groupData = $this->db->select($groupSql);

        return response()->json([
            'success' => true,
            'report' => [
                'produccion_hemocomponentes' => $prodData,
                'distribucion_grupos' => $groupData,
            ]
        ]);
    }

    public function getTraceability($code)
    {
        $extId = (int)$code;

        $extSql = "SELECT e.vexdNroExd, 
                          e.vexdFecIni as vexdFecExt, 
                          e.vexdCantml as vexdVolExt, 
                          d.vdonNombre + ' ' + d.vdonPatern as donante, 
                          d.vdonDocIde, 
                          g.vgrsGruABO + g.vgrsTipoRH as grupo_donante,
                          res.vresNombre as flebotomista
                   FROM vamExtDona e
                   LEFT JOIN vamScreeni s ON e.vscrNroScr = s.vscrNroScr
                   LEFT JOIN vamDonante d ON s.vdonCodDon = d.vdonCodDon
                   LEFT JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                   LEFT JOIN vamRespons res ON e.vexdResExd = res.vresCodRes
                   WHERE e.vexdNroExd = ?";
        
        $ext = $this->db->selectOne($extSql, [$extId]);

        if (!$ext) {
            return response()->json(['success' => false, 'message' => 'Código de extracción no encontrado'], 404);
        }

        $fracSql = "SELECT f.vfraNroFra, p.vproDescri as producto, f.vfraFecFra, f.vfraFecVen, f.vfraCantiml as vfraVolumen, f.vfraEstFra as vpalTipAlm
                    FROM vamFraccio f
                    LEFT JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                    WHERE f.vexdNroExd = ?";
        $fractions = $this->db->select($fracSql, [$extId]);

        return response()->json([
            'success' => true,
            'donante_extraccion' => $ext,
            'hemocomponentes_derivados' => $fractions
        ]);
    }
}

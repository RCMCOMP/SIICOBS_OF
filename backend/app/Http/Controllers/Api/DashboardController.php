<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class DashboardController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function stock()
    {
        $sql = "SELECT p.vproNroPro as id, p.vproDescri as producto,
                       g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                       COUNT(f.vfraNroFra) as cantidad
                FROM vamFraccio f
                INNER JOIN vamProduct p ON f.vproNroPro = p.vproNroPro
                INNER JOIN vamGrupSan g ON f.vgrsCodGrs = g.vgrsCodGrs
                WHERE f.vfraEstFra = '1'
                GROUP BY p.vproNroPro, p.vproDescri, g.vgrsGruABO, g.vgrsTipoRH
                ORDER BY p.vproNroPro ASC";

        $rows = $this->db->select($sql);

        // Agrupar datos en formato matriz
        $matrix = [];
        $groups = [];

        foreach ($rows as $r) {
            $prodId = $r['id'];
            $prodName = $r['producto'];
            $grp = trim($r['grupo_sanguineo']);
            $qty = (int)$r['cantidad'];

            if (!in_array($grp, $groups)) {
                $groups[] = $grp;
            }

            if (!isset($matrix[$prodId])) {
                $matrix[$prodId] = [
                    'id' => $prodId,
                    'producto' => $prodName,
                    'total' => 0
                ];
            }

            $matrix[$prodId][$grp] = $qty;
            $matrix[$prodId]['total'] += $qty;
        }

        sort($groups);

        // Llenar vacíos con 0
        foreach ($matrix as &$item) {
            foreach ($groups as $g) {
                if (!isset($item[$g])) {
                    $item[$g] = 0;
                }
            }
        }

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'data' => array_values($matrix)
        ]);
    }

    public function kpis()
    {
        $totalDonantes = $this->db->selectOne("SELECT COUNT(*) as c FROM vamDonante")['c'] ?? 0;
        $totalExtracciones = $this->db->selectOne("SELECT COUNT(*) as c FROM vamExtDona")['c'] ?? 0;
        $totalHemocomponentes = $this->db->selectOne("SELECT COUNT(*) as c FROM vamFraccio")['c'] ?? 0;
        $stockLiberado = $this->db->selectOne("SELECT COUNT(*) as c FROM vamFraccio WHERE vfraEstFra = '1'")['c'] ?? 0;
        $stockCuarentena = $this->db->selectOne("SELECT COUNT(*) as c FROM vamFraccio WHERE vfraEstFra = '0'")['c'] ?? 0;
        $solicitudes = $this->db->selectOne("SELECT COUNT(*) as c FROM vamSolicit")['c'] ?? 0;

        return response()->json([
            'success' => true,
            'kpis' => [
                'total_donantes' => (int)$totalDonantes,
                'total_extracciones' => (int)$totalExtracciones,
                'total_hemocomponentes' => (int)$totalHemocomponentes,
                'stock_liberado' => (int)$stockLiberado,
                'stock_cuarentena' => (int)$stockCuarentena,
                'solicitudes_activas' => (int)$solicitudes,
            ]
        ]);
    }

    public function actividades()
    {
        $sql = "SELECT TOP 8 e.vexdNroExd as id, 
                       e.vexdFecIni as fecha, 
                       d.vdonNombre + ' ' + d.vdonPatern as donante,
                       g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                       e.vexdCantml as volumen_ml
                FROM vamExtDona e
                LEFT JOIN vamScreeni s ON e.vscrNroScr = s.vscrNroScr
                LEFT JOIN vamDonante d ON s.vdonCodDon = d.vdonCodDon
                LEFT JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                ORDER BY e.vexdNroExd DESC";

        $activities = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }
}

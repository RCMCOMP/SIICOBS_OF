<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class FlebotomiaController extends Controller
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
        $search = trim($request->input('search', ''));

        $baseSql = "SELECT e.vexdNroExd as id, 
                           e.vexdFecIni as fecha, 
                           d.vdonNombre + ' ' + d.vdonPatern as donante,
                           d.vdonDocIde as doc_identidad,
                           g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                           b.vbolDescri as tipo_bolsa,
                           e.vexdCantml as volumen_ml,
                           res.vresNombre as flebotomista
                    FROM vamExtDona e
                    LEFT JOIN vamScreeni s ON e.vscrNroScr = s.vscrNroScr
                    LEFT JOIN vamDonante d ON s.vdonCodDon = d.vdonCodDon
                    LEFT JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                    LEFT JOIN vamBolsaHe b ON e.vbolCodBol = b.vbolCodBol
                    LEFT JOIN vamRespons res ON e.vexdResExd = res.vresCodRes";

        $params = [];
        if (!empty($search)) {
            $baseSql .= " WHERE d.vdonDocIde LIKE ? OR d.vdonNombre LIKE ? OR d.vdonPatern LIKE ? OR CAST(e.vexdNroExd AS VARCHAR) LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $result = $this->db->paginate($baseSql, $params, $page, $perPage, "e.vexdNroExd DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function getBolsas()
    {
        $sql = "SELECT vbolCodBol as id, vbolDescri as descripcion, vbolCantid as stock FROM vamBolsaHe ORDER BY vbolCodBol ASC";
        $bolsas = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'data' => $bolsas
        ]);
    }

    public function getGrupos()
    {
        $sql = "SELECT vgrsCodGrs as id, vgrsGruABO + vgrsTipoRH as nombre FROM vamGrupSan ORDER BY vgrsCodGrs ASC";
        $grupos = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'data' => $grupos
        ]);
    }

    public function store(Request $request)
    {
        $donorId = (int)$request->input('donor_id');
        $bagId = (int)$request->input('tipo_bolsa_id', 1);
        $grpId = (int)$request->input('grupo_sanguineo_id', 1);
        $volumen = (int)$request->input('volumen_ml', 450);

        // Buscar último screening de este donante
        $scrRow = $this->db->selectOne("SELECT TOP 1 vscrNroScr FROM vamScreeni WHERE vdonCodDon = ? ORDER BY vscrNroScr DESC", [$donorId]);
        $scrId = $scrRow['vscrNroScr'] ?? 1;

        $maxRow = $this->db->selectOne("SELECT MAX(vexdNroExd) as max_id FROM vamExtDona");
        $nextExd = (int)($maxRow['max_id'] ?? 0) + 1;

        $sql = "INSERT INTO vamExtDona (vexdNroExd, vscrNroScr, vcenCodCen, vexdFecIni, vexdFecFin, vexdCantml, vbolCodBol, vgrsCodGrs, vexdEstExd, vexdResExd)
                VALUES (?, ?, 1, GETDATE(), GETDATE(), ?, ?, ?, '1', 1)";

        $this->db->insert($sql, [$nextExd, $scrId, $volumen, $bagId, $grpId]);

        return response()->json([
            'success' => true,
            'message' => 'Extracción registrada exitosamente',
            'extraction_id' => $nextExd,
            'barcode' => 'EX' . str_pad($nextExd, 8, '0', STR_PAD_LEFT)
        ]);
    }

    public function getLabel($id)
    {
        $sql = "SELECT e.vexdNroExd as id, e.vexdFecIni as fecha, e.vexdCantml as volumen,
                       d.vdonNombre + ' ' + d.vdonPatern as donante,
                       d.vdonDocIde as doc_identidad,
                       g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo,
                       b.vbolDescri as tipo_bolsa
                FROM vamExtDona e
                LEFT JOIN vamScreeni s ON e.vscrNroScr = s.vscrNroScr
                LEFT JOIN vamDonante d ON s.vdonCodDon = d.vdonCodDon
                LEFT JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                LEFT JOIN vamBolsaHe b ON e.vbolCodBol = b.vbolCodBol
                WHERE e.vexdNroExd = ?";

        $label = $this->db->selectOne($sql, [(int)$id]);

        if (!$label) {
            return response()->json(['success' => false, 'message' => 'Extracción no encontrada'], 404);
        }

        $code = 'EX' . str_pad($label['id'], 8, '0', STR_PAD_LEFT);
        $qrData = json_encode([
            'ext_id' => $label['id'],
            'fecha' => $label['fecha'],
            'grupo' => $label['grupo_sanguineo'],
            'vol' => $label['volumen'],
            'ci' => $label['doc_identidad'],
            'inst' => 'BANCO_SANGRE_ORURO'
        ]);

        return response()->json([
            'success' => true,
            'label' => array_merge($label, [
                'barcode' => $code,
                'qr_data' => $qrData
            ])
        ]);
    }
}

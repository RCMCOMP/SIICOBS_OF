<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class DonanteController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);

        $baseSql = "SELECT d.vdonCodDon as id, 
                           d.vdonDocIde as doc_identidad, 
                           d.vdonNombre as nombre, 
                           d.vdonPatern as primer_apellido, 
                           d.vdonMatern as segundo_apellido,
                           d.vdonSexodn as sexo, 
                           d.vdonFecNac as fecha_nacimiento, 
                           d.vdonTelDom as telefono, 
                           d.vdonTelCel as celular,
                           d.vdonDirecc as direccion
                    FROM vamDonante d";

        $params = [];
        if (!empty($search)) {
            $baseSql .= " WHERE d.vdonDocIde LIKE ? OR d.vdonNombre LIKE ? OR d.vdonPatern LIKE ? OR d.vdonMatern LIKE ? OR d.vdonTelCel LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
        }

        $result = $this->db->paginate($baseSql, $params, $page, $perPage, "d.vdonCodDon DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function show($id)
    {
        $sql = "SELECT d.vdonCodDon as id, d.vdonDocIde as doc_identidad, d.vdonNombre as nombre,
                       d.vdonPatern as primer_apellido, d.vdonMatern as segundo_apellido,
                       d.vdonSexodn as sexo, d.vdonFecNac as fecha_nacimiento,
                       d.vdonTelDom as telefono, d.vdonTelCel as celular, d.vdonDirecc as direccion
                FROM vamDonante d
                WHERE d.vdonCodDon = ?";
        
        $donor = $this->db->selectOne($sql, [(int)$id]);

        if (!$donor) {
            return response()->json(['success' => false, 'message' => 'Donante no encontrado'], 404);
        }

        // Historial de extracciones del donante mediante vamScreeni
        $extractionsSql = "SELECT e.vexdNroExd as id, e.vexdFecIni as fecha, e.vexdCantml as volumen,
                                  e.vexdEstExd as estado, b.vbolDescri as tipo_bolsa,
                                  g.vgrsGruABO + g.vgrsTipoRH as grupo_sanguineo
                           FROM vamExtDona e
                           INNER JOIN vamScreeni s ON e.vscrNroScr = s.vscrNroScr
                           LEFT JOIN vamBolsaHe b ON e.vbolCodBol = b.vbolCodBol
                           LEFT JOIN vamGrupSan g ON e.vgrsCodGrs = g.vgrsCodGrs
                           WHERE s.vdonCodDon = ?
                           ORDER BY e.vexdNroExd DESC";
        $extractions = $this->db->select($extractionsSql, [(int)$id]);

        return response()->json([
            'success' => true,
            'donor' => $donor,
            'extractions' => $extractions
        ]);
    }

    public function store(Request $request)
    {
        $docId = trim($request->input('doc_identidad', ''));
        $nombre = trim($request->input('nombre', ''));
        $priApe = trim($request->input('primer_apellido', ''));
        $segApe = trim($request->input('segundo_apellido', ''));
        $sexo = trim($request->input('sexo', 'M'));
        $fecNac = trim($request->input('fecha_nacimiento', '1990-01-01'));
        $direccion = trim($request->input('direccion', ''));
        $telefono = trim($request->input('telefono', ''));
        $celular = trim($request->input('celular', ''));

        if (empty($docId) || empty($nombre)) {
            return response()->json(['success' => false, 'message' => 'Documento de identidad y Nombre son obligatorios'], 422);
        }

        $maxRow = $this->db->selectOne("SELECT MAX(vdonCodDon) as max_id FROM vamDonante");
        $nextId = (int)($maxRow['max_id'] ?? 0) + 1;

        $sql = "INSERT INTO vamDonante (vdonCodDon, vdonDocIde, vtidCodTid, vdonNombre, vdonPatern, vdonMatern, vdonSexodn, vdonFecNac, vdonDirecc, vdonTelDom, vdonTelCel)
                VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->insert($sql, [$nextId, $docId, $nombre, $priApe, $segApe, $sexo, $fecNac, $direccion, $telefono, $celular]);

        return response()->json([
            'success' => true,
            'message' => 'Donante registrado exitosamente',
            'donor_id' => $nextId
        ]);
    }

    public function update(Request $request, $id)
    {
        $donorId = (int)$id;
        $docId = trim($request->input('doc_identidad', ''));
        $nombre = trim($request->input('nombre', ''));
        $priApe = trim($request->input('primer_apellido', ''));
        $segApe = trim($request->input('segundo_apellido', ''));
        $sexo = trim($request->input('sexo', 'M'));
        $fecNac = trim($request->input('fecha_nacimiento', ''));
        $direccion = trim($request->input('direccion', ''));
        $telefono = trim($request->input('telefono', ''));
        $celular = trim($request->input('celular', ''));

        $sql = "UPDATE vamDonante 
                SET vdonDocIde = ?, vdonNombre = ?, vdonPatern = ?, vdonMatern = ?, 
                    vdonSexodn = ?, vdonFecNac = ?, vdonDirecc = ?, vdonTelDom = ?, vdonTelCel = ?
                WHERE vdonCodDon = ?";

        $this->db->update($sql, [$docId, $nombre, $priApe, $segApe, $sexo, $fecNac, $direccion, $telefono, $celular, $donorId]);

        return response()->json([
            'success' => true,
            'message' => 'Datos del donante actualizados correctamente'
        ]);
    }
}

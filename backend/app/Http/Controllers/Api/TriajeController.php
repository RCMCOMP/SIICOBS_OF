<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class TriajeController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getQuestions()
    {
        $sql = "SELECT vcueNroCue as id, vcueNroPre as numero, vcuePregun as pregunta, 
                       vcueOpcio1 as opc1, vcueOpcio2 as opc2, vcueRespue as resp_esperada
                FROM vamCuestio
                ORDER BY vcueNroCue ASC, vcueNroPre ASC";
        
        $questions = $this->db->select($sql);

        return response()->json([
            'success' => true,
            'questions' => $questions
        ]);
    }

    public function getRejections(Request $request)
    {
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 20);

        $baseSql = "SELECT r.vrecCodRec as id, r.vrecFecRet as fecha,
                           d.vdonNombre + ' ' + d.vdonPatern as donante,
                           d.vdonDocIde as doc_identidad,
                           r.vrecMotivo as motivo,
                           res.vresNombre as evaluador
                    FROM vamRechazo r
                    LEFT JOIN vamDonante d ON r.vdonCodDon = d.vdonCodDon
                    LEFT JOIN vamRespons res ON r.vresCodRes = res.vresCodRes";

        $result = $this->db->paginate($baseSql, [], $page, $perPage, "r.vrecCodRec DESC");

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    public function evaluate(Request $request)
    {
        $donorId = (int)$request->input('donor_id');
        $peso = $request->input('peso', '70');
        $talla = $request->input('talla', '170');
        $paMax = $request->input('presion_sistolica', '120');
        $paMin = $request->input('presion_diastolica', '80');
        $pulso = $request->input('pulso', '75');
        $temp = $request->input('temperatura', '36.5');
        $hb = $request->input('hemoglobina', '15.0');
        $hto = $request->input('hematocrito', '45.0');
        $apto = (bool)$request->input('apto', true);
        $motivoRechazo = $request->input('motivo_rechazo', '');

        $maxRow = $this->db->selectOne("SELECT MAX(vscrNroScr) as max_id FROM vamScreeni");
        $nextScr = (int)($maxRow['max_id'] ?? 0) + 1;

        $sqlScr = "INSERT INTO vamScreeni (vscrNroScr, vdonCodDon, vcenCodCen, vscrFechas, vscrPesodo, vscrTalla, vscrPreMax, vscrPreMin, vscrPulsod, vscrTemped, vscrGhemog, vscrHemato, vscrResScr)
                   VALUES (?, ?, 1, GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->insert($sqlScr, [$nextScr, $donorId, (string)$peso, (string)$talla, (string)$paMax, (string)$paMin, (string)$pulso, (string)$temp, (string)$hb, (string)$hto, $apto ? 1 : 0]);

        if (!$apto) {
            $maxRec = $this->db->selectOne("SELECT MAX(vrecCodRec) as max_id FROM vamRechazo");
            $nextRec = (int)($maxRec['max_id'] ?? 0) + 1;

            $sqlRec = "INSERT INTO vamRechazo (vrecCodRec, vdonCodDon, vscrNroScr, vcenCodCen, vrecMotivo, vrecFecRet, vresCodRes)
                       VALUES (?, ?, ?, 1, ?, GETDATE(), 1)";
            $this->db->insert($sqlRec, [$nextRec, $donorId, $nextScr, $motivoRechazo ?: 'Diferimiento clínico']);
        }

        return response()->json([
            'success' => true,
            'message' => $apto ? 'Donante evaluado y APTO para flebotomía' : 'Donante DIFERIDO / RECHAZADO registrado',
            'screening_id' => $nextScr,
            'apto' => $apto
        ]);
    }
}

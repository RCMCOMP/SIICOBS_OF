<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;

class SerologiaController extends Controller
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
    }

    public function getTests()
    {
        $sql = "SELECT vpruCodPru as id, vpruDescri as nombre, vpruCaract as metodo FROM vamPruebas ORDER BY vpruorden ASC";
        $tests = $this->db->select($sql);

        if (empty($tests)) {
            $tests = [
                ['id' => 1, 'nombre' => 'VIH 1/2 (Anticuerpos y Antígeno p24)', 'metodo' => 'ELISA 4ta Gen / Quimioluminiscencia'],
                ['id' => 2, 'nombre' => 'Hepatitis B (HBsAg)', 'metodo' => 'Quimioluminiscencia'],
                ['id' => 3, 'nombre' => 'Hepatitis B (Anti-HBc Total)', 'metodo' => 'ELISA'],
                ['id' => 4, 'nombre' => 'Hepatitis C (Anti-HCV)', 'metodo' => 'Quimioluminiscencia'],
                ['id' => 5, 'nombre' => 'Chagas (T. cruzi)', 'metodo' => 'ELISA / HAI'],
                ['id' => 6, 'nombre' => 'Sífilis (Treponema pallidum)', 'metodo' => 'RPR / VDRL'],
                ['id' => 7, 'nombre' => 'HTLV I/II', 'metodo' => 'ELISA']
            ];
        }

        return response()->json([
            'success' => true,
            'tests' => $tests
        ]);
    }

    public function saveResults(Request $request)
    {
        $extId = (int)$request->input('extraccion_id');
        $results = (array)$request->input('results', []);

        $hasReactive = false;
        foreach ($results as $r) {
            $testId = (int)($r['id'] ?? 1);
            $reactive = (bool)($r['reactivo'] ?? false);
            if ($reactive) $hasReactive = true;

            $maxRow = $this->db->selectOne("SELECT MAX(vserNroPru) as max_id FROM vamSerolog");
            $nextSer = (int)($maxRow['max_id'] ?? 0) + 1;

            $sql = "INSERT INTO vamSerolog (vserNroPru, vexdNroExd, vpruCodPru, vserResult, vresCodRes, vserFecSer)
                    VALUES (?, ?, ?, ?, 1, GETDATE())";

            $this->db->insert($sql, [$nextSer, $extId, $testId, $reactive ? 'R' : 'N']);
        }

        if ($hasReactive) {
            $this->db->update("UPDATE vamFraccio SET vfraEstFra = '2' WHERE vexdNroExd = ?", [$extId]);
            $this->db->update("UPDATE vamExtDona SET vexdEstExd = '2' WHERE vexdNroExd = ?", [$extId]);

            return response()->json([
                'success' => true,
                'status' => 'REACTIVE',
                'message' => 'ALERTA: Muestra REACTIVA detectada. Hemocomponentes bloqueados y enviados a DESCARTE.'
            ]);
        } else {
            $this->db->update("UPDATE vamFraccio SET vfraEstFra = '1' WHERE vexdNroExd = ?", [$extId]);
            $this->db->update("UPDATE vamExtDona SET vexdEstExd = '1' WHERE vexdNroExd = ?", [$extId]);

            return response()->json([
                'success' => true,
                'status' => 'NON_REACTIVE',
                'message' => 'Resultados NO REACTIVOS. Unidades liberadas para uso clínico seguro.'
            ]);
        }
    }
}

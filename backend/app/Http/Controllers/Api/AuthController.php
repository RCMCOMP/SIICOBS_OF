<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;
use App\Services\AclService;

class AuthController extends Controller
{
    private VampiroDbService $db;
    private AclService $aclService;

    public function __construct(VampiroDbService $db, AclService $aclService)
    {
        $this->db = $db;
        $this->aclService = $aclService;
    }

    public function login(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));

        if (empty($username) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor ingrese su usuario y contraseña.'
            ], 400);
        }

        // Buscar usuario en vamRespons
        $sql = "SELECT vresCodRes, vresNombre, vresDocide, vresIdenti, vresPaswor, vresAdmini, vresIniNom 
                FROM vamRespons 
                WHERE vresIdenti = ? AND vresPaswor = ?";
        
        $user = $this->db->selectOne($sql, [$username, $password]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas. Verifique su usuario y contraseña.'
            ], 401);
        }

        $userId = (int)$user['vresCodRes'];
        $isAdmin = (bool)($user['vresAdmini'] ?? false);

        // Obtener permisos ACL asignados
        $permissions = $this->aclService->getUserPermissions($userId, $isAdmin);

        // Generar token firmado
        $payload = [
            'userId' => $userId,
            'username' => $user['vresIdenti'],
            'name' => $user['vresNombre'],
            'isAdmin' => $isAdmin,
            'timestamp' => time()
        ];
        $token = base64_encode(json_encode($payload));

        return response()->json([
            'success' => true,
            'message' => 'Bienvenido al sistema SIICOBS Moderno',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'username' => $user['vresIdenti'],
                'name' => $user['vresNombre'],
                'initials' => $user['vresIniNom'] ?? '',
                'isAdmin' => $isAdmin,
            ],
            'acl' => $permissions
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->get('auth_user');
        $userId = (int)$user['vresCodRes'];
        $isAdmin = (bool)($user['vresAdmini'] ?? false);

        $permissions = $this->aclService->getUserPermissions($userId, $isAdmin);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $userId,
                'username' => $user['vresIdenti'],
                'name' => $user['vresNombre'],
                'isAdmin' => $isAdmin,
            ],
            'acl' => $permissions
        ]);
    }

    public function logout()
    {
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}

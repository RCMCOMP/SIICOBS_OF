<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AclService;
use App\Services\VampiroDbService;

class CheckUserAcl
{
    private AclService $aclService;
    private VampiroDbService $db;

    public function __construct(AclService $aclService, VampiroDbService $db)
    {
        $this->aclService = $aclService;
        $this->db = $db;
    }

    public function handle(Request $request, Closure $next, string $resource = '', string $action = 'can_view'): Response
    {
        $authHeader = $request->header('Authorization', '');
        $token = trim(preg_replace('/^Bearer\s+/i', '', $authHeader));

        if (empty($token)) {
            $token = trim($request->input('token', ''));
        }

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado: Token de sesión requerido.'
            ], 401);
        }

        // Decodificar token (Base64 payload firmado o token estructurado)
        $tokenData = json_decode(base64_decode($token), true);
        if (!$tokenData || empty($tokenData['userId'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión inválida o expirada.'
            ], 401);
        }

        $userId = (int)$tokenData['userId'];
        $user = $this->db->selectOne("SELECT vresCodRes, vresIdenti, vresNombre, vresAdmini FROM vamRespons WHERE vresCodRes = ?", [$userId]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado en la base de datos.'
            ], 401);
        }

        // Adjuntar usuario autenticado a la request
        $isAdmin = (bool)($user['vresAdmini'] ?? false);
        $request->merge(['auth_user' => $user, 'is_admin' => $isAdmin]);

        // Si es Admin, tiene acceso total a cualquier recurso
        if ($isAdmin) {
            return $next($request);
        }

        // Si no se especificó recurso, solo requiere autenticación
        if (empty($resource)) {
            return $next($request);
        }

        // Validar permisos ACL del usuario
        $permissions = $this->aclService->getUserPermissions($userId, false);
        $userPerm = $permissions[$resource] ?? null;

        if (!$userPerm || empty($userPerm[$action])) {
            return response()->json([
                'success' => false,
                'message' => "Acceso Denegado (ACL): No cuenta con permisos para la acción '{$action}' en el recurso '{$resource}'."
            ], 403);
        }

        return $next($request);
    }
}

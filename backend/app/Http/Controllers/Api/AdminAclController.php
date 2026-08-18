<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VampiroDbService;
use App\Services\AclService;

class AdminAclController extends Controller
{
    private VampiroDbService $db;
    private AclService $aclService;

    public function __construct(VampiroDbService $db, AclService $aclService)
    {
        $this->db = $db;
        $this->aclService = $aclService;
    }

    public function getUsers(Request $request)
    {
        $search = trim($request->input('search', ''));
        $sql = "SELECT vresCodRes as id, vresNombre as nombre, vresDocide as doc_identidad, 
                       vresIdenti as username, vresAdmini as is_admin, vresTelefo as telefono, 
                       vresTelCel as celular, vresDirecc as direccion
                FROM vamRespons";
        
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE vresNombre LIKE ? OR vresIdenti LIKE ? OR vresDocide LIKE ?";
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        $sql .= " ORDER BY vresCodRes ASC";

        $users = $this->db->select($sql, $params);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function createUser(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = trim($request->input('password', ''));
        $nombre = trim($request->input('nombre', ''));
        $docId = trim($request->input('doc_identidad', ''));
        $telefono = trim($request->input('telefono', ''));
        $celular = trim($request->input('celular', ''));
        $isAdmin = $request->boolean('is_admin', false) ? 1 : 0;
        $institutionName = trim($request->input('institution_name', ''));

        if (empty($username) || empty($password) || empty($nombre)) {
            return response()->json([
                'success' => false,
                'message' => 'El nombre, nombre de usuario y contraseña son obligatorios.'
            ], 422);
        }

        // Verificar si ya existe el username
        $exists = $this->db->selectOne("SELECT vresCodRes FROM vamRespons WHERE vresIdenti = ?", [$username]);
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'El nombre de usuario ya está registrado en el sistema.'
            ], 422);
        }

        // Obtener siguiente ID
        $maxRow = $this->db->selectOne("SELECT MAX(vresCodRes) as max_id FROM vamRespons");
        $nextId = (int)($maxRow['max_id'] ?? 0) + 1;

        $inits = strtoupper(substr($username, 0, 3));

        $sql = "INSERT INTO vamRespons (vresCodRes, vresNombre, vresDocide, vtidCodtid, vresDirecc, vresTelefo, vresTelCel, vresIdenti, vresPaswor, vresAdmini, vresIniNom) 
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->insert($sql, [
            $nextId, $nombre, $docId, $institutionName, $telefono, $celular, $username, $password, $isAdmin, $inits
        ]);

        // Guardar permisos iniciales si se enviaron
        $permissions = $request->input('permissions', []);
        if (!empty($permissions)) {
            $this->aclService->saveUserPermissions($nextId, $permissions, $institutionName);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user_id' => $nextId
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $userId = (int)$id;
        $nombre = trim($request->input('nombre', ''));
        $docId = trim($request->input('doc_identidad', ''));
        $telefono = trim($request->input('telefono', ''));
        $celular = trim($request->input('celular', ''));
        $isAdmin = $request->boolean('is_admin', false) ? 1 : 0;
        $password = trim($request->input('password', ''));
        $institutionName = trim($request->input('institution_name', ''));

        $sql = "UPDATE vamRespons SET vresNombre = ?, vresDocide = ?, vresTelefo = ?, vresTelCel = ?, vresAdmini = ?, vresDirecc = ?";
        $params = [$nombre, $docId, $telefono, $celular, $isAdmin, $institutionName];

        if (!empty($password)) {
            $sql .= ", vresPaswor = ?";
            $params[] = $password;
        }

        $sql .= " WHERE vresCodRes = ?";
        $params[] = $userId;

        $this->db->update($sql, $params);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente'
        ]);
    }

    public function getResources()
    {
        return response()->json([
            'success' => true,
            'resources' => AclService::getAllResources()
        ]);
    }

    public function getUserAcl($userId)
    {
        $user = $this->db->selectOne("SELECT vresCodRes, vresNombre, vresIdenti, vresAdmini FROM vamRespons WHERE vresCodRes = ?", [(int)$userId]);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $isAdmin = (bool)($user['vresAdmini'] ?? false);
        $permissions = $this->aclService->getUserPermissions((int)$userId, $isAdmin);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user['vresCodRes'],
                'name' => $user['vresNombre'],
                'username' => $user['vresIdenti'],
                'isAdmin' => $isAdmin
            ],
            'acl' => $permissions
        ]);
    }

    public function saveUserAcl(Request $request, $userId)
    {
        $permissions = $request->input('permissions', []);
        $institutionName = $request->input('institution_name', null);

        $this->aclService->saveUserPermissions((int)$userId, $permissions, $institutionName);

        return response()->json([
            'success' => true,
            'message' => 'Matriz de permisos ACL guardada exitosamente.'
        ]);
    }
}

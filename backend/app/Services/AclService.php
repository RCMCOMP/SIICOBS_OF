<?php

namespace App\Services;

class AclService
{
    private VampiroDbService $db;

    public function __construct(VampiroDbService $db)
    {
        $this->db = $db;
        $this->ensureAclTablesExist();
    }

    /**
     * Asegura la creación no invasiva de la tabla de ACL en bdvampiro
     */
    public function ensureAclTablesExist(): void
    {
        try {
            $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='mod_user_acl' AND xtype='U')
            BEGIN
                CREATE TABLE mod_user_acl (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    user_id INT NOT NULL,
                    resource VARCHAR(100) NOT NULL,
                    can_view BIT DEFAULT 1,
                    can_create BIT DEFAULT 0,
                    can_edit BIT DEFAULT 0,
                    can_delete BIT DEFAULT 0,
                    institution_name VARCHAR(150) NULL,
                    created_at DATETIME DEFAULT GETDATE(),
                    updated_at DATETIME DEFAULT GETDATE()
                );
                CREATE INDEX idx_mod_user_acl ON mod_user_acl(user_id, resource);
            END";
            $this->db->statement($sql);
        } catch (\Exception $e) {
            // Ignorar si ya existe o error controlado
        }
    }

    /**
     * Lista todos los recursos y módulos del sistema
     */
    public static function getAllResources(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Tablero de Control & Stock en Vivo', 'category' => 'General', 'icon' => 'dashboard'],
            ['key' => 'donantes', 'label' => 'Donantes & Filiación', 'category' => 'Donación', 'icon' => 'people'],
            ['key' => 'triaje', 'label' => 'Triaje, Signos Vitales & Cuestionario', 'category' => 'Donación', 'icon' => 'favorite'],
            ['key' => 'flebotomia', 'label' => 'Flebotomía & Extracción', 'category' => 'Extracción', 'icon' => 'colorize'],
            ['key' => 'fraccionamiento', 'label' => 'Fraccionamiento & Producción', 'category' => 'Laboratorio', 'icon' => 'science'],
            ['key' => 'serologia', 'label' => 'Tamizaje Serológico (Infecciosas)', 'category' => 'Laboratorio', 'icon' => 'biotech'],
            ['key' => 'inmunohematologia', 'label' => 'Inmunohematología & Pruebas Cruzadas', 'category' => 'Laboratorio', 'icon' => 'bloodtype'],
            ['key' => 'calidad', 'label' => 'Control de Calidad & Liberación', 'category' => 'Calidad', 'icon' => 'verified'],
            ['key' => 'almacen', 'label' => 'Almacén, Cámaras Frías & Bolsas', 'category' => 'Almacén', 'icon' => 'inventory_2'],
            ['key' => 'solicitudes', 'label' => 'Recepción de Solicitudes Hospitalarias', 'category' => 'Distribución', 'icon' => 'assignment'],
            ['key' => 'despacho', 'label' => 'Despacho & Distribución de Unidades', 'category' => 'Distribución', 'icon' => 'local_shipping'],
            ['key' => 'facturacion', 'label' => 'Facturación & Notas de Entrega', 'category' => 'Finanzas', 'icon' => 'receipt_long'],
            ['key' => 'reportes', 'label' => 'Reportes Oficiales, SNIS & Trazabilidad', 'category' => 'Reportes', 'icon' => 'analytics'],
            ['key' => 'portal_clinicas', 'label' => 'Portal de Clínicas & Hospitales Externos', 'category' => 'Externo', 'icon' => 'local_hospital'],
            ['key' => 'admin_acl', 'label' => 'Administración de Usuarios & Matriz ACL', 'category' => 'Configuración', 'icon' => 'admin_panel_settings'],
        ];
    }

    /**
     * Obtiene los permisos ACL asignados a un usuario
     */
    public function getUserPermissions(int $userId, bool $isAdmin = false): array
    {
        if ($isAdmin) {
            // Superadmin tiene acceso a todos los recursos
            $all = [];
            foreach (self::getAllResources() as $res) {
                $all[$res['key']] = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                ];
            }
            return $all;
        }

        $sql = "SELECT resource, can_view, can_create, can_edit, can_delete FROM mod_user_acl WHERE user_id = ?";
        $rows = $this->db->select($sql, [$userId]);

        if (empty($rows)) {
            // Si es usuario antiguo sin ACL en tabla nueva, leer permisos del sistema legado (vamPermiso)
            return $this->getLegacyPermissions($userId);
        }

        $perms = [];
        foreach ($rows as $r) {
            $perms[$r['resource']] = [
                'can_view' => (bool)$r['can_view'],
                'can_create' => (bool)$r['can_create'],
                'can_edit' => (bool)$r['can_edit'],
                'can_delete' => (bool)$r['can_delete'],
            ];
        }
        return $perms;
    }

    /**
     * Mapeo de compatibilidad con permisos legados (vamPermiso/vamProceso)
     */
    private function getLegacyPermissions(int $userId): array
    {
        $perms = [
            'dashboard' => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ];

        $sql = "SELECT vpceCodPce FROM vamPermiso WHERE vresCodRes = ?";
        $rows = $this->db->select($sql, [$userId]);
        $codes = array_column($rows, 'vpceCodPce');

        if (in_array(135, $codes)) $perms['almacen'] = ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false];
        if (in_array(11, $codes)) $perms['fraccionamiento'] = ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false];
        if (in_array(2, $codes)) $perms['despacho'] = ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false];
        if (in_array(19, $codes)) $perms['reportes'] = ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false];
        if (in_array(8, $codes)) $perms['serologia'] = ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false];
        if (in_array(5, $codes)) $perms['flebotomia'] = ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false];

        return $perms;
    }

    /**
     * Guarda o actualiza la matriz ACL de un usuario
     */
    public function saveUserPermissions(int $userId, array $permissions, ?string $institutionName = null): void
    {
        $this->db->statement("DELETE FROM mod_user_acl WHERE user_id = ?", [$userId]);

        foreach ($permissions as $resource => $p) {
            $canView = !empty($p['can_view']) ? 1 : 0;
            $canCreate = !empty($p['can_create']) ? 1 : 0;
            $canEdit = !empty($p['can_edit']) ? 1 : 0;
            $canDelete = !empty($p['can_delete']) ? 1 : 0;

            $this->db->insert(
                "INSERT INTO mod_user_acl (user_id, resource, can_view, can_create, can_edit, can_delete, institution_name) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, $resource, $canView, $canCreate, $canEdit, $canDelete, $institutionName]
            );
        }
    }
}

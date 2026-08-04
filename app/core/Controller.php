<?php
class Controller {
    private static array $sysConfig = [];

    private static function sysConfig(): array {
        if (empty(self::$sysConfig)) {
            $db = DB::getInstance();
            self::$sysConfig = [
                'companyName' => $db->single("SELECT config_value FROM system_config WHERE config_key='company_name'", [], false) ?: '',
                'sysName'     => $db->single("SELECT config_value FROM system_config WHERE config_key='system_name'", [], false) ?: '绿色产品管理系统GPM',
                'logoPath'    => $db->single("SELECT config_value FROM system_config WHERE config_key='company_logo'", [], false) ?: '/assets/images/logo.png',
            ];
        }
        return self::$sysConfig;
    }

    public function render(string $view, array $data = []): void {
        $config = $GLOBALS['config'] ?? require __DIR__ . '/../../config/app.php';
        $data += [
            'config'  => $config,
            'title'   => '仪表盘',
            'version' => $config['version'] ?? '1.0.0',
        ] + self::sysConfig();
        extract($data);
        ob_start();
        include __DIR__ . "/../views/$view.php";
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }

    protected function requirePermission(string $perm): void {
        if (!can($perm)) {
            http_response_code(403);
            $this->render('errors/403', ['title' => '无权限']);
            exit;
        }
    }

    protected function requireOwnership($ownerId, string $redirect = '/'): void {
        if (isSupplier() && (int)$ownerId !== userId()) {
            flash('error', '无权操作');
            $this->redirect($redirect);
        }
    }

    protected function requireSupplierRecord(string $table, $id, string $redirect = '/'): ?array {
        $row = DB::getInstance()->single("SELECT * FROM $table WHERE id=?", [(int)$id], true);
        if (!$row) {
            flash('error', '记录不存在');
            $this->redirect($redirect);
        }
        if (isSupplier() && (int)($row['supplier_id'] ?? 0) !== userSupplierId()) {
            flash('error', '无权操作');
            $this->redirect($redirect);
        }
        return $row;
    }

    protected function requireUserRecord(string $table, $id, string $ownerField, string $redirect = '/'): ?array {
        $row = DB::getInstance()->single("SELECT * FROM $table WHERE id=?", [(int)$id], true);
        if (!$row) {
            flash('error', '记录不存在');
            $this->redirect($redirect);
        }
        if (isSupplier() && (int)($row[$ownerField] ?? 0) !== userId()) {
            flash('error', '无权操作');
            $this->redirect($redirect);
        }
        return $row;
    }

    protected function requireNotApproved(string $status, string $redirect = '/'): void {
        if ($status === 'A' && !isAdmin()) {
            flash('error', '已批准的记录无法操作');
            $this->redirect($redirect);
        }
    }

    protected function supplierScope(array &$where, array &$params, string $field = 'supplier_id'): void {
        if (isSupplier()) {
            $where[] = "$field=?";
            $params[] = userSupplierId();
        }
    }

    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
}

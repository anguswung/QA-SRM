<?php
class Controller {
    public function render(string $view, array $data = []): void {
        $config = $GLOBALS['config'] ?? require __DIR__ . '/../../config/app.php';
        $data += [
            'config'      => $config,
            'title'       => '仪表盘',
            'companyName' => DB::getInstance()->single("SELECT config_value FROM system_config WHERE config_key='company_name'", [], false) ?: '',
            'sysName'     => DB::getInstance()->single("SELECT config_value FROM system_config WHERE config_key='system_name'", [], false) ?: '绿色产品管理系统GPM',
            'version'     => $config['version'] ?? '1.0.0',
            'logoPath'    => DB::getInstance()->single("SELECT config_value FROM system_config WHERE config_key='company_logo'", [], false) ?: '/assets/images/logo.png',
        ];
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

    protected function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
}

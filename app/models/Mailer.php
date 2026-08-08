<?php
/**
 * 邮件服务：SMTP 发送 / 报告到期提醒。
 * 纯 PHP socket 实现，无第三方依赖。
 */
class Mailer {

    public static function config(): array {
        $db = DB::getInstance();
        $rows = $db->query("SELECT config_key, config_value FROM system_config");
        $map = [];
        foreach ($rows as $r) $map[$r['config_key']] = $r['config_value'];
        return [
            'host'        => trim($map['mail_smtp_host'] ?? ''),
            'port'        => (int)($map['mail_smtp_port'] ?? 587),
            'encryption'  => $map['mail_smtp_encryption'] ?? 'tls',
            'username'    => trim((string)($map['mail_smtp_username'] ?? '')),
            'password'    => (string)($map['mail_smtp_password'] ?? ''),
            'from_address'=> trim((string)($map['mail_from_address'] ?? '')),
            'from_name'   => (string)($map['mail_from_name'] ?? ''),
            'enabled'     => ($map['mail_report_expiry_enabled'] ?? '1') === '1',
            'yellow_days' => (int)($map['report_expiry_yellow_days'] ?? 30),
            'title_tpl'   => (string)($map['mail_template_expiry_title'] ?? '【{company_name}】检测报告到期提醒'),
            'body_tpl'    => self::defaultBody(),
            'company_name'=> (string)($map['company_name'] ?? ''),
            'system_name' => (string)($map['system_name'] ?? '绿色产品管理系统GPM'),
        ];
    }

    private static function defaultBody(): string {
        return "<p>您好：</p>\r\n"
            . "<p>贵司物料「{material_name}」的检测报告 {report_no}（{report_type}）将于 {expiry_date} 到期，"
            . "距到期仅剩 <b>{days_left}</b> 天。请及时安排复测并上传最新报告，以免影响合规状态。</p>\r\n"
            . "<p>感谢配合！</p>\r\n<p>{company_name}</p>";
    }

    /** SMTP 发送一封邮件。 $error 出参返回错误信息。 */
    public function send($to, $subject, $bodyHtml, &$error = ''): bool {
        $cfg = self::config();
        if (!$cfg['host'] || !$cfg['username']) { $error = 'SMTP 服务器或账号未配置'; return false; }
        $conn = $this->smtpConnect($cfg, $error);
        if (!$conn) return false;

        // 认证
        $r = $this->smtpCmd($conn, 'AUTH LOGIN');
        $code = (int)substr($r, 0, 3);
        if ($code === 503) { $code = 235; } // 服务器无需认证
        if ($code != 334 && $code != 235) { $error = "AUTH 失败: " . trim($r); fclose($conn); return false; }
        if ($code == 334) {
            fwrite($conn, base64_encode($cfg['username']) . "\r\n"); $r = $this->smtpRead($conn);
            if ((int)substr($r, 0, 3) != 334) { $error = "用户名认证失败: " . trim($r); fclose($conn); return false; }
            fwrite($conn, base64_encode($cfg['password']) . "\r\n"); $r = $this->smtpRead($conn);
            if ((int)substr($r, 0, 3) != 235) { $error = "密码认证失败: " . trim($r); fclose($conn); return false; }
        }

        $from = $cfg['from_address'] ?: $cfg['username'];
        $r = $this->smtpCmd($conn, "MAIL FROM:<{$from}>");
        if ((int)substr($r, 0, 3) != 250) { $error = "MAIL FROM失败: " . trim($r); fclose($conn); return false; }
        $r = $this->smtpCmd($conn, "RCPT TO:<{$to}>");
        if ((int)substr($r, 0, 3) != 250) { $error = "RCPT TO失败(收件人 {$to}): " . trim($r); fclose($conn); return false; }

        $r = $this->smtpCmd($conn, 'DATA');
        if ((int)substr($r, 0, 3) != 354) { $error = "DATA失败: " . trim($r); fclose($conn); return false; }

        $fromName = $cfg['from_name'] ? ('=?UTF-8?B?' . base64_encode($cfg['from_name']) . '?=') : $from;
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = "From: {$fromName} <{$from}>\r\n"
            . "To: <{$to}>\r\n"
            . "Subject: {$subjectEnc}\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "Date: " . date('r') . "\r\n";
        $body64 = rtrim(chunk_split(base64_encode($bodyHtml), 76, "\r\n"));
        fwrite($conn, $headers . "\r\n" . $body64 . "\r\n.\r\n");
        $r = $this->smtpRead($conn, true);
        if ((int)substr($r, 0, 3) != 250) { $error = "发送失败: " . trim($r); fclose($conn); return false; }
        fwrite($conn, "QUIT\r\n");
        fclose($conn);
        return true;
    }

    /** 仅测试 SMTP 连接与认证，返回 bool。 */
    public function testSmtp(&$error = ''): bool {
        $cfg = self::config();
        if (!$cfg['host'] || !$cfg['username']) { $error = 'SMTP 服务器或账号未配置'; return false; }
        $conn = $this->smtpConnect($cfg, $error);
        if (!$conn) return false;
        fwrite($conn, "QUIT\r\n");
        fclose($conn);
        return true;
    }

    private function smtpConnect(array $cfg, &$error) {
        $remote = ($cfg['encryption'] === 'ssl' ? 'ssl://' : 'tcp://') . $cfg['host'] . ':' . $cfg['port'];
        $errno = 0; $errstr = '';
        $conn = @stream_socket_client($remote, $errno, $errstr, 10);
        if (!$conn) { $error = "SMTP 连接 {$cfg['host']}:{$cfg['port']} 失败: $errstr ($errno)"; return null; }
        stream_set_timeout($conn, 10);
        $r = $this->smtpRead($conn);
        if ((int)substr($r, 0, 3) !== 220) { $error = 'SMTP 服务器问候异常: ' . trim($r); fclose($conn); return null; }
        $r = $this->smtpCmd($conn, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if ((int)substr($r, 0, 3) !== 250) { $error = 'EHLO 失败: ' . trim($r); fclose($conn); return null; }
        if ($cfg['encryption'] === 'tls') {
            $r = $this->smtpCmd($conn, 'STARTTLS');
            if ((int)substr($r, 0, 3) !== 220) { $error = 'STARTTLS 失败: ' . trim($r); fclose($conn); return null; }
            if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $error = 'TLS 握手失败'; fclose($conn); return null;
            }
            $r = $this->smtpCmd($conn, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            if ((int)substr($r, 0, 3) !== 250) { $error = 'TLS 后 EHLO 失败: ' . trim($r); fclose($conn); return null; }
        }
        return $conn;
    }

    private function smtpCmd($conn, string $cmd): string {
        fwrite($conn, $cmd . "\r\n");
        return $this->smtpRead($conn);
    }

    private function smtpRead($conn, $multiline = false): string {
        $data = '';
        while (($line = fgets($conn, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    }

    /** 渲染模板占位符，如 {material_name}、{days_left}。 */
    public static function renderTemplate(string $template, array $vars): string {
        $search = []; $replace = [];
        foreach ($vars as $k => $v) { $search[] = '{' . $k . '}'; $replace[] = (string)$v; }
        return str_replace($search, $replace, $template);
    }

    public function sendTest(string $to, &$error = ''): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $error = '请输入有效的测试收件邮箱'; return false; }
        return $this->send($to, 'SMTP 测试邮件', '<p>这是一封测试邮件，说明你的邮件服务器配置正确。</p>', $error);
    }

    /** 发送到期报告提醒。$force=true 则忽略 enabled 开关。返回统计。 */
    public function sendExpiryReminders(bool $force = false): array {
        $cfg = self::config();
        $result = [
            'enabled' => (bool)$cfg['enabled'],
            'reports' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 0,
            'errors' => [], 'logs' => [],
        ];
        if (!$cfg['enabled'] && !$force) { $result['errors'][] = '到期提醒未启用（可在邮箱设置里打开）'; return $result; }
        if (!$cfg['host'] || !$cfg['username']) { $result['errors'][] = 'SMTP 服务器或账号未配置'; return $result; }
        $this->ensureLogTable();

        $db = DB::getInstance();
        $cutoff = date('Y-m-d', strtotime("+{$cfg['yellow_days']} days"));
        $today = date('Y-m-d');

        $reports = [];
        // 物料检测报告
        $reports = array_merge($reports, $db->query(
            "SELECT 'material' AS src, tr.id, tr.report_no, tr.report_type, tr.expiry_date,
                    m.material_name, m.supplier_id, s.name AS supplier_name
             FROM test_report tr
             JOIN material m ON tr.material_id=m.id
             LEFT JOIN supplier s ON m.supplier_id=s.id
             WHERE tr.audit_status='approved' AND tr.expiry_date IS NOT NULL AND tr.expiry_date != ''
               AND julianday(tr.expiry_date) <= julianday(?) AND julianday(tr.expiry_date) >= julianday(?)",
            [$cutoff, $today]
        ));
        // 均质材料报告
        $reports = array_merge($reports, $db->query(
            "SELECT 'hm' AS src, hr.id, hr.report_no, hr.report_type, hr.expiry_date,
                    hm.material_name, u.supplier_id, s.name AS supplier_name
             FROM hm_report hr
             JOIN homogeneous_material hm ON hr.hm_id=hm.id
             LEFT JOIN users u ON hm.created_by=u.id
             LEFT JOIN supplier s ON u.supplier_id=s.id
             WHERE hr.status='current' AND hr.expiry_date IS NOT NULL AND hr.expiry_date != ''
               AND julianday(hr.expiry_date) <= julianday(?) AND julianday(hr.expiry_date) >= julianday(?)",
            [$cutoff, $today]
        ));

        $result['reports'] = count($reports);
        $titleTpl = $cfg['title_tpl'];
        $bodyTpl = $cfg['body_tpl'];

        foreach ($reports as $r) {
            if (empty($r['supplier_id'])) continue; // 无归属供应商无法确定收件人
            $daysLeft = max(0, (int)((strtotime($r['expiry_date']) - time()) / 86400));
            $vars = [
                'company_name'  => $cfg['company_name'],
                'system_name'   => $cfg['system_name'],
                'supplier_name' => $r['supplier_name'] ?? '',
                'material_name' => $r['material_name'] ?? '',
                'report_no'     => $r['report_no'] ?? '',
                'report_type'   => $r['report_type'] ?? '',
                'expiry_date'   => $r['expiry_date'] ?? '',
                'days_left'     => $daysLeft,
            ];
            $subject = self::renderTemplate($titleTpl, $vars);
            $body = self::renderTemplate($bodyTpl, $vars);
            $bodyHtml = '<html><body style="font-family:system-ui,sans-serif;color:#26313c;line-height:1.7">'
                . $body . '</body></html>';

            $recipients = $this->recipientsForSupplier((int)$r['supplier_id']);
            $result['logs'][$r['report_no']] = $recipients;

            foreach ($recipients as $email) {
                if ($this->alreadySent((string)$r['src'], (int)$r['id'], $email)) {
                    $result['skipped']++;
                    continue;
                }
                $err = '';
                $ok = $this->send($email, $subject, $bodyHtml, $err);
                $this->logSend((string)$r['src'], (int)$r['id'], $r['report_no'], $r['material_name'],
                    (int)$r['supplier_id'], $email, $subject, $ok, $err);
                if ($ok) $result['sent']++;
                else { $result['failed']++; $result['errors'][] = "{$r['report_no']} -> {$email}: {$err}"; }
            }
        }
        return $result;
    }

    private function recipientsForSupplier(int $supplierId): array {
        $db = DB::getInstance();
        $emails = [];
        $rows = $db->query(
            "SELECT email FROM supplier_contact WHERE supplier_id=? AND email IS NOT NULL AND email != ''",
            [$supplierId]
        );
        foreach ($rows as $r) $emails[] = trim($r['email']);
        $rows = $db->query(
            "SELECT email FROM users WHERE supplier_id=? AND email IS NOT NULL AND email != ''",
            [$supplierId]
        );
        foreach ($rows as $r) $emails[] = trim($r['email']);
        $emails = array_values(array_unique(array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))));
        return $emails;
    }

    private function alreadySent(string $type, int $reportId, string $email): bool {
        $db = DB::getInstance();
        return (bool)$db->single(
            "SELECT COUNT(*) FROM mail_send_log
             WHERE report_type=? AND report_id=? AND recipient=? AND status='ok'
               AND sent_at >= date('now','localtime')",
            [$type, $reportId, $email]
        );
    }

    private function logSend(string $src, int $reportId, string $no, string $name, int $supplierId,
        string $email, string $subject, bool $ok, string $err): void {
        $db = DB::getInstance();
        $db->execute(
            "INSERT INTO mail_send_log (report_type, report_id, report_no, report_name, supplier_id,
             recipient, subject, body, status, error, sent_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,datetime('now','localtime'))",
            [$src, $reportId, $no, $name, $supplierId, $email, $subject, '', $ok ? 'ok' : 'error', $err]
        );
    }

    private function ensureLogTable(): void {
        DB::getInstance()->execute(
            "CREATE TABLE IF NOT EXISTS mail_send_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                report_type TEXT,
                report_id INTEGER,
                report_no TEXT,
                report_name TEXT,
                supplier_id INTEGER,
                recipient TEXT,
                subject TEXT,
                body TEXT,
                status TEXT DEFAULT 'ok',
                error TEXT,
                sent_at TEXT DEFAULT (datetime('now','localtime'))
            )"
        );
    }
}
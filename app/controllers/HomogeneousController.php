<?php
class HomogeneousController extends Controller {
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') { $this->doCreate(); return; }
            if ($action === 'update') { $this->doUpdate(); return; }
            if ($action === 'delete') { $this->doDelete(); return; }
            if ($action === 'analyze_hm') { $this->doAnalyze(); return; }
            if ($action === 'delete_report') { $this->doDeleteReport(); return; }
            if ($action === 'save_elements') { $this->doSaveElements(); return; }
            if ($action === 'audit_hm') { $this->doAuditHm(); return; }
            if ($action === 'copy') { $this->doCopy(); return; }
            if ($action === 'import') { $this->doImport(); return; }
        }
        $action = $_GET['action'] ?? '';
        if ($action === 'create') { $this->showForm(); return; }
        if ($action === 'edit') { $this->showForm((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'data') { $this->data(); return; }
        if ($action === 'compliance') { $this->showCompliance((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'reports') { $this->showReports((int)($_GET['id'] ?? 0), (int)($_GET['report_id'] ?? 0)); return; }
        if ($action === 'usage') { $this->showMaterialUsage((int)($_GET['id'] ?? 0)); return; }
        if ($action === 'delete_report') { $this->doDeleteReport(); return; }
        $this->showList();
    }
    private function showList() {
        $this->requirePermission('homogeneous.view');
        $db = DB::getInstance();
        $search = $_GET['search'] ?? '';
        $uid = isSupplier() ? userId() : 0;
        $scopeWhere = $uid ? " WHERE m.created_by=$uid" : '';
        $reportSelects = "
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_expiry";
        // 统计卡片：按报告的合规规则统计（与列表环保状态列一致），不受搜索条件影响
        $totalRows = $db->query("SELECT m.id, $reportSelects FROM homogeneous_material m$scopeWhere", []);
        $total = count($totalRows);
        $compliantCount = $nonCompliantCount = $pendingCount = 0;
        foreach ($totalRows as $r) {
            $s = $this->hmEnvStatus($r);
            if ($s === 'compliant') $compliantCount++;
            elseif ($s === 'non_compliant') $nonCompliantCount++;
            else $pendingCount++;
        }
        $where = []; $params = [];
        if (isSupplier()) { $where[] = "m.created_by=?"; $params[] = userId(); }
        if ($search) { $where[] = "m.material_name LIKE ?"; $params[] = "%$search%"; }
        $title = '均质材料';
        $this->render('homogeneous/index', get_defined_vars());
    }

    /** 服务端分页数据接口（action=data）：返回 JSON { total, html } */
    private function data() {
        $this->requirePermission('homogeneous.view');
        $db = DB::getInstance();
        [$page, $perPage] = $this->pageParams();
        $search = $_GET['search'] ?? '';
        $where = []; $params = [];
        if (isSupplier()) { $where[] = "m.created_by=?"; $params[] = userId(); }
        if ($search) { $where[] = "m.material_name LIKE ?"; $params[] = "%$search%"; }
        $reportSelects = $this->reportSelectsSql();
        $whereSql = $where ? " WHERE " . implode(' AND ', $where) : '';
        $total = (int)$db->single("SELECT COUNT(*) FROM homogeneous_material m" . $whereSql, $params);
        $offset = ($page - 1) * $perPage;
        $rows = $db->query(
            "SELECT m.*, COALESCE(s.name_short, s2.name_short) AS supplier_name_short, $reportSelects
             FROM homogeneous_material m
             LEFT JOIN supplier s ON m.supplier_id=s.id
             LEFT JOIN users u ON m.created_by=u.id
             LEFT JOIN supplier s2 ON u.supplier_id=s2.id" . $whereSql . " ORDER BY m.id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        $html = '';
        foreach ($rows as $m) { $m['env_status'] = $this->hmEnvStatus($m); $html .= $this->hmRowHtml($m); }
        $this->paginationResponse($total, $html);
    }

    private function reportSelectsSql(): string {
        return "
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='RoHS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS rohs_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='MSDS' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS msds_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='REACH' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS reach_expiry,
          (SELECT id FROM hm_report WHERE hm_id=m.id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_id,
          (SELECT expiry_date FROM hm_report WHERE hm_id=m.id AND report_type='HF' AND status='current' AND file_path IS NOT NULL AND file_path != '' ORDER BY version DESC LIMIT 1) AS hf_expiry";
    }

    /** 均质材料列表行 HTML（服务端渲染，保留报告状态/权限/按钮逻辑） */
    private function hmRowHtml($m): string {
        $html = '<tr>';
        $html .= colId($m['id']);
        $html .= '<td class="text-start"><a class="fw-bold text-primary" href="/homogeneous?action=usage&id=' . $m['id'] . '" title="查看使用该均质材料的物料">' . h($m['material_name']) . '</a></td>';
        $html .= '<td><span class="badge badge-soft-success">均质原材料</span></td>';
        if (!isSupplier()) {
            $html .= '<td><small>' . h($m['supplier_name_short'] ?: ($m['supplier_name'] ?? '-')) . '</small></td>';
        }
        $html .= '<td>' . h($m['weight_mg'] ?? '-') . ' ' . h($m['weight_unit'] ?? 'g') . '</td>';
        foreach (['RoHS', 'REACH', 'HF', 'MSDS'] as $rt) {
            $hasId = $m[strtolower($rt) . '_id'] ?? '';
            $expiry = $m[strtolower($rt) . '_expiry'] ?? '';
            $html .= '<td class="small px-1">';
            if (!$hasId) {
                $html .= '<span class="text-muted">无</span>';
            } elseif ($rt === 'MSDS') {
                $html .= '<span class="text-success" title="有MSDS">✓</span>';
            } elseif (!$expiry) {
                $html .= '<span class="text-muted" title="无有效期">-</span>';
            } elseif (strtotime($expiry) < time()) {
                $html .= '<a href="/homogeneous?action=edit&id=' . $m['id'] . '&step=3" class="badge bg-danger text-white" title="点击重新上传">过期</a>';
            } elseif (strtotime($expiry) <= strtotime('+30 days')) {
                $html .= '<a href="/homogeneous?action=edit&id=' . $m['id'] . '&step=3" class="badge bg-warning" title="点击更新">将到期</a>';
            } else {
                $html .= '<span class="badge bg-success text-white" title="' . h($expiry) . '">' . h(substr($expiry, 5)) . '</span>';
            }
            $html .= '</td>';
        }
        $html .= '<td>';
        if ($m['env_status'] === 'compliant') $html .= '<span class="badge badge-soft-success">合规</span>';
        elseif ($m['env_status'] === 'non_compliant') $html .= '<span class="badge badge-soft-danger">不合规</span>';
        else $html .= '<span class="badge badge-soft-warning">待评估</span>';
        $html .= '</td>';
        $html .= '<td class="nowrap">' . statusBadge($m['audit_status'] ?? 'N') . '</td>';
        $html .= '<td><small class="text-muted">' . h(substr($m['updated_at'] ?: $m['created_at'], 0, 10)) . '</small></td>';
        $html .= '<td><div class="btn-group btn-group-sm">';
        $html .= '<button class="btn-icon btn btn-sm btn-outline-success" title="复制" onclick="openCopyModal(' . $m['id'] . ',\'' . h(addslashes($m['material_name'])) . '\')"><i class="fas fa-copy"></i></button>';
        $html .= '<a href="/homogeneous?action=reports&id=' . $m['id'] . '" class="btn-icon btn btn-sm btn-outline-info" title="检测报告"><i class="fas fa-file-alt"></i></a>';
        $html .= '<a href="/homogeneous?action=compliance&id=' . $m['id'] . '" class="btn-icon btn btn-sm btn-outline-info" title="合规分析"><i class="fas fa-balance-scale"></i></a>';
        if (can('homogeneous.edit') && ($m['audit_status'] !== 'A' || isAdmin())) {
            $html .= '<a href="/homogeneous?action=edit&id=' . $m['id'] . '" class="btn-icon btn btn-sm btn-outline-primary" title="编辑"><i class="fas fa-edit"></i></a>';
        }
        if (can('homogeneous.delete') && ($m['audit_status'] !== 'A' || isAdmin())) {
            $html .= '<form method="post" style="display:contents"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $m['id'] . '">' . csrf_field() . '<button type="submit" class="btn-icon btn btn-sm btn-outline-danger" title="删除" onclick="return confirm(\'确认删除？\')"><i class="fas fa-trash"></i></button></form>';
        }
        if ($m['audit_status'] === 'S' && isAdmin()) {
            $html .= '<form method="post" style="display:contents"><input type="hidden" name="action" value="audit_hm"><input type="hidden" name="id" value="' . $m['id'] . '"><input type="hidden" name="audit_status" value="A">' . csrf_field() . '<button type="submit" class="btn-icon btn btn-sm btn-outline-success" title="批准" onclick="return confirm(\'确认批准？\')"><i class="fas fa-check"></i></button></form>';
            $html .= '<form method="post" style="display:contents"><input type="hidden" name="action" value="audit_hm"><input type="hidden" name="id" value="' . $m['id'] . '"><input type="hidden" name="audit_status" value="R">' . csrf_field() . '<button type="submit" class="btn-icon btn btn-sm btn-outline-danger" title="驳回" onclick="return confirm(\'确认驳回？\')"><i class="fas fa-times"></i></button></form>';
        }
        $html .= '</div></td></tr>';
        return $html;
    }
    private function hmEnvStatus($m): string {
        // 与列表「环保状态」列口径一致：RoHS/REACH/HF 均有未过期报告为合规，
        // 有任一报告但存在缺失或过期则为不合规，否则待评估
        $envTypes = ['RoHS','REACH','HF'];
        $allValid = true;
        $hasEnvReport = false;
        foreach ($envTypes as $rt) {
            $key = strtolower($rt);
            $hasId = $m[$key . '_id'] ?? '';
            $expiry = $m[$key . '_expiry'] ?? '';
            if ($expiry) {
                $hasEnvReport = true;
                if (strtotime($expiry) < time()) { $allValid = false; break; }
            } elseif ($hasId) {
                $hasEnvReport = true;
                $allValid = false;
            } else {
                $allValid = false;
            }
        }
        if ($hasEnvReport && $allValid) return 'compliant';
        if ($hasEnvReport) return 'non_compliant';
        return 'pending';
    }
    private function showMaterialUsage($hmId = 0) {
        $this->requirePermission('homogeneous.view');
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$item) { header('Location: /homogeneous'); exit; }
        if (isSupplier() && (int)($item['created_by'] ?? 0) !== userId()) { header('Location: /homogeneous?error=no_permission'); exit; }
        $where = "mc.substance_id=?";
        $params = [$hmId];
        if (isSupplier()) { $where .= " AND m.supplier_id=?"; $params[] = userSupplierId(); }
        $materials = $db->query("SELECT m.id, m.material_no, m.material_name, m.spec, m.mpn, m.category, m.weight_mg, m.weight_unit, m.env_status, m.updated_at,
            s.name AS supplier_name,
            mc.min_percentage, mc.max_percentage, mc.is_main_component, mc.remark
            FROM material_composition mc
            JOIN material m ON m.id=mc.material_id
            LEFT JOIN supplier s ON m.supplier_id=s.id
            WHERE $where ORDER BY m.id", $params);
        $title = '使用该均质材料的物料 - ' . $item['material_name'];
        $this->render('homogeneous/material_usage', get_defined_vars());
    }
    private function showForm($id = 0) {
        $this->requirePermission($id ? 'homogeneous.edit' : 'homogeneous.create');
        $db = DB::getInstance();
        $item = $id ? $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$id], true) : [];
        if ($id && !$item) { header('Location: /homogeneous'); exit; }
        if ($id) $this->requireNotApproved($item['audit_status'] ?? 'N', '/homogeneous');
        if ($id) $this->requireOwnership($item['created_by'] ?? 0, '/homogeneous');
        $composition = $id ? $db->query("SELECT hc.*, s.name_en FROM hm_composition hc LEFT JOIN substance s ON hc.cas_no=s.cas_no WHERE hc.hm_id=? ORDER BY hc.id", [$id]) : [];
        $reports = $id ? $db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version_group, version DESC", [$id]) : [];
        $currentStep = (int)($_GET['step'] ?? 1);
        $title = $id ? '编辑均质材料元素' : '新增均质原材料';
        $this->render('homogeneous/form', get_defined_vars());
    }
    private function saveComposition($hmId) {
        $db = DB::getInstance();
        $db->execute("DELETE FROM hm_composition WHERE hm_id=?", [$hmId]);
        $names = $_POST['comp_name'] ?? [];
        $namesCn = $_POST['comp_name_cn'] ?? [];
        $cass = $_POST['comp_cas'] ?? [];
        $pcts = $_POST['comp_pct'] ?? [];
        $mins = $_POST['comp_min'] ?? [];
        $maxs = $_POST['comp_max'] ?? [];
        foreach ($names as $i => $name) {
            if (empty(trim($name))) continue;
            $cas = $cass[$i] ?? '';
            $db->execute("INSERT INTO hm_composition (hm_id, substance_name, name_cn, cas_no, percentage, min_pct, max_pct) VALUES (?,?,?,?,?,?,?)",
                [$hmId, $name, $namesCn[$i] ?? '', $cas, (float)($pcts[$i] ?? 0), (float)($mins[$i] ?? 0), (float)($maxs[$i] ?? 0)]);
            if ($cas && preg_match('/^\d{2,7}-\d{2}-\d$/', $cas)) {
                $enName = trim($name);
                $cnName = trim($namesCn[$i] ?? '');
                $db->execute("INSERT OR IGNORE INTO substance (cas_no, name, name_en, is_controlled) VALUES (?,?,?,0)", [$cas, $cnName ?: $enName, $enName]);
                if ($cnName) {
                    $db->execute("UPDATE substance SET name=?, name_en=? WHERE cas_no=? AND (name IS NULL OR name='' OR name=name_en)", [$cnName, $enName, $cas]);
                }
            }
        }
    }
    private function upsertReport($hmId, $type, $no, $agency, $testDate, $expiryDate, $filePath, $fileSize) {
        $db = DB::getInstance();
        // supersede existing current report for same (hm_id, type)
        $current = $db->single("SELECT id, version, version_group FROM hm_report WHERE hm_id=? AND report_type=? AND status='current' ORDER BY version DESC LIMIT 1", [$hmId, $type], true);
        $newId = null;
        if ($current) {
            $version = (int)$current['version'] + 1;
            $vg = $current['version_group'];
        } else {
            $version = 1;
            $vg = $hmId . '_' . $type;
        }
        $db->execute("INSERT INTO hm_report (hm_id, report_no, report_type, test_agency, test_date, expiry_date, file_path, file_size, version, version_group, status) VALUES (?,?,?,?,?,?,?,?,?,?,'current')",
            [$hmId, $no, $type, $agency, $testDate, $expiryDate, $filePath, $fileSize, $version, $vg]);
        $newId = $db->lastInsertId();
        if ($current) {
            $db->execute("UPDATE hm_report SET status='superseded', superseded_by=? WHERE id=?", [$newId, $current['id']]);
        }
        return $newId;
    }

    private function saveReports($hmId) {
        $db = DB::getInstance();
        $files = $_FILES['hm_report_file'] ?? [];
        $missing = [];
        $required = ['RoHS', 'MSDS'];
        foreach ($required as $rt) {
            if (!empty($files['name'][$rt])) continue;
            $existing = $db->single("SELECT id FROM hm_report WHERE hm_id=? AND report_type=? AND status='current'", [$hmId, $rt]);
            if (!$existing) $missing[] = $rt;
        }
        $saved = 0;
        $nos = $_POST['hm_report_no'] ?? [];
        $agencies = $_POST['hm_test_agency'] ?? [];
        $dates = $_POST['hm_test_date'] ?? [];
        $expiries = $_POST['hm_expiry_date'] ?? [];
        foreach ($nos as $type => $no) {
            $no = trim($no);
            $filePath = '';
            $fileSize = 0;
            if (!empty($files['name'][$type]) && $files['error'][$type] === UPLOAD_ERR_OK) {
                $dir = uploadPath($_SESSION['username'] ?? 'unknown');
                $ext = strtolower(pathinfo($files['name'][$type], PATHINFO_EXTENSION));
                $origName = pathinfo($files['name'][$type], PATHINFO_FILENAME);
                $fileName = $origName . '_' . date('Ymd') . '.' . $ext;
                $filePath = $dir . '/' . $fileName;
                $n = 1;
                while (file_exists($filePath)) { $fileName = $origName . '_' . date('Ymd') . '_' . ($n++) . '.' . $ext; $filePath = $dir . '/' . $fileName; }
                move_uploaded_file($files['tmp_name'][$type], $filePath);
                $fileSize = $files['size'][$type];
            }
            if (!$filePath && !$no) continue;
            if (!$no) $no = 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $testDate = $dates[$type] ?? '';
            $expiryDate = $expiries[$type] ?? ($testDate ? date('Y-m-d', strtotime($testDate . ' +1 year')) : '');
            $this->upsertReport($hmId, $type, $no, $agencies[$type] ?? '', $testDate, $expiryDate, $filePath, $fileSize);
            $saved++;
        }
        if ($saved) {
            // auto-analyze
            (new ComplianceEngine())->analyzeHm($hmId);
        }
        if (!empty($missing)) {
            flash('warning', implode('、', $missing) . ' 报告未上传，请尽快补充');
        }
    }
    private function doCreate() {
        $this->requirePermission('homogeneous.create');
        $db = DB::getInstance();
        $step = (int)($_POST['step'] ?? 1);
        if ($step == 2) {
            $supplierId = isSupplier() ? userSupplierId() : 0;
            $db->execute("INSERT INTO homogeneous_material (material_name, supplier_name, weight_mg, weight_unit, supplier_id, created_by) VALUES (?,?,?,?,?,?)",
                [$_POST['material_name'] ?? '', $_POST['supplier_name'] ?? '', (float)($_POST['weight_mg'] ?? 0), $_POST['weight_unit'] ?? 'g', $supplierId, userId()]);
            $mid = $db->lastInsertId();
            logOp('create', 'homogeneous', $mid, '新增均质(步骤2)');
            header('Location: /homogeneous?action=edit&id=' . $mid . '&step=2'); exit;
        }
        $mid = (int)($_POST['id'] ?? 0);
        if ($mid) $this->requireUserRecord('homogeneous_material', $mid, 'created_by', '/homogeneous');
        if ($step == 1) { header('Location: /homogeneous?action=edit&id=' . $mid . '&step=1'); exit; }
        if ($step == 3) {
            $pcts = $_POST['comp_pct'] ?? [];
            $total = array_sum(array_map('floatval', $pcts));
            if ($total < 99.99 || $total > 100.01) {
                flash('error', "成分含量合计为" . number_format($total, 2) . "%，须等于100%才能继续");
                header('Location: /homogeneous?action=edit&id=' . $mid . '&step=2'); exit;
            }
            $this->saveComposition($mid); logOp('create', 'homogeneous', $mid, '保存成分(步骤3)'); header('Location: /homogeneous?action=edit&id=' . $mid . '&step=3'); exit;
        }
        if ($step >= 4) { $this->saveComposition($mid); $this->saveReports($mid); }
        logOp('create', 'homogeneous', $mid, '完成创建均质材料');
        if ($step >= 5) { flash('success', '均质材料创建成功'); header('Location: /homogeneous'); exit; }
        header('Location: /homogeneous?action=edit&id=' . $mid . '&step=4'); exit;
    }
    private function doUpdate() {
        $this->requirePermission('homogeneous.edit');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) return;
        $db = DB::getInstance();
        $hm = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$id], true);
        if (!$hm) { flash('error', '记录不存在'); header('Location: /homogeneous'); exit; }
        $this->requireNotApproved($hm['audit_status'] ?? 'N', '/homogeneous');
        $this->requireOwnership($hm['created_by'] ?? 0, '/homogeneous');
        $step = (int)($_POST['step'] ?? 1);
        if ($step == 2) {
            $supplierId = isSupplier() ? userSupplierId() : 0;
            $db->execute("UPDATE homogeneous_material SET material_name=?, supplier_name=?, weight_mg=?, weight_unit=?, supplier_id=?, updated_at=datetime('now','localtime') WHERE id=?",
                [$_POST['material_name'] ?? '', $_POST['supplier_name'] ?? '', (float)($_POST['weight_mg'] ?? 0), $_POST['weight_unit'] ?? 'g', $supplierId, $id]);
            logOp('update', 'homogeneous', $id, '更新基本信息(步骤2)');
            header('Location: /homogeneous?action=edit&id=' . $id . '&step=2'); exit;
        }
        if ($step == 1) {
            $db->execute("UPDATE homogeneous_material SET material_name=?, supplier_name=?, weight_mg=?, weight_unit=?, updated_at=datetime('now','localtime') WHERE id=?",
                [$_POST['material_name'] ?? '', $_POST['supplier_name'] ?? '', (float)($_POST['weight_mg'] ?? 0), $_POST['weight_unit'] ?? 'g', $id]);
            logOp('update', 'homogeneous', $id, '更新基本信息(步骤1)');
            header('Location: /homogeneous?action=edit&id=' . $id . '&step=1'); exit;
        }
        if ($step == 3) {
            $pcts = $_POST['comp_pct'] ?? [];
            $total = array_sum(array_map('floatval', $pcts));
            if ($total < 99.99 || $total > 100.01) {
                flash('error', "成分含量合计为" . number_format($total, 2) . "%，须等于100%才能继续");
                header('Location: /homogeneous?action=edit&id=' . $id . '&step=2'); exit;
            }
            $this->saveComposition($id); logOp('update', 'homogeneous', $id, '保存成分(步骤3)'); header('Location: /homogeneous?action=edit&id=' . $id . '&step=3'); exit;
        }
        if ($step >= 4) { $this->saveComposition($id); $this->saveReports($id); }
        logOp('update', 'homogeneous', $id, '完成更新均质材料');
        if ($step >= 5) { flash('success', '均质材料更新成功'); header('Location: /homogeneous'); exit; }
        header('Location: /homogeneous?action=edit&id=' . $id . '&step=4'); exit;
    }
    private function doDeleteReport() {
        $this->requirePermission('homogeneous.edit');
        $rid = (int)($_REQUEST['report_id'] ?? 0);
        $hmId = (int)($_REQUEST['id'] ?? 0);
        if ($rid) {
            $db = DB::getInstance();
            $hm = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
            if (!$hm) { flash('error', '均质材料不存在'); header('Location: /homogeneous?action=reports&id=' . $hmId); exit; }
            $this->requireNotApproved($hm['audit_status'] ?? 'N', '/homogeneous?action=reports&id=' . $hmId);
            $this->requireOwnership($hm['created_by'] ?? 0, '/homogeneous?action=reports&id=' . $hmId);
            $r = $db->single("SELECT * FROM hm_report WHERE id=?", [$rid], true);
            if ($r && $r['file_path'] && file_exists($r['file_path'])) unlink($r['file_path']);
            $db->execute("DELETE FROM hm_report WHERE id=?", [$rid]);
            flash('success', '报告已删除');
        }
        header('Location: /homogeneous?action=edit&id=' . $hmId . '&step=3'); exit;
    }
    private function doDelete() {
        $this->requirePermission('homogeneous.delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db = DB::getInstance();
            $hm = $db->single("SELECT created_by FROM homogeneous_material WHERE id=?", [$id], true);
            if (!$hm) { flash('error', '均质材料不存在'); header('Location: /homogeneous'); exit; }
            $this->requireOwnership($hm['created_by'] ?? 0, '/homogeneous');
            $reports = $db->query("SELECT * FROM hm_report WHERE hm_id=?", [$id]);
            foreach ($reports as $r) {
                if ($r['file_path'] && file_exists($r['file_path'])) unlink($r['file_path']);
                $db->execute("DELETE FROM hm_report_element WHERE hm_report_id=?", [$r['id']]);
            }
            $db->execute("DELETE FROM hm_report WHERE hm_id=?", [$id]);
            $db->execute("DELETE FROM hm_composition WHERE hm_id=?", [$id]);
            $db->execute("DELETE FROM material_composition WHERE substance_id=?", [$id]);
            $db->execute("DELETE FROM homogeneous_material WHERE id=?", [$id]);
            logOp('delete', 'homogeneous', $id, '删除均质材料');
            flash('success', '已删除');
        }
        header('Location: /homogeneous'); exit;
    }

    private function doAuditHm() {
        $this->requirePermission('homogeneous.audit');
        if (!isAdmin()) { flash('error', '无权操作'); header('Location: /homogeneous'); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['audit_status'] ?? '';
        if ($id && in_array($status, ['A', 'R'])) {
            $db = DB::getInstance();
            $db->execute("UPDATE homogeneous_material SET audit_status=?, updated_at=datetime('now','localtime') WHERE id=?", [$status, $id]);
            if ($status === 'A') {
                $db->execute("UPDATE homogeneous_material SET env_status='compliant' WHERE id=? AND env_status='pending'", [$id]);
            }
            logOp('audit', 'homogeneous', $id, "审核均质材料: $status");
            flash('success', $status === 'A' ? '已批准' : '已驳回');
        }
        header('Location: /homogeneous'); exit;
    }

    private function showCompliance($hmId) {
        $this->requirePermission('compliance.view');
        $db = DB::getInstance();
        $item = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$item) { header('Location: /homogeneous'); exit; }
        $this->requireOwnership($item['created_by'] ?? 0, '/homogeneous');
        $composition = $db->query("SELECT * FROM hm_composition WHERE hm_id=?", [$hmId]);
        // determine report-based env status
        $reportEnvStatus = 'pending';
        $envTypes = ['RoHS','REACH','HF'];
        $allValid = true; $hasAny = false;
        foreach ($envTypes as $rt) {
            $rpt = $db->single("SELECT expiry_date FROM hm_report WHERE hm_id=? AND report_type=? AND status='current' ORDER BY version DESC LIMIT 1", [$hmId, $rt], true);
            if ($rpt && $rpt['expiry_date']) {
                $hasAny = true;
                if (strtotime($rpt['expiry_date']) < time()) { $allValid = false; break; }
            } else {
                $allValid = false;
            }
        }
        if ($hasAny && $allValid) $reportEnvStatus = 'compliant';
        elseif ($hasAny) $reportEnvStatus = 'non_compliant';
        // Load latest analysis records from DB
        $records = $db->query("SELECT ar.*, COALESCE(rl.name, '手工录入限值') AS regulation_name, rl.regulation_no FROM analysis_record ar LEFT JOIN regulation_lib rl ON ar.regulation_id=rl.id WHERE ar.hm_id=? ORDER BY ar.substance_name, ar.analyze_result DESC", [$hmId]);
        $results = [];
        $seen = [];
        foreach ($records as $r) {
            $key = $r['substance_name'] ?: $r['cas_no'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $nameCn = $db->single("SELECT name FROM substance WHERE cas_no=?", [$r['cas_no']], true);
            $results[] = [
                'substance' => $r['substance_name'] ?: ($r['remark'] ?? ''),
                'name_cn' => $nameCn['name'] ?? '',
                'cas' => $r['cas_no'] ?? '',
                'detected' => $r['detected_value'] ?? 0,
                'limit' => $r['limit_value'] ?? 0,
                'result' => $r['analyze_result'],
                'result_text' => (new ComplianceEngine())->resultText($r['analyze_result']),
                'analyzed_at' => $r['analyzed_at'],
            ];
        }
        $title = '合规分析- ' . $item['material_name'];
        $this->render('homogeneous/compliance', get_defined_vars());
    }
    private function showReports($hmId, $activeReportId = 0) {
        $this->requirePermission('homogeneous.view');
        $db = DB::getInstance();
        $db->execute("CREATE TABLE IF NOT EXISTS hm_report_element (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hm_report_id INTEGER REFERENCES hm_report(id),
            substance_name TEXT, cas_no TEXT,
            content_value REAL, unit TEXT DEFAULT 'mg/kg',
            limit_value REAL, limit_unit TEXT DEFAULT 'mg/kg',
            detection_method TEXT
        )");
        $item = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
        if (!$item) { header('Location: /homogeneous'); exit; }
        $this->requireOwnership($item['created_by'] ?? 0, '/homogeneous');
        $reports = $db->query("SELECT * FROM hm_report WHERE hm_id=? ORDER BY version_group, version DESC", [$hmId]);
        $hasReport = (int)$db->single("SELECT COUNT(*) FROM hm_report WHERE hm_id=?", [$hmId]);
        $hasMeasured = (int)$db->single(
            "SELECT COUNT(*) FROM hm_report_element hre
             JOIN hm_report hr ON hre.hm_report_id = hr.id
             WHERE hr.hm_id=? AND hre.content_value IS NOT NULL AND hre.content_value > 0",
            [$hmId]
        );
        $noUploadData = !$hasReport && !$hasMeasured;
        // sort so RoHS comes first
        usort($reports, function($a, $b) {
            $order = ['RoHS' => 0, 'REACH' => 1, 'HF' => 2, 'MSDS' => 3];
            $oa = $order[$a['report_type']] ?? 9;
            $ob = $order[$b['report_type']] ?? 9;
            if ($oa !== $ob) return $oa - $ob;
            return ($b['version'] ?? 1) - ($a['version'] ?? 1);
        });
        $composition = $db->query("SELECT * FROM hm_composition WHERE hm_id=? ORDER BY id", [$hmId]);
        // load elements per report (fallback to regulation defaults)
        $reportElements = [];
        foreach ($reports as $r) {
            $elems = $db->query("SELECT * FROM hm_report_element WHERE hm_report_id=? ORDER BY id", [$r['id']]);
            if (empty($elems)) {
                $regName = '%' . $r['report_type'] . '%';
                $reg = $db->single("SELECT id FROM regulation_lib WHERE name LIKE ? AND status='published' ORDER BY id LIMIT 1", [$regName], true);
                if ($reg) {
                    $elems = $db->query("SELECT id AS __orig, substance_name, cas_no, limit_value, limit_unit, article_no, title, content_text, exemption_desc FROM regulation_content WHERE regulation_id=? ORDER BY sort_order, id", [$reg['id']]);
                }
            }
            if ($r['report_type'] === 'HF') {
                $hfDefaults = [
                    ['substance_name' => '氟（F）', 'cas_no' => '7782-41-4', 'limit_value' => 900, 'limit_unit' => 'mg/kg'],
                    ['substance_name' => '氯（Cl）', 'cas_no' => '7782-50-5', 'limit_value' => 900, 'limit_unit' => 'mg/kg'],
                    ['substance_name' => '溴（Br）', 'cas_no' => '7726-95-6', 'limit_value' => 900, 'limit_unit' => 'mg/kg'],
                    ['substance_name' => '碘（I）', 'cas_no' => '7553-56-2', 'limit_value' => 900, 'limit_unit' => 'mg/kg'],
                    ['substance_name' => '砹（At）', 'cas_no' => '7440-68-8', 'limit_value' => 0, 'limit_unit' => 'mg/kg'],
                ];
                $existingCas = [];
                foreach ($elems as $e) {
                    if (!empty($e['cas_no'])) {
                        $existingCas[] = $e['cas_no'];
                    }
                }
                foreach ($hfDefaults as $def) {
                    if (!in_array($def['cas_no'], $existingCas)) {
                        $elems[] = $def;
                    }
                }
            }
            $reportElements[$r['id']] = $elems;
        }
        // load admin internal standards + official limits for the 3-way comparison
        $internalStd = [];
        foreach ($db->query("SELECT report_type, cas_no, substance_name, limit_value FROM internal_standard") as $is) {
            $internalStd[$is['report_type']][$is['cas_no']] = (float)$is['limit_value'];
        }
        $stdLimits = [];
        foreach ($reports as $r) {
            $byCas = [];
            foreach (stdOfficialLimits($r['report_type']) as $def) {
                $byCas[$def['cas_no']] = [
                    'official' => (float)$def['limit_value'],
                    'internal' => $internalStd[$r['report_type']][$def['cas_no']] ?? null,
                ];
            }
            $stdLimits[$r['id']] = $byCas;
        }
        $title = '检测报告 ' . $item['material_name'];
        $this->render('homogeneous/reports', get_defined_vars());
    }
    private function doSaveElements() {
        $this->requirePermission('homogeneous.edit');
        $hmReportId = (int)($_POST['hm_report_id'] ?? 0);
        $hmId = (int)($_POST['hm_id'] ?? 0);
        if ($hmReportId) {
            $db = DB::getInstance();
            $hm = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$hmId], true);
            if (!$hm) { flash('error', '均质材料不存在'); header('Location: /homogeneous'); exit; }
            $this->requireNotApproved($hm['audit_status'] ?? 'N', '/homogeneous');
            $this->requireOwnership($hm['created_by'] ?? 0, '/homogeneous');
            $db->execute("DELETE FROM hm_report_element WHERE hm_report_id=?", [$hmReportId]);
            $names = $_POST['substance_name'] ?? [];
            foreach ($names as $i => $name) {
                if (trim($name ?? '') === '') continue;
                $db->execute("INSERT INTO hm_report_element (hm_report_id, substance_name, cas_no, content_value, unit, limit_value, limit_unit) VALUES (?,?,?,?,?,?,?)",
                    [$hmReportId, $name, $_POST['cas_no'][$i] ?? '', (float)($_POST['content_value'][$i] ?? 0),
                     $_POST['unit'][$i] ?? 'mg/kg', (float)($_POST['limit_value'][$i] ?? 0),
                     $_POST['limit_unit'][$i] ?? 'mg/kg']);
            }
            flash('success', '元素数据已保存');
        }
        header('Location: /homogeneous?action=reports&id=' . $hmId . '&report_id=' . $hmReportId);
        exit;
    }
    private function doAnalyze() {
        $this->requirePermission('compliance.analyze');
        $hmId = (int)($_POST['hm_id'] ?? 0);
        if ($hmId) {
            $this->requireUserRecord('homogeneous_material', $hmId, 'created_by', '/homogeneous');
            $db = DB::getInstance();
            $hasReport = (int)$db->single("SELECT COUNT(*) FROM hm_report WHERE hm_id=?", [$hmId]);
            $hasMeasured = (int)$db->single(
                "SELECT COUNT(*) FROM hm_report_element hre
                 JOIN hm_report hr ON hre.hm_report_id = hr.id
                 WHERE hr.hm_id=? AND hre.content_value IS NOT NULL AND hre.content_value > 0",
                [$hmId]
            );
            if (!$hasReport && !$hasMeasured) {
                flash('error', '未上传报告和无物质实测数据，请操作');
                header('Location: /homogeneous?action=compliance&id=' . $hmId); exit;
            }
            $engine = new ComplianceEngine();
            $results = $engine->analyzeHm($hmId);
            flash('success', '分析完成，共 ' . count($results) . ' 条');
        }
        header('Location: /homogeneous?action=compliance&id=' . $hmId); exit;
    }
    private function doImport() {
        $this->requirePermission('homogeneous.create');
        if (empty($_FILES['import_file']['tmp_name'])) { flash('error', '请选择文件'); header('Location: /homogeneous'); exit; }
        $f = $_FILES['import_file'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xls','xlsx'])) { flash('error', '仅支持Excel文件'); header('Location: /homogeneous'); exit; }
        require_once __DIR__ . '/../../vendor/autoload.php';
        try {
            $reader = $ext === 'xlsx' ? new \PhpOffice\PhpSpreadsheet\Reader\Xlsx() : new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            $rows = $reader->load($f['tmp_name'])->getActiveSheet()->toArray();
        } catch (Exception $e) { flash('error', '文件解析失败'); header('Location: /homogeneous'); exit; }
        if (empty($rows)) { flash('error', '模板为空'); header('Location: /homogeneous'); exit; }
        $headerRow = -1;
        foreach ($rows as $idx => $row) {
            $rowStr = implode(',', array_map('strval', $row));
            if (str_contains($rowStr, '均质材料ID') && str_contains($rowStr, '均质材料名称')) {
                $headerRow = $idx; break;
            }
        }
        if ($headerRow < 0) { flash('error', '模板不匹配，请使用物料均质模板'); header('Location: /homogeneous'); exit; }
        $db = DB::getInstance();
        $imported = 0; $updated = 0; $errors = [];
        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $hmId = (int)($row[0] ?? 0);
            $hmName = trim($row[1] ?? '');
            $hmSupplier = trim($row[2] ?? '');
            $hmWeight = (float)($row[3] ?? 0);
            $substanceName = trim($row[4] ?? '');
            $nameCn = trim($row[5] ?? '');
            $casNo = trim($row[6] ?? '');
            $percentage = (float)($row[7] ?? 0);
            $minPct = (float)($row[8] ?? 0);
            $maxPct = (float)($row[9] ?? 0);
            if (empty($hmName)) continue;
            if ($hmId) {
                $exists = $db->single("SELECT id FROM homogeneous_material WHERE id=?", [$hmId], true);
                if (!$exists) $hmId = 0;
            }
            if (!$hmId) {
                $hm = $db->single("SELECT id FROM homogeneous_material WHERE material_name=?", [$hmName], true);
                if ($hm) { $hmId = $hm['id']; }
            }
            if ($hmId) {
                if (isSupplier()) {
                    $own = $db->single("SELECT created_by FROM homogeneous_material WHERE id=?", [$hmId], true);
                    if (!$own || (int)$own['created_by'] !== userId()) {
                        $errors[] = "第".($i+1)."行 均质材料ID{$hmId}已存在且不属于当前用户";
                        continue;
                    }
                }
                $supplierId = 0;
                if ($hmSupplier) { $supplierId = QueryRegistry::supplierIdByName($hmSupplier) ?? 0; }
                $db->execute("UPDATE homogeneous_material SET supplier_name=?, weight_mg=?, supplier_id=?, updated_at=datetime('now','localtime') WHERE id=?",
                    [$hmSupplier, $hmWeight, $supplierId, $hmId]);
                $updated++;
            } else {
                $supplierId = 0;
                if ($hmSupplier) { $supplierId = QueryRegistry::supplierIdByName($hmSupplier) ?? 0; }
                $db->execute("INSERT INTO homogeneous_material (material_name, supplier_name, weight_mg, supplier_id, env_status, created_by) VALUES (?,?,?,?,?,?)",
                    [$hmName, $hmSupplier, $hmWeight, $supplierId, 'pending', userId()]);
                $hmId = $db->lastInsertId();
                $imported++;
            }
            if ($hmId && (!empty($substanceName) || !empty($nameCn))) {
                $matchKey = !empty($substanceName) ? $substanceName : $nameCn;
                $exists = $db->single("SELECT id FROM hm_composition WHERE hm_id=? AND (substance_name=? OR name_cn=?)", [$hmId, $matchKey, $matchKey], true);
                if ($exists) {
                    $db->execute("UPDATE hm_composition SET substance_name=?, name_cn=?, cas_no=?, percentage=?, min_pct=?, max_pct=? WHERE id=?",
                        [$substanceName, $nameCn, $casNo, $percentage, $minPct, $maxPct, $exists['id']]);
                } else {
                    $db->execute("INSERT INTO hm_composition (hm_id, substance_name, name_cn, cas_no, percentage, min_pct, max_pct) VALUES (?,?,?,?,?,?,?)",
                        [$hmId, $substanceName, $nameCn, $casNo, $percentage, $minPct, $maxPct]);
                }
            }
        }
        logOp('import', 'homogeneous_material', 0, "批量导入$imported 个均质材料, 更新$updated 个均质材料");
        $msg = "成功导入$imported 个均质材料";
        if ($updated > 0) $msg .= ", 更新$updated 个均质材料";
        if (!empty($errors)) $msg .= '。' . implode('; ', array_slice($errors, 0, 5));
        flash($imported > 0 || $updated > 0 ? 'success' : 'error', $msg);
        header('Location: /homogeneous'); exit;
    }
    private function doCopy() {
        $this->requirePermission('homogeneous.create');
        $sourceId = (int)($_POST['source_id'] ?? 0);
        $newName = trim($_POST['new_name'] ?? '');
        if (!$sourceId || !$newName) { flash('error', '参数错误'); header('Location: /homogeneous'); exit; }
        $db = DB::getInstance();
        $source = $db->single("SELECT * FROM homogeneous_material WHERE id=?", [$sourceId], true);
        if (!$source) { flash('error', '原均质材料不存在'); header('Location: /homogeneous'); exit; }
        if (isSupplier() && (int)($source['created_by'] ?? 0) !== userId()) { flash('error', '无权操作'); header('Location: /homogeneous'); exit; }
        $db->execute("INSERT INTO homogeneous_material (material_name, supplier_name, weight_mg, weight_unit, supplier_id, env_status, audit_status, created_by) VALUES (?,?,?,?,?,?,?,?)",
            [$newName, $source['supplier_name'], $source['weight_mg'], $source['weight_unit'], $source['supplier_id'], 'pending', 'N', userId()]);
        $newId = $db->lastInsertId();
        $composition = $db->query("SELECT * FROM hm_composition WHERE hm_id=?", [$sourceId]);
        foreach ($composition as $c) {
            $db->execute("INSERT INTO hm_composition (hm_id, substance_name, name_cn, cas_no, percentage, min_pct, max_pct) VALUES (?,?,?,?,?,?,?)",
                [$newId, $c['substance_name'], $c['name_cn'], $c['cas_no'], $c['percentage'], $c['min_pct'], $c['max_pct']]);
        }
        logOp('create', 'homogeneous', $newId, "复制自ID{$sourceId}: {$newName}");
        flash('success', "均质材料「{$newName}」已复制成功");
        header('Location: /homogeneous'); exit;
    }
}

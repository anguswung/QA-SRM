-- 绿色产品管理系统GPM - 数据库Schema
-- SQLite3 compatible

PRAGMA journal_mode=WAL;
PRAGMA foreign_keys=ON;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    realname TEXT,
    role TEXT DEFAULT 'supplier',
    email TEXT,
    phone TEXT,
    supplier_id INTEGER,
    status TEXT DEFAULT 'active',
    password_changed INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 角色表
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    permissions TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 供应商表
CREATE TABLE IF NOT EXISTS supplier (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    name_short TEXT,
    credit_code TEXT,
    contact_person TEXT,
    contact_phone TEXT,
    address TEXT,
    level TEXT DEFAULT 'C',
    blacklist INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 供应商联系人
CREATE TABLE IF NOT EXISTS supplier_contact (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER REFERENCES supplier(id),
    name TEXT,
    position TEXT,
    phone TEXT,
    mobile TEXT,
    email TEXT,
    is_primary INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 供应商评价
CREATE TABLE IF NOT EXISTS supplier_rating (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER REFERENCES supplier(id),
    rating_date TEXT,
    score REAL,
    dimension TEXT,
    comment TEXT,
    evaluator_id INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 均质材料报告表
CREATE TABLE IF NOT EXISTS hm_report (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hm_id INTEGER REFERENCES homogeneous_material(id),
    report_no TEXT,
    report_type TEXT,
    test_agency TEXT,
    test_date TEXT,
    expiry_date TEXT,
    file_path TEXT,
    file_size INTEGER,
    version INTEGER DEFAULT 1,
    version_group TEXT,
    status TEXT DEFAULT 'current',
    superseded_by INTEGER REFERENCES hm_report(id),
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 均质材料报告元素表
CREATE TABLE IF NOT EXISTS hm_report_element (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hm_report_id INTEGER REFERENCES hm_report(id),
    substance_name TEXT,
    cas_no TEXT,
    content_value REAL,
    unit TEXT DEFAULT 'mg/kg',
    limit_value REAL,
    limit_unit TEXT DEFAULT 'mg/kg',
    detection_method TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 均质材料成分表
CREATE TABLE IF NOT EXISTS hm_composition (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hm_id INTEGER REFERENCES homogeneous_material(id),
    substance_name TEXT,
    name_cn TEXT DEFAULT '',
    cas_no TEXT,
    molecular_formula TEXT,
    percentage REAL,
    min_pct REAL DEFAULT 0,
    max_pct REAL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 均质材料表 (独立于物料表)
CREATE TABLE IF NOT EXISTS homogeneous_material (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_name TEXT NOT NULL,
    spec TEXT DEFAULT '均质原材料',
    supplier_id INTEGER DEFAULT 0,
    weight_mg REAL,
    weight_unit TEXT DEFAULT 'g',
    supplier_name TEXT DEFAULT '',
    cas_no TEXT DEFAULT '',
    env_status TEXT DEFAULT 'pending',
    audit_status TEXT DEFAULT 'N',
    valid_status TEXT DEFAULT 'Valid',
    created_by INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物料与均质材料关联表
CREATE TABLE IF NOT EXISTS material_homogeneous (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER NOT NULL REFERENCES material(id),
    hm_id INTEGER NOT NULL REFERENCES homogeneous_material(id),
    usage_mg REAL DEFAULT 0,
    usage_unit TEXT DEFAULT 'g',
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    UNIQUE(material_id, hm_id)
);

-- 物料分类
CREATE TABLE IF NOT EXISTS material_category (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER REFERENCES material_category(id),
    sort_order INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物料表
CREATE TABLE IF NOT EXISTS material (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_no TEXT UNIQUE,
    material_name TEXT NOT NULL,
    spec TEXT,
    mpn TEXT,
    category TEXT,
    supplier_id INTEGER REFERENCES supplier(id),
    unit_price REAL,
    unit_weight REAL DEFAULT 0,
    quantity REAL DEFAULT 1,
    weight_mg REAL,
    weight_unit TEXT DEFAULT 'g',
    env_status TEXT DEFAULT 'non_compliant',
    audit_status TEXT DEFAULT 'N',
    audit_user TEXT,
    audit_time TEXT,
    audit_comment TEXT,
    valid_status TEXT DEFAULT 'Valid',
    mcd_file_path TEXT DEFAULT '',
    created_by INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物料成分
CREATE TABLE IF NOT EXISTS material_composition (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    substance_id INTEGER,
    substance_name TEXT,
    cas_no TEXT,
    min_percentage REAL,
    max_percentage REAL,
    is_main_component INTEGER DEFAULT 0,
    remark TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物料审核日志
CREATE TABLE IF NOT EXISTS material_audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    action TEXT,
    comment TEXT,
    operator_id INTEGER,
    operated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 添加剂
CREATE TABLE IF NOT EXISTS additive (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT,
    category_name_en TEXT,
    substance_id INTEGER REFERENCES substance(id),
    substance_name TEXT,
    min_value REAL,
    max_value REAL,
    material_category_id INTEGER REFERENCES material_category(id),
    remark TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 产品表 (BOM)
CREATE TABLE IF NOT EXISTS part (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    part_no TEXT NOT NULL UNIQUE,
    part_name TEXT NOT NULL,
    spec TEXT,
    parent_id INTEGER REFERENCES part(id),
    unit TEXT,
    sort_order INTEGER DEFAULT 0,
    supplier_id INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    mcd_file_path TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 产品物料关联 (BOM)
CREATE TABLE IF NOT EXISTS part_material (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    part_id INTEGER NOT NULL REFERENCES part(id),
    material_id INTEGER NOT NULL REFERENCES material(id),
    quantity REAL DEFAULT 1,
    unit TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- BOM变更记录
CREATE TABLE IF NOT EXISTS bom_change_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    part_id INTEGER REFERENCES part(id),
    change_type TEXT,
    material_id INTEGER,
    old_quantity REAL,
    new_quantity REAL,
    operator_id INTEGER,
    operated_at TEXT DEFAULT (datetime('now','localtime')),
    remark TEXT
);

-- 检测报告
CREATE TABLE IF NOT EXISTS test_report (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    report_no TEXT NOT NULL UNIQUE,
    report_type TEXT,
    report_version TEXT DEFAULT 'V1.0',
    test_agency TEXT,
    test_date TEXT,
    issue_date TEXT,
    effective_date TEXT,
    expiry_date TEXT,
    file_path TEXT,
    file_size INTEGER,
    audit_status TEXT DEFAULT 'pending',
    audit_user TEXT,
    audit_time TEXT,
    audit_comment TEXT,
    version INTEGER DEFAULT 1,
    created_by INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 检测元素含量
CREATE TABLE IF NOT EXISTS element_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER REFERENCES test_report(id),
    substance_name TEXT,
    cas_no TEXT,
    content_value REAL,
    unit TEXT DEFAULT 'mg/kg',
    limit_value REAL,
    limit_unit TEXT,
    is_over_limit INTEGER DEFAULT 0,
    detection_method TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 文档分类
CREATE TABLE IF NOT EXISTS document_category (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    parent_id INTEGER REFERENCES document_category(id),
    validity_days INTEGER DEFAULT 365,
    is_required INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 文档提醒配置
CREATE TABLE IF NOT EXISTS doc_reminder (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    days_before INTEGER DEFAULT 30,
    enabled INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 文档对比记录
CREATE TABLE IF NOT EXISTS document_comparison (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    document_id INTEGER,
    compare_type TEXT,
    result_summary TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 法规库
CREATE TABLE IF NOT EXISTS regulation_lib (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    regulation_no TEXT UNIQUE,
    name TEXT NOT NULL,
    name_en TEXT,
    issuing_body TEXT,
    publish_date TEXT,
    effective_date TEXT,
    category TEXT,
    status TEXT DEFAULT 'draft',
    description TEXT,
    content_text TEXT,
    version TEXT DEFAULT 'V1.0',
    is_focus INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 法规管控条款
CREATE TABLE IF NOT EXISTS regulation_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    regulation_id INTEGER REFERENCES regulation_lib(id),
    article_no TEXT,
    title TEXT,
    content_text TEXT,
    substance_name TEXT,
    cas_no TEXT,
    limit_value REAL,
    limit_unit TEXT DEFAULT 'mg/kg',
    exemption_desc TEXT,
    effective_date TEXT,
    sort_order INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物质库
CREATE TABLE IF NOT EXISTS substance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cas_no TEXT NOT NULL UNIQUE,
    ec_no TEXT,
    index_no TEXT,
    name TEXT NOT NULL,
    name_en TEXT,
    molecular_formula TEXT,
    alias TEXT,
    substance_group_id INTEGER REFERENCES substance_group(id),
    is_controlled INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物质分组
CREATE TABLE IF NOT EXISTS substance_group (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    parent_id INTEGER REFERENCES substance_group(id),
    sort_order INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 用途
CREATE TABLE IF NOT EXISTS maybe_use (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 物质用途关联
CREATE TABLE IF NOT EXISTS substance_maybe_use (
    substance_id INTEGER REFERENCES substance(id),
    maybe_use_id INTEGER REFERENCES maybe_use(id),
    PRIMARY KEY (substance_id, maybe_use_id)
);

-- 分析配置
CREATE TABLE IF NOT EXISTS analysis_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    regulation_ids TEXT,
    focus_only INTEGER DEFAULT 0,
    auto_calc INTEGER DEFAULT 1,
    created_by INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 分析记录
CREATE TABLE IF NOT EXISTS analysis_record (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER,
    hm_id INTEGER,
    regulation_id INTEGER REFERENCES regulation_lib(id),
    analyze_result INTEGER DEFAULT 0,
    substance_name TEXT,
    cas_no TEXT,
    detected_value REAL,
    limit_value REAL,
    is_manual INTEGER DEFAULT 0,
    manual_result TEXT,
    remark TEXT,
    analyzed_at TEXT,
    analyst_id INTEGER
);

-- 豁免记录
CREATE TABLE IF NOT EXISTS exemption_record (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    substance_id INTEGER REFERENCES substance(id),
    regulation_id INTEGER REFERENCES regulation_lib(id),
    content_id INTEGER REFERENCES regulation_content(id),
    exempt_id TEXT,
    exempt_name TEXT,
    is_limit INTEGER DEFAULT 0,
    remark TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- BPM流程模板
CREATE TABLE IF NOT EXISTS bpm_template (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    category TEXT,
    status TEXT DEFAULT 'draft',
    version TEXT DEFAULT 'V1.0',
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- BPM流程节点
CREATE TABLE IF NOT EXISTS bpm_node (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER REFERENCES bpm_template(id),
    node_name TEXT NOT NULL,
    node_type TEXT DEFAULT 'approval',
    sort_order INTEGER,
    role_required TEXT,
    timeout_hours INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- BPM流程实例
CREATE TABLE IF NOT EXISTS bpm_instance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER REFERENCES bpm_template(id),
    title TEXT,
    ref_type TEXT,
    ref_id INTEGER,
    initiator_id INTEGER,
    current_node_id INTEGER,
    priority INTEGER DEFAULT 0,
    due_date TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now','localtime')),
    completed_at TEXT
);

-- BPM流程任务
CREATE TABLE IF NOT EXISTS bpm_task (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instance_id INTEGER REFERENCES bpm_instance(id),
    node_id INTEGER REFERENCES bpm_node(id),
    assignee_id INTEGER,
    status TEXT DEFAULT 'pending',
    comment TEXT,
    processed_at TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- BPM流程代理
CREATE TABLE IF NOT EXISTS bpm_agent (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    principal_id INTEGER,
    agent_id INTEGER,
    template_ids TEXT DEFAULT '*',
    start_date TEXT,
    end_date TEXT,
    status TEXT DEFAULT 'active',
    remark TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 任务附件
CREATE TABLE IF NOT EXISTS task_attachment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instance_id INTEGER REFERENCES bpm_instance(id),
    file_name TEXT,
    file_path TEXT,
    uploaded_by INTEGER REFERENCES users(id),
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 冲突矿产调查
CREATE TABLE IF NOT EXISTS conflict_mineral_survey (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER REFERENCES supplier(id),
    survey_no TEXT NOT NULL UNIQUE,
    survey_date TEXT,
    status TEXT DEFAULT 'draft',
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 冶炼厂清单
CREATE TABLE IF NOT EXISTS smelter_list (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER REFERENCES conflict_mineral_survey(id),
    smelter_name TEXT NOT NULL,
    smelter_location TEXT,
    metal_type TEXT,
    mineral_source TEXT,
    conflict_region INTEGER DEFAULT 0,
    certification TEXT,
    status TEXT DEFAULT 'pending',
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 元素供应商
CREATE TABLE IF NOT EXISTS element_supplier (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER REFERENCES conflict_mineral_survey(id),
    element_type TEXT,
    supplier_name TEXT,
    is_smelter INTEGER DEFAULT 0,
    smelter_id INTEGER REFERENCES smelter_list(id),
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 预警记录
CREATE TABLE IF NOT EXISTS warning_record (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_id INTEGER REFERENCES material(id),
    report_id INTEGER REFERENCES test_report(id),
    warning_level TEXT,
    warning_type TEXT,
    warning_desc TEXT,
    days_left INTEGER,
    status TEXT DEFAULT 'pending',
    solved INTEGER DEFAULT 0,
    task_assignee TEXT,
    notified_at TEXT,
    closed_at TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 通知公告
CREATE TABLE IF NOT EXISTS notice (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT,
    publisher_id INTEGER,
    publisher_name TEXT,
    publish_time TEXT,
    status TEXT DEFAULT 'published',
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 通知附件
CREATE TABLE IF NOT EXISTS notice_attachment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notice_id INTEGER REFERENCES notice(id),
    file_name TEXT,
    file_path TEXT,
    file_size INTEGER,
    upload_user TEXT,
    create_time TEXT DEFAULT (datetime('now','localtime'))
);

-- 通知已读记录
CREATE TABLE IF NOT EXISTS notice_read_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    notice_id INTEGER REFERENCES notice(id),
    supplier_id INTEGER REFERENCES supplier(id),
    read_time TEXT DEFAULT (datetime('now','localtime'))
);

-- 问卷
CREATE TABLE IF NOT EXISTS survey (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    expiration_date TEXT,
    status TEXT DEFAULT 'active',
    created_by INTEGER,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 问卷回复
CREATE TABLE IF NOT EXISTS survey_response (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER REFERENCES survey(id),
    supplier_id INTEGER REFERENCES supplier(id),
    supplier_name TEXT,
    file_name TEXT,
    file_path TEXT,
    submit_status TEXT DEFAULT '0',
    reviewer TEXT,
    review_time TEXT,
    review_comment TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 合规规则表
CREATE TABLE IF NOT EXISTS compliance_rule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_type TEXT NOT NULL DEFAULT 'all',
    rule_type TEXT NOT NULL DEFAULT 'mandatory',
    doc_category_code TEXT,
    warning_message TEXT,
    enabled INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 操作日志
CREATE TABLE IF NOT EXISTS operation_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    module TEXT,
    target_type TEXT,
    target_id INTEGER,
    detail TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 登录日志
CREATE TABLE IF NOT EXISTS login_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    username TEXT,
    login_time TEXT DEFAULT (datetime('now','localtime')),
    ip_address TEXT,
    platform TEXT,
    result TEXT,
    fail_reason TEXT
);

-- 系统配置
CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key TEXT NOT NULL UNIQUE,
    config_value TEXT,
    description TEXT,
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 默认数据
INSERT OR IGNORE INTO system_config (config_key, config_value, description) VALUES
('report_expiry_yellow_days', '30', '报告到期黄色预警天数'),
('report_expiry_orange_days', '15', '报告到期橙色预警天数'),
('report_expiry_red_days', '7', '报告到期红色预警天数'),
('compliance_rate_A_threshold', '90', 'A级供应商合规率阈值'),
('compliance_rate_B_threshold', '70', 'B级供应商合规率阈值'),
('file_upload_max_size', '52428800', '文件上传最大字节'),
('file_upload_allowed_types', 'pdf,doc,docx,xls,xlsx', '允许上传的文件类型'),
('default_password', '123456', '默认密码'),
('company_name', '绿色产品管理系统GPM', '公司名称'),
('company_phone', '0755-88888888', '公司电话'),
('company_address', '广东省深圳市南山区', '公司地址'),
('system_name', '绿色产品管理系统GPM', '系统名称');

INSERT OR IGNORE INTO roles (name, description, permissions) VALUES
('admin', '系统管理员', '["*"]'),
('quality_manager', '质量管理员', '["material.view","material.audit","report.view","report.audit","compliance.view","supplier.view"]'),
('env_specialist', '环保专员', '["substance.view","substance.create","regulation.view","regulation.create","compliance.view"]'),
('purchaser', '采购员', '["material.view","report.view"]'),
('supplier', '供应商', '["material.self","report.self"]');

INSERT OR IGNORE INTO users (username, password, realname, role, status, password_changed) VALUES
('admin', '$2y$12$pTiiywXYTGt3PAy.EozPr.Z5L33mMvUt40HrhEM0lfbHlVPc.Qvhm', '系统管理员', 'admin', 'active', 0);

-- ==================== V2.0 新增表 ====================

-- 物料分类表 (V2.0)
CREATE TABLE IF NOT EXISTS material_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER DEFAULT 0,
    name TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    updated_at TEXT DEFAULT (datetime('now','localtime'))
);

-- 导出任务表 (V2.0)
CREATE TABLE IF NOT EXISTS export_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    file_path TEXT,
    file_size INTEGER,
    error_msg TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime')),
    completed_at TEXT
);

-- OCR缓存表 (V2.0)
CREATE TABLE IF NOT EXISTS ocr_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    file_md5 TEXT NOT NULL UNIQUE,
    file_text TEXT,
    ocr_method TEXT,
    created_at TEXT DEFAULT (datetime('now','localtime'))
);

-- ==================== V2.0 表结构变更 ====================

-- material 表新增 category_id 字段
ALTER TABLE material ADD COLUMN category_id INTEGER DEFAULT 0;

-- test_report 表新增 ocr_method 和 file_md5 字段
ALTER TABLE test_report ADD COLUMN ocr_method TEXT DEFAULT '';
ALTER TABLE test_report ADD COLUMN file_md5 TEXT DEFAULT '';

-- users 表新增 email_notify 字段
ALTER TABLE users ADD COLUMN email_notify INTEGER DEFAULT 0;

-- notice 表新增 send_email 字段
ALTER TABLE notice ADD COLUMN send_email INTEGER DEFAULT 0;

<?php
return [
    'dashboard' => ['label' => '仪表盘', 'perms' => [
        'dashboard.view' => '查看仪表盘',
    ]],
    'material' => ['label' => '物料管理', 'perms' => [
        'material.view'   => '查看物料列表',
        'material.create' => '创建物料',
        'material.edit'   => '编辑物料',
        'material.delete' => '删除物料',
        'material.audit'  => '审核物料',
    ]],
    'homogeneous' => ['label' => '均质材料', 'perms' => [
        'homogeneous.view'   => '查看均质材料',
        'homogeneous.create' => '创建均质材料',
        'homogeneous.edit'   => '编辑均质材料',
        'homogeneous.delete' => '删除均质材料',
    ]],
    'report' => ['label' => '检测报告', 'perms' => [
        'report.view'   => '查看报告列表',
        'report.create' => '上传报告',
        'report.edit'   => '编辑报告',
        'report.delete' => '删除报告',
        'report.audit'  => '审核报告',
    ]],
    'compliance' => ['label' => '合规分析', 'perms' => [
        'compliance.view'    => '查看合规分析',
        'compliance.analyze' => '执行合规分析',
    ]],
    'regulation' => ['label' => '法规管理', 'perms' => [
        'regulation.view'   => '查看法规库',
        'regulation.create' => '创建法规',
        'regulation.edit'   => '编辑法规',
        'regulation.delete' => '删除法规',
    ]],
    'substance' => ['label' => '物质管理', 'perms' => [
        'substance.view'   => '查看物质库',
        'substance.create' => '添加物质',
        'substance.edit'   => '编辑物质',
        'substance.delete' => '删除物质',
    ]],
    'supplier' => ['label' => '供应商管理', 'perms' => [
        'supplier.view'    => '查看供应商',
        'supplier.create'  => '创建供应商',
        'supplier.edit'    => '编辑供应商',
        'supplier.delete'  => '删除供应商',
        'supplier.rating'  => '供应商评级',
    ]],
    'task' => ['label' => '任务中心', 'perms' => [
        'task.view'   => '查看任务',
        'task.create' => '创建任务',
    ]],
    'notice' => ['label' => '通知公告', 'perms' => [
        'notice.view'   => '查看公告',
        'notice.create' => '发布公告',
        'notice.edit'   => '编辑公告',
        'notice.delete' => '删除公告',
    ]],
    'survey' => ['label' => '问卷调查', 'perms' => [
        'survey.view'   => '查看问卷',
        'survey.create' => '创建问卷',
    ]],
    'warning' => ['label' => '预警中心', 'perms' => [
        'warning.view' => '查看预警',
    ]],
    'query' => ['label' => '查询报表', 'perms' => [
        'query.view' => '查询报表',
    ]],
    'rule' => ['label' => '合规规则', 'perms' => [
        'rule.view' => '查看规则',
    ]],
    'document' => ['label' => '文档管理', 'perms' => [
        'document.view' => '查看文档',
    ]],
    'system' => ['label' => '系统管理', 'perms' => [
        'system.users'  => '用户管理',
        'system.roles'  => '角色管理',
        'system.config' => '系统参数',
        'system.logs'   => '操作日志',
    ]],
];

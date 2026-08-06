<div class="page-header">
  <h4><i class="fas fa-cog"></i> 流程配置</h4>
  <div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal"><i class="fas fa-plus"></i> 新增模板</button>
    <a href="/bpm" class="btn btn-sm btn-outline-secondary">任务中心</a>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">流程模板</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:20%">名称</th><th style="width:14%">分类</th><th style="width:10%">状态</th><th style="width:12%">操作</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($templates as $t): ?>
            <tr><td class="col-id"><?=h($t['id'] ?? '')?></td><td><?=h($t['name'])?></td><td><?=h($t['category'])?></td><td><?=statusBadge($t['status'])?></td>
              <td>
                <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
                  <input type="hidden" name="action" value="template_delete"><input type="hidden" name="id" value="<?=$t['id']?>">
                  <button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">节点配置</div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:20%">模板</th><th style="width:16%">节点</th><th style="width:9%">类型</th><th style="width:6%">排序</th><th style="width:14%">角色</th><th style="width:9%">超时(h)</th><th style="width:10%">操作</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($nodes as $n): ?>
            <tr>
              <td class="col-id"><?=h($n['id'] ?? '')?></td><td><small><?=h($n['template_name'])?></small></td>
              <td><?=h($n['node_name'])?></td>
              <td><span class="badge badge-soft-info"><?=h($n['node_type'])?></span></td>
              <td><?=$n['sort_order']?></td>
              <td><?=h($n['role_required'])?></td>
              <td><?=h($n['timeout_hours'] ?? '-')?></td>
              <td>
                <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')"><?=csrf_field()?>
                  <input type="hidden" name="action" value="node_delete"><input type="hidden" name="id" value="<?=$n['id']?>">
                  <button class="btn-icon btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card mt-3">
      <div class="card-header">添加节点</div>
      <div class="card-body">
        <form method="post"><?=csrf_field()?>
          <input type="hidden" name="action" value="node_create">
          <div class="row">
            <div class="col-6">
              <div class="mb-3"><label>模板</label>
                <select name="template_id" class="form-control" required>
                  <?php foreach ($templates as $t): ?>
                  <option value="<?=$t['id']?>"><?=h($t['name'])?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3"><label>节点名称</label><input type="text" name="node_name" class="form-control" required></div>
            </div>
          </div>
          <div class="row">
            <div class="col-4">
              <div class="mb-3"><label>类型</label>
                <select name="node_type" class="form-control">
                  <option value="start">开始</option>
                  <option value="approval">审批</option>
                  <option value="countersign">会签</option>
                  <option value="end">结束</option>
                </select>
              </div>
            </div>
            <div class="col-4">
              <div class="mb-3"><label>排序</label><input type="number" name="sort_order" class="form-control" value="1"></div>
            </div>
            <div class="col-4">
              <div class="mb-3"><label>所需角色</label>
                <select name="role_required" class="form-control">
                  <option value="admin">管理员</option>
                  <option value="quality_manager">质量管理员</option>
                  <option value="env_specialist">环保专员</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mb-3"><label>超时时间(小时)</label><input type="number" name="timeout_hours" class="form-control" value="48"></div>
          <button class="btn btn-primary btn-sm">添加节点</button>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="templateModal">
  <div class="modal-dialog">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title">新增流程模板</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="template_create">
        <div class="mb-3"><label>模板名称</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label>分类</label>
          <select name="category" class="form-control">
            <option value="material_audit">物料审核</option>
            <option value="supplier_audit">供应商审核</option>
            <option value="document_review">文档审核</option>
          </select>
        </div>
        <div class="mb-3"><label>描述</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">创建</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button></div>
    </form>
  </div>
</div>

<div class="page-header">
  <h5><i class="fas fa-gavel"></i> <?=h($item['name'])?> <small class="text-muted">[<?=h($item['regulation_no'])?>]</small></h5>
  <div>
    <a href="/regulation?action=edit&id=<?=$item['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> 编辑</a>
    <a href="/regulation" class="btn btn-sm btn-outline-secondary"><i class="fas fa-list"></i> 返回列表</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">基本信息</div>
      <div class="card-body">
        <table class="table table-sm">
          <tr><td style="width:80px">编号</td><td><?=h($item['regulation_no'])?></td></tr>
          <tr><td>英文名</td><td><?=h($item['name_en'] ?? '-')?></td></tr>
          <tr><td>分类</td><td><span class="badge badge-soft-info"><?=h($item['category'])?></span></td></tr>
          <tr><td>发布机构</td><td><?=h($item['issuing_body'] ?? '-')?></td></tr>
          <tr><td>生效日期</td><td><?=h($item['effective_date'] ?? '-')?></td></tr>
          <tr><td>状态</td><td><?=statusBadge($item['status'])?></td></tr>
          <tr><td>版本</td><td><?=h($item['version'] ?? 'V1.0')?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        管控条款
        <div class="card-actions">
          <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#contentModal"><i class="fas fa-plus"></i> 添加条款</button>
        </div>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover">
          <thead><tr><th>条款编号</th><th>标题</th><th>管控物质</th><th>CAS号</th><th>限值(<?=h($contents[0]['limit_unit'] ?? 'mg/kg')?>)</th><th>豁免</th><th>操作</th></tr></thead>
          <tbody>
            <?php foreach ($contents as $c): ?>
            <tr>
              <td><?=h($c['article_no'])?></td>
              <td><?=h($c['title'])?></td>
              <td><span class="badge badge-soft-danger"><?=h($c['substance_name'])?></span></td>
              <td><?=h($c['cas_no'])?></td>
              <td class="font-weight-bold"><?=h($c['limit_value'])?> <?=h($c['limit_unit'])?></td>
              <td><small class="text-muted"><?=h($c['exemption_desc'] ?? '-')?></small></td>
              <td>
                <form method="post" onsubmit="return confirm('确定删除？')" class="d-inline">
                  <input type="hidden" name="action" value="content_delete">
                  <input type="hidden" name="id" value="<?=$c['id']?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="contentModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header"><h5 class="modal-title">添加管控条款</h5><button class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="content_create">
          <input type="hidden" name="regulation_id" value="<?=$item['id']?>">
          <div class="row">
            <div class="col-6">
              <div class="form-group"><label>条款编号</label><input type="text" name="article_no" class="form-control"></div>
            </div>
            <div class="col-6">
              <div class="form-group"><label>排序</label><input type="number" name="sort_order" class="form-control" value="0"></div>
            </div>
          </div>
          <div class="form-group"><label>条款标题</label><input type="text" name="title" class="form-control"></div>
          <div class="row">
            <div class="col-4">
              <div class="form-group"><label>物质名称</label><input type="text" name="substance_name" class="form-control" required></div>
            </div>
            <div class="col-4">
              <div class="form-group"><label>CAS号</label><input type="text" name="cas_no" class="form-control" placeholder="7440-43-9"></div>
            </div>
            <div class="col-4">
              <div class="form-group"><label>限值</label><input type="number" name="limit_value" class="form-control" step="0.001" required></div>
            </div>
          </div>
          <div class="form-group"><label>限值单位</label>
            <select name="limit_unit" class="form-control">
              <option value="mg/kg">mg/kg</option>
              <option value="ppm">ppm</option>
              <option value="%">%</option>
            </select>
          </div>
          <div class="form-group"><label>豁免条款</label><textarea name="exemption_desc" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>条款内容</label><textarea name="content_text" class="form-control" rows="3"></textarea></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">添加</button>
          <button class="btn btn-secondary" data-dismiss="modal">取消</button>
        </div>
      </form>
    </div>
  </div>
</div>

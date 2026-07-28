<div class="page-header">
  <h5><i class="fas fa-flask"></i> 物质库</h5>
  <div>
    <?php if (can('substance.create')): ?><a href="/substance?action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> 新增物质</a><?php endif; ?>
  </div>
</div>
<div class="search-bar mb-3">
  <form method="get" class="form-inline">
    <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="搜索名称/CAS" value="<?=h($_GET['search'] ?? '')?>">
    <select name="group_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部分组</option>
      <?php foreach ($groups as $g): ?>
      <option value="<?=$g['id']?>" <?=($_GET['group_id'] ?? 0) == $g['id'] ? 'selected' : ''?>><?=h($g['name'])?></option>
      <?php endforeach; ?>
    </select>
    <select name="controlled" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
      <option value="">全部状态</option>
      <option value="1" <?=($_GET['controlled'] ?? '') === '1' ? 'selected' : ''?>>受控</option>
      <option value="0" <?=($_GET['controlled'] ?? '') === '0' ? 'selected' : ''?>>非受控</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
  </form>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th>CAS号</th><th>中文名称</th><th>英文名称</th><th>分子式</th><th>分组</th><th>受控</th><th>操作</th></tr></thead>
      <tbody>
        <?php foreach ($subs as $s): ?>
        <tr>
          <td><?=h($s['cas_no'])?></td>
          <td><?=h($s['name'])?></td>
          <td><?=h($s['name_en'])?></td>
          <td><?=h($s['molecular_formula'])?></td>
          <td><?=h($s['group_name'] ?? '-')?></td>
          <td><?=$s['is_controlled'] ? '<span class="status-tag danger">受控</span>' : '<span class="status-tag secondary">非受控</span>'?></td>
          <td>
            <?php if (can('substance.edit')): ?><a href="/substance?action=edit&id=<?=$s['id']?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a><?php endif; ?>
            <?php if (can('substance.delete')): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('确定删除？')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$s['id']?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

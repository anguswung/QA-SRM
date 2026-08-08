<div class="page-header">
  <h4><i class="fas fa-truck"></i> <?=h($item['name'])?> <small class="text-muted">[<?=h($item['code'])?>]</small></h4>
  <div>
    <?php if (can('supplier.edit') || (isSupplier() && userSupplierId() === (int)$item['id'])): ?><a href="/supplier?action=edit&id=<?=$item['id']?>" class="btn btn-primary"><i class="fas fa-edit"></i> 编辑</a><?php endif; ?>
    <a href="/supplier" class="btn btn-success">返回</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">基本信息</div>
      <div class="card-body">
        <table class="table table-sm">
        <?php $__rnum = 0; ?>
          <tr><td class="row-num"><?=++$__rnum?></td><td>编码</td><td><?=h($item['code'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>名称</td><td><?=h($item['name'])?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>联系人</td><td><?=h($item['contact_person'] ?: '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>联系电话</td><td><?=h($item['contact_phone'] ?: '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>邮箱</td><td><?=h($item['email'] ?: '-')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>地址</td><td><small><?=h($item['address'] ?: '-')?></small></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>等级</td><td><?=statusBadge($item['level'] . '_')?></td></tr>
          <tr><td class="row-num"><?=++$__rnum?></td><td>状态</td><td><?=$item['blacklist'] ? '<span class="status-tag danger">黑名单</span>' : '<span class="status-tag success">正常</span>'?></td></tr>
        </table>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">关联用户账号</div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>账号</th><th>姓名</th><th>角色</th><th>状态</th></tr></thead>
          <tbody>
            <?php if (empty($linkedUsers)): ?>
            <tr><td colspan="5" class="text-muted py-3">无关联账号</td></tr>
            <?php else: ?>
            <?php foreach ($linkedUsers as $lu): ?>
            <tr>
              <td class="col-id"><?=h($lu['id'])?></td>
              <td><?=h($lu['username'])?></td>
              <td><?=h($lu['realname'] ?: '-')?></td>
              <td><?=h($lu['role'])?></td>
              <td><span class="status-tag <?=$lu['status'] === 'active' ? 'success' : 'secondary'?>"><?=$lu['status'] === 'active' ? '启用' : '禁用'?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">供应商物料</div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>编号</th><th class="text-start">名称</th><th>环保状态</th><th>审核状态</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php foreach ($materials as $m): ?>
            <tr>
              <td class="col-id"><?=h($m['id'] ?? '')?></td><td><?=h($m['material_no'])?></td>
              <td><a href="/material?action=detail&id=<?=$m['id']?>"><?=h($m['material_name'])?></a></td>
              <td><?=statusBadge($m['env_status'])?></td>
              <td><?=statusBadge($m['audit_status'])?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        评价记录
        <div class="card-actions">
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ratingModal"><i class="fas fa-plus"></i> 评价</button>
        </div>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm">
          <thead><tr><th style="width:40px" class="col-id">ID</th><th>月份</th><th>资料完整性</th><th>合规率</th><th>响应速度</th><th>综合</th><th>均分</th></tr></thead>
          <tbody>
<?php $__rnum = 0; ?>
            <?php if (empty($ratingGroups)): ?>
            <tr><td colspan="6" class="text-muted py-3">暂无评价记录</td></tr>
            <?php else: ?>
            <?php $dims = ['资料完整性', '合规率', '响应速度', '综合']; ?>
            <?php foreach ($ratingGroups as $monthKey => $g): ?>
            <tr>
              <td class="col-id"><?=h($g['id'] ?? '')?></td><td class="fw-bold"><?=h($monthKey)?></td>
              <?php $scores = []; ?>
              <?php foreach ($dims as $dim): ?>
              <?php $item = $g['items'][$dim] ?? null; ?>
              <td>
                <?php if ($item): ?>
                <span class="fw-bold"><?=h($item['score'])?></span>
                <?php if ($item['comment']): ?><br><small class="text-muted"><?=h($item['comment'])?></small><?php endif; ?>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <?php $scores[] = $item ? (float)$item['score'] : 0; ?>
              <?php endforeach; ?>
              <td class="fw-bold text-primary"><?=array_sum($scores) > 0 ? round(array_sum($scores) / count(array_filter($scores)), 1) : '-'?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="ratingModal">
  <div class="modal-dialog modal-lg">
    <form method="post" class="modal-content"><?=csrf_field()?>
      <div class="modal-header"><h5 class="modal-title" id="ratingModalTitle">供应商评价</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="rating_create">
        <input type="hidden" name="supplier_id" value="<?=$item['id']?>">
        <div class="mb-3"><label>评价日期</label><input type="date" name="rating_date" class="form-control" value="<?=date('Y-m-d')?>"></div>
        <div class="card-header" style="padding:10px 0 0;background:transparent;border-radius:0">
          <ul class="nav nav-tabs card-header-tabs" id="supplierRatingTabs">
            <?php $dims = ['资料完整性', '合规率', '响应速度', '综合']; ?>
            <?php foreach ($dims as $i => $dim): ?>
            <li class="nav-item"><a class="nav-link <?=$i === 0 ? 'active' : ''?>" data-bs-toggle="tab" href="#tab<?=$i?>"><?=$dim?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="tab-content card-body" style="padding:12px 0 0">
          <?php
          $existingScores = [];
          $existingComments = [];
          foreach ($currentMonthRatings as $r) {
              $existingScores[$r['dimension']] = $r['score'];
              $existingComments[$r['dimension']] = $r['comment'];
          }
          $isEdit = !empty($currentMonthRatings);
          ?>
          <?php foreach ($dims as $i => $dim): ?>
          <div class="tab-pane <?=$i === 0 ? 'active' : ''?>" id="tab<?=$i?>">
            <div class="row g-3">
              <div class="mb-3 col-md-6">
                <label>评分 (0-100)</label>
                <input type="number" name="score[<?=$dim?>]" class="form-control" min="0" max="100" placeholder="请输入<?=$dim?>评分" value="<?=h($existingScores[$dim] ?? '')?>">
              </div>
              <div class="mb-3 col-md-6">
                <label>说明</label>
                <input type="text" name="comment[<?=$dim?>]" class="form-control" placeholder="可选备注" value="<?=h($existingComments[$dim] ?? '')?>">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="alert alert-info py-1 mb-0 mt-2" id="ratingHint">
          <i class="fas fa-info-circle"></i>
          <?php if ($isEdit): ?>本月已有评价记录，再次提交将覆盖更新。<?php else: ?>每月仅可评价一次，提交后当月不可重复新增。<?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary"><?=$isEdit ? '修改保存' : '保存全部维度'?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
      </div>
    </form>
  </div>
</div>

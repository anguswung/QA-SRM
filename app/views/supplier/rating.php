<div class="page-header">
  <h4><i class="fas fa-star"></i> 供应商评级</h4>
  <div>
    <?php if (isAdmin()): ?><a href="/supplier" class="btn btn-sm btn-outline-secondary">供应商列表</a><?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover datatable">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th>编码</th><th class="text-start">名称</th><th>等级</th><th>物料数</th><th>合规数</th><th>不合规数</th><th>合规率</th></tr></thead>
      <tbody>
<?php $__rnum = 0; ?>
        <?php foreach ($suppliers as $s): ?>
        <?php $rate = $s['material_count'] > 0 ? round($s['compliant_count'] / $s['material_count'] * 100, 1) : 0; ?>
        <tr>
          <td class="col-id"><?=h($s['id'] ?? '')?></td><td><?=h($s['code'])?></td>
          <td><a href="/supplier?action=detail&id=<?=$s['id']?>"><?=h($s['name'])?></a></td>
          <td><?=statusBadge($s['level'] . '_')?></td>
          <td><?=(int)$s['material_count']?></td>
          <td><span class="text-success"><?=(int)$s['compliant_count']?></span></td>
          <td><span class="text-danger"><?=(int)$s['non_compliant_count']?></span></td>
          <td>
            <div class="d-flex align-items-center">
              <div class="progress flex-grow-1 ms-2 h-8">
                <div class="progress-bar bg-<?=$rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger')?>" style="width:<?=min($rate, 100)?>%"></div>
              </div>
              <small class="fw-bold"><?=$rate?>%</small>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

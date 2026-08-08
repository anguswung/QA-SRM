<div class="page-header">
  <h4><i class="fas fa-calendar-times"></i> 到期查询</h4>
</div>
<div class="card">
  <div class="card-body">
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
      <select name="range" class="form-control form-control-sm w-auto ms-2" onchange="this.form.submit()">
        <option value="">全部</option>
        <option value="expired" <?=($_GET['range'] ?? '') === 'expired' ? 'selected' : ''?>>已过期</option>
        <option value="30days" <?=($_GET['range'] ?? '') === '30days' ? 'selected' : ''?>>30天内到期</option>
        <option value="60days" <?=($_GET['range'] ?? '') === '60days' ? 'selected' : ''?>>60天内到期</option>
        <option value="valid" <?=($_GET['range'] ?? '') === 'valid' ? 'selected' : ''?>>有效期内</option>
      </select>
      <input type="date" name="start_date" class="form-control form-control-sm w-auto ms-2" value="<?=h($_GET['start_date'] ?? '')?>">
      <input type="date" name="end_date" class="form-control form-control-sm w-auto ms-2" value="<?=h($_GET['end_date'] ?? '')?>">
      <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i> 查询</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0" data-server="1" data-url="/query/expired?action=data">
      <thead><tr><th style="width:40px" class="col-id">ID</th><th style="width:12%">报告编号</th><th>物料</th><th style="width:10%">供应商</th><th style="width:7%">类型</th><th style="width:10%">有效期</th><th style="width:9%">剩余天数</th><th style="width:8%">状态</th><th style="width:10%">操作</th></tr></thead>
      <tbody>
        <tr><td colspan="7" class="text-center text-muted py-4">加载中...</td></tr>
      </tbody>
    </table>
  </div>
</div>

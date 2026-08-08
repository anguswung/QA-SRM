<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<?php
$companyName ??= '';
$sysName ??= '绿色产品管理系统GPM';
$version ??= '1.0.0';
$logoPath ??= '/assets/images/logo.png';
?>
<title><?=h($title ?? '仪表盘')?> — <?=h($companyName ?: $sysName)?></title>
<script>window._csrf=<?=json_encode(csrf_token())?>;</script>
<script>function getCsrfToken(){try{var x=new XMLHttpRequest();x.open('GET','/api/csrf-token',false);x.send();var t=JSON.parse(x.responseText).token;if(t)return t;}catch(e){}return window._csrf||'';}</script>
<script>if(typeof $==='undefined'){window.$=function(a){if(typeof a==='function'){if(typeof jQuery!=='undefined'){a(jQuery)}else{var i=setInterval(function(){if(typeof jQuery!=='undefined'){a(jQuery);clearInterval(i)}},5)}}else{return typeof jQuery==='undefined'?{each:function(){return this},on:function(){return this},off:function(){return this},find:function(){return this},closest:function(){return this},hide:function(){return this},show:function(){return this},addClass:function(){return this},removeClass:function(){return this},toggleClass:function(){return this},html:function(){return this},text:function(){return this},val:function(){return this},attr:function(){return this},data:function(){return this},css:function(){return this},length:0,selector:a||''}:jQuery(a)}};window.$.fn={};window.$.extend=function(){return arguments[1]||{}};window.$.each=function(a,f){for(var i=0;i<(a||[]).length;i++)f(i,a[i])}}</script>
<link href="/assets/vendor/tabler/css/tabler.min.css" rel="stylesheet">
<link href="/assets/vendor/fontawesome/css/all.min.css" rel="stylesheet">
<link href="/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="/assets/vendor/toastr/toastr.min.css" rel="stylesheet">
<link href="/assets/css/app.css?v=48" rel="stylesheet">
<link href="/assets/css/icomoon.css" rel="stylesheet">
  <link href="/assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <script src="/assets/vendor/chartjs/chart.umd.min.js"></script>
  <script>if(typeof Chart==='undefined')document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"><\/script>');</script>
</head>
<body class="layout-fluid">
  <div class="page">
    <?php if (isset($_SESSION['user_id'])): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="page-wrapper">
      <?php include __DIR__ . '/topbar.php'; ?>
      <div class="page-body">
        <div class="container-xl">
          <div class="container-fluid p-0">
          <?php if (isset($content)) echo $content; ?>
          </div>
        </div>
      </div>
      <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
          <div class="row align-items-center">
            <div class="col-12 text-center text-lg-end">
              <?=h($sysName)?> - v<?=h($version)?>
            </div>
            <div class="col-12 mt-1 text-center text-lg-end">
              <?=h($companyName ?: $sysName)?> - 版权所有，翻录必究。
            </div>
          </div>
        </div>
      </footer>
    </div>
  <?php else: ?>
  <div class="page-wrapper">
    <div class="page-body">
      <div class="container-xl">
        <div class="container-fluid p-0">
          <?php if (isset($content)) echo $content; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<script src="/assets/vendor/jquery/jquery.min.js"></script>
<script>if(typeof $==='undefined')document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>');</script>
<script src="/assets/vendor/tabler/js/tabler.min.js"></script>
<script>
(function(){
  // tabler.min.js 已内置 Bootstrap 5.3.7（含 dropdown/modal/collapse 的 data-api），
  // 无需再从 CDN 加载第二份 bootstrap.bundle，否则两个 Bootstrap 会同时响应
  // [data-bs-toggle="dropdown"]，导致侧边栏菜单"点击展开又立即收起"。
  var bs = window.tabler && window.tabler.bootstrap;
  if (bs) {
    window.bootstrap = bs;
    if (window.$ && !window.$.fn.modal) {
      window.$.fn.modal = function(method){
        var el = this[0];
        if (el && bs.Modal) {
          var inst = bs.Modal.getInstance(el) || bs.Modal.getOrCreateInstance(el);
          if (method === 'show' || method === 'hide' || method === 'toggle') inst[method]();
        }
        return this;
      };
    }
  } else if (typeof $ === 'undefined' || !$.fn.modal) {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"><\/script>');
  }
})();
</script>
<script src="/assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script>if(typeof $==='undefined'||!$.fn.dataTable)document.write('<script src="https://cdn.jsdelivr.net/npm/datatables.net@3.0.1/js/jquery.dataTables.min.js"><\/script>');</script>
<script src="/assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script>if(typeof $==='undefined'||!$.fn.dataTable)document.write('<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@3.0.1/js/dataTables.bootstrap5.min.js"><\/script>');</script>
<script src="/assets/vendor/toastr/toastr.min.js"></script>
<script>if(typeof toastr==='undefined')document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"><\/script>');</script>
<script>
toastr.options.positionClass='toast-top-center';
toastr.options.newestOnTop=true;
(function(){
    var orig={success:toastr.success,error:toastr.error,warning:toastr.warning,info:toastr.info};
    function max3(type,msg,title,opts){
        var container=document.getElementById('toast-container');
        if(container){
            var kids=container.children;
            while(kids.length>=3){
                kids[kids.length-1].remove();
                kids=container.children;
            }
        }
        orig[type](msg,title,opts);
    }
    toastr.success=function(m,t,o){max3('success',m,t,o)};
    toastr.error=function(m,t,o){max3('error',m,t,o)};
    toastr.warning=function(m,t,o){max3('warning',m,t,o)};
    toastr.info=function(m,t,o){max3('info',m,t,o)};
})();
</script>
<script src="/assets/js/app.js?v=4"></script>
<script src="/assets/vendor/quill/quill.min.js"></script>
<div class="app-watermark"><img src="<?=h($logoPath)?>" alt="logo"></div>
<?php if (isset($_SESSION['_flash'])): ?>
<script>toastr.<?=$_SESSION['_flash']['type'] ?? 'info'?>('<?=h($_SESSION['_flash']['message'] ?? '')?>');</script>
<?php unset($_SESSION['_flash']); endif; ?>
</body>
</html>

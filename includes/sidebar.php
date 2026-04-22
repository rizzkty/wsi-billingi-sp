<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$menus = [];
if ($role === 'pemilik') {
    $menus = [
        ['icon'=>'▦','label'=>'Dashboard','href'=>'/billing-isp/pages/pemilik/dashboard.php'],
        ['icon'=>'◉','label'=>'Pelanggan','href'=>'/billing-isp/pages/pemilik/pelanggan.php'],
        ['icon'=>'◈','label'=>'Paket',    'href'=>'/billing-isp/pages/pemilik/paket.php'],
        ['icon'=>'◎','label'=>'Tagihan',  'href'=>'/billing-isp/pages/pemilik/tagihan.php'],
        ['icon'=>'▤','label'=>'Laporan',  'href'=>'/billing-isp/pages/pemilik/laporan.php'],
        ['icon'=>'◧','label'=>'Users',    'href'=>'/billing-isp/pages/pemilik/users.php'],
    ];
} elseif ($role === 'admin') {
    $menus = [
        ['icon'=>'▦','label'=>'Dashboard', 'href'=>'/billing-isp/pages/admin/dashboard.php'],
        ['icon'=>'◉','label'=>'Pelanggan', 'href'=>'/billing-isp/pages/admin/pelanggan.php'],
        ['icon'=>'◈','label'=>'Paket',     'href'=>'/billing-isp/pages/admin/paket.php'],
        ['icon'=>'◎','label'=>'Tagihan',   'href'=>'/billing-isp/pages/admin/tagihan.php'],
        ['icon'=>'💳','label'=>'Pembayaran','href'=>'/billing-isp/pages/admin/pembayaran.php'],
        ['icon'=>'📊','label'=>'Laporan',  'href'=>'/billing-isp/pages/admin/laporan.php'],
        ['icon'=>'▤','label'=>'Peta',      'href'=>'/billing-isp/pages/admin/peta.php'],
    ];
} elseif ($role === 'teknisi') {
    $menus = [
        ['icon'=>'▦','label'=>'Dashboard', 'href'=>'/billing-isp/pages/teknisi/dashboard.php'],
        ['icon'=>'▤','label'=>'Peta',      'href'=>'/billing-isp/pages/teknisi/peta.php'],
        ['icon'=>'◈','label'=>'Pembayaran','href'=>'/billing-isp/pages/teknisi/pembayaran.php'],
    ];
}
$rl = ['pemilik'=>'Pemilik','admin'=>'Admin','teknisi'=>'Teknisi'][$role] ?? $role;
?>
<aside class="sidebar">
  <div class="sb-top">
    <div class="sb-brand">
      <div class="sb-logo">ISP</div>
      <span class="sb-brand-text">Billing ISP</span>
    </div>
  </div>
  <nav class="sb-nav">
    <?php foreach($menus as $m):
      $a = ($current_page===basename($m['href']))?'active':''; ?>
    <a href="<?=$m['href']?>" class="sb-item <?=$a?>">
      <span class="sb-icon"><?=$m['icon']?></span>
      <span class="sb-label"><?=$m['label']?></span>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-avatar"><?=strtoupper(mb_substr($_SESSION['nama']??'U',0,1))?></div>
      <div class="sb-user-info">
        <span class="sb-user-name"><?=htmlspecialchars($_SESSION['nama']??'')?></span>
        <span class="sb-user-role"><?=$rl?></span>
      </div>
    </div>
    <form method="POST" action="/billing-isp/logout.php" class="sb-logout-form">
      <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
      <button type="submit" class="sb-logout">
        <span class="sb-icon">⏻</span>
        <span class="sb-label">Keluar</span>
      </button>
    </form>
  </div>
</aside>

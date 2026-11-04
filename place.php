<?php
$pageTitle = 'Destination';
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /wanderstays/index.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM places WHERE id = ?");
$stmt->bind_param('i', $id); $stmt->execute();
$place = $stmt->get_result()->fetch_assoc();
if (!$place) { header('Location: /wanderstays/index.php'); exit; }

$photos = $conn->query("SELECT file_path FROM place_photos WHERE place_id = $id ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);

$typeF = $_GET['type'] ?? '';
$sort  = $_GET['sort'] ?? '';
$types = ['Hotel','Resort','Villa','Airbnb','Homestay','Guesthouse','Cottage'];

$whereSQL = "pr.place_id = $id" . ($typeF ? " AND pr.type = '" . $conn->real_escape_string($typeF) . "'" : "");
$orderSQL = match($sort) { 'asc' => 'ORDER BY pr.price_per_night ASC', 'desc' => 'ORDER BY pr.price_per_night DESC', default => 'ORDER BY pr.created_at DESC' };

$sql = "SELECT pr.*, u.full_name as owner_name,
        (SELECT file_path FROM property_photos pp WHERE pp.property_id = pr.id ORDER BY sort_order LIMIT 1) as cover,
        (SELECT COUNT(*) FROM property_photos pp WHERE pp.property_id = pr.id) as photo_count
        FROM properties pr JOIN users u ON u.id = pr.owner_id
        WHERE $whereSQL $orderSQL";
$props = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-wrap">
  <a href="/wanderstays/index.php" class="btn-outline btn-sm" style="margin-bottom:24px;display:inline-block">← Back to Explore</a>

  <!-- Banner -->
  <div class="place-banner" style="margin-bottom:10px">
    <?php if (!empty($photos)): ?>
      <img id="banner-photo" src="/wanderstays/<?= sanitize($photos[0]['file_path']) ?>" alt="<?= sanitize($place['name']) ?>">
      <?php if (count($photos)>1): ?>
      <div class="dot-nav">
        <?php foreach ($photos as $i=>$ph): ?>
          <button class="dot <?= $i===0?'active':'' ?>" onclick="switchDot(<?=$i?>,this,<?=json_encode(array_column($photos,'file_path'))?>)"></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <div style="width:100%;height:100%;background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:72px">🏔</div>
    <?php endif; ?>
    <div class="place-banner-overlay"></div>
    <div class="place-banner-info">
      <span class="tag" style="margin-bottom:8px;display:inline-block"><?= sanitize($place['state']) ?></span>
      <h1><?= sanitize($place['name']) ?></h1>
      <p class="sf" style="color:#b8b0a0;font-size:13px;margin-top:6px;max-width:600px"><?= sanitize($place['description']) ?></p>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" action="/wanderstays/index.php" style="margin:24px 0">
    <input type="hidden" name="page" value="place">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="filter-bar">
      <div class="form-group">
        <label>Property Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="">All Types</option>
          <?php foreach ($types as $t): ?>
            <option value="<?=$t?>" <?=$typeF===$t?'selected':''?>><?=$t?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Sort by Price</label>
        <select name="sort" id="price_sort">
          <option value="" <?=$sort===''?'selected':''?>>Default</option>
          <option value="asc" <?=$sort==='asc'?'selected':''?>>Price: Low → High</option>
          <option value="desc" <?=$sort==='desc'?'selected':''?>>Price: High → Low</option>
        </select>
      </div>
    </div>
  </form>

  <h2 style="margin-bottom:20px">Available Properties <span class="sf" style="font-size:14px;color:var(--dim);font-weight:400">(<?= count($props) ?>)</span></h2>

  <?php if (empty($props)): ?>
    <div class="empty"><p>No properties here yet<?= $typeF?' for this type':'' ?>.</p></div>
  <?php else: ?>
  <div class="grid2">
    <?php foreach ($props as $pr): ?>
    <a href="/wanderstays/index.php?page=property&id=<?= $pr['id'] ?>" style="text-decoration:none;color:inherit">
    <div class="card">
      <div class="card-img">
        <?php if ($pr['cover']): ?>
          <img src="/wanderstays/<?= sanitize($pr['cover']) ?>" alt="<?= sanitize($pr['name']) ?>">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--bg3)">🏨</div>
        <?php endif; ?>
        <div class="card-type"><span class="tag"><?= sanitize($pr['type']) ?></span></div>
        <?php if ($pr['photo_count']>1): ?><div class="photo-count"><span class="tag" style="font-size:10px">+<?=$pr['photo_count']-1?> photos</span></div><?php endif; ?>
      </div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px">
          <h3><?= sanitize($pr['name']) ?></h3>
        </div>
        <p class="sf" style="font-size:12px;color:var(--dim);margin-bottom:10px;line-height:1.5"><?= sanitize(substr($pr['description'],0,80)) ?>…</p>
        <div class="sf" style="display:flex;justify-content:space-between;align-items:center">
          <div><span style="font-size:20px;font-weight:700;color:var(--gold)"><?= formatPrice($pr['price_per_night']) ?></span><span style="font-size:11px;color:var(--dim)">/night</span></div>
          <?php if ($pr['rating_count']>0): ?>
            <span style="font-size:12px;color:var(--gold)">★ <?= number_format($pr['rating_total']/$pr['rating_count'],1) ?></span>
          <?php endif; ?>
        </div>
        <p class="sf" style="font-size:11px;color:var(--dim);margin-top:4px">Max <?= $pr['max_guests'] ?> guests · by <?= sanitize($pr['owner_name']) ?></p>
      </div>
    </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

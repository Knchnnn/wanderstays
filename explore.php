<?php
$pageTitle = 'Explore';
require_once __DIR__ . '/../includes/header.php';

$stateF = $_GET['state'] ?? '';
$typeF  = $_GET['type']  ?? '';
$sort   = $_GET['sort']  ?? '';

$states = ['Rajasthan','Himachal Pradesh','Goa','Kerala','Uttarakhand','Tamil Nadu','Maharashtra','Jammu & Kashmir','Gujarat','Karnataka','West Bengal','Meghalaya','Sikkim'];
$types  = ['Hotel','Resort','Villa','Airbnb','Homestay','Guesthouse','Cottage'];

// Build places query with min property price
$where = ['1=1'];
$params = []; $types_str = '';
if ($stateF) { $where[] = 'pl.state = ?'; $params[] = $stateF; $types_str .= 's'; }
if ($typeF)  { $where[] = 'pl.type = ?';  $params[] = $typeF;  $types_str .= 's'; }
$whereSQL = implode(' AND ', $where);
$orderSQL = match($sort) {
    'asc'  => 'ORDER BY min_price ASC',
    'desc' => 'ORDER BY min_price DESC',
    default => 'ORDER BY pl.created_at DESC'
};

$sql = "SELECT pl.*, 
        MIN(pr.price_per_night) as min_price,
        (SELECT file_path FROM place_photos pp WHERE pp.place_id = pl.id ORDER BY sort_order LIMIT 1) as cover,
        COUNT(pr.id) as prop_count
        FROM places pl
        LEFT JOIN properties pr ON pr.place_id = pl.id
        WHERE $whereSQL
        GROUP BY pl.id $orderSQL";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types_str, ...$params); }
$stmt->execute();
$places = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-wrap">
  <div style="margin-bottom:40px">
    <p class="sf" style="font-size:11px;letter-spacing:4px;color:var(--gold);text-transform:uppercase;margin-bottom:10px">Discover India</p>
    <h1>Where shall we <em style="color:var(--gold)">wander next?</em></h1>
  </div>

  <form method="GET" action="/wanderstays/index.php">
    <div class="filter-bar">
      <div class="form-group">
        <label>State</label>
        <select name="state" onchange="this.form.submit()">
          <option value="">All States</option>
          <?php foreach ($states as $s): ?>
            <option value="<?= $s ?>" <?= $stateF===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Property Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="">All Types</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>" <?= $typeF===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Sort by Price</label>
        <select name="sort" id="price_sort">
          <option value="" <?= $sort===''?'selected':'' ?>>Default</option>
          <option value="asc" <?= $sort==='asc'?'selected':'' ?>>Price: Low → High</option>
          <option value="desc" <?= $sort==='desc'?'selected':'' ?>>Price: High → Low</option>
        </select>
      </div>
      <input type="hidden" name="page" value="">
    </div>
  </form>

  <?php if (empty($places)): ?>
    <div class="empty"><p>No destinations found. <?= $stateF||$typeF ? 'Try clearing filters.' : 'Owners will add destinations soon.' ?></p></div>
  <?php else: ?>
  <div class="grid2">
    <?php foreach ($places as $pl): ?>
    <a href="/wanderstays/index.php?page=place&id=<?= $pl['id'] ?>" style="text-decoration:none;color:inherit">
    <div class="card">
      <div class="card-img">
        <?php if ($pl['cover']): ?>
          <img src="/wanderstays/<?= sanitize($pl['cover']) ?>" alt="<?= sanitize($pl['name']) ?>">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;color:var(--bg3)">🏔</div>
        <?php endif; ?>
        <div class="card-type"><span class="tag"><?= sanitize($pl['type']) ?></span></div>
      </div>
      <div class="card-body">
        <h3 style="margin-bottom:4px"><?= sanitize($pl['name']) ?></h3>
        <p class="sf" style="font-size:12px;color:var(--gold);margin-bottom:8px">📍 <?= sanitize($pl['state']) ?></p>
        <p class="sf" style="font-size:13px;color:var(--dim);line-height:1.5;margin-bottom:12px"><?= sanitize(substr($pl['description'],0,90)) ?><?= strlen($pl['description'])>90?'…':'' ?></p>
        <div class="sf" style="display:flex;justify-content:space-between;align-items:center">
          <?php if ($pl['min_price']): ?>
            <span style="font-size:12px;color:var(--muted)">From <strong style="color:var(--gold)"><?= formatPrice($pl['min_price']) ?></strong>/night</span>
          <?php else: ?>
            <span style="font-size:12px;color:var(--dim)">No properties yet</span>
          <?php endif; ?>
          <span style="font-size:11px;color:var(--dim)"><?= $pl['prop_count'] ?> <?= $pl['prop_count']==1?'property':'properties' ?></span>
        </div>
      </div>
    </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

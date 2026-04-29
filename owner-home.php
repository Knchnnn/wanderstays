<?php
$pageTitle = 'Owner Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$uid = $user['id'];

// Handle accept/reject
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $rid    = (int)($_POST['rid'] ?? 0);
    $action = $_POST['action'];
    if ($action==='accept') {
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $stmt = $conn->prepare("UPDATE booking_requests SET status='accepted',accept_deadline=? WHERE id=? AND property_id IN (SELECT id FROM properties WHERE owner_id=?)");
        $stmt->bind_param('sii',$deadline,$rid,$uid); $stmt->execute();
        header('Location: /wanderstays/index.php?msg='.urlencode('Request accepted!')); exit;
    } elseif ($action==='reject') {
        $stmt = $conn->prepare("UPDATE booking_requests SET status='rejected' WHERE id=? AND property_id IN (SELECT id FROM properties WHERE owner_id=?)");
        $stmt->bind_param('ii',$rid,$uid); $stmt->execute();
        header('Location: /wanderstays/index.php?msg='.urlencode('Request rejected.').'&type=error'); exit;
    }
}

$props = $conn->query("SELECT pr.*,
    (SELECT file_path FROM property_photos pp WHERE pp.property_id=pr.id ORDER BY sort_order LIMIT 1) as cover,
    pl.name as place_name, pl.state as place_state
    FROM properties pr JOIN places pl ON pl.id=pr.place_id
    WHERE pr.owner_id=$uid ORDER BY pr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$priceSort = $_GET['sort'] ?? '';
if ($priceSort==='asc') usort($props,fn($a,$b)=>$a['price_per_night']<=>$b['price_per_night']);
if ($priceSort==='desc') usort($props,fn($a,$b)=>$b['price_per_night']<=>$a['price_per_night']);

$propIds = array_column($props,'id');
$reqs = [];
if (!empty($propIds)) {
    $in = implode(',',$propIds);
    $reqs = $conn->query("SELECT br.*, u.full_name as traveller_name, pr.name as prop_name, pr.price_per_night as owner_price
        FROM booking_requests br JOIN users u ON u.id=br.traveller_id JOIN properties pr ON pr.id=br.property_id
        WHERE br.property_id IN ($in) ORDER BY br.created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$totalRevenue = array_sum(array_map(fn($r)=> $r['paid'] ? $r['offered_price']*nightsBetween($r['check_in'],$r['check_out']) : 0, $reqs));
$pending  = count(array_filter($reqs,fn($r)=>$r['status']==='pending'));
$accepted = count(array_filter($reqs,fn($r)=>$r['status']==='accepted'));
?>
<div class="page-wrap">
  <h1 style="margin-bottom:6px">Owner Dashboard</h1>
  <p class="sf" style="color:var(--dim);margin-bottom:32px;font-size:13px">Manage your listings and booking requests</p>

  <div class="stats-row s4">
    <div class="stat-box"><div class="stat-val"><?=count($props)?></div><div class="stat-lbl">Properties</div></div>
    <div class="stat-box"><div class="stat-val"><?=$pending?></div><div class="stat-lbl">Pending</div></div>
    <div class="stat-box"><div class="stat-val"><?=$accepted?></div><div class="stat-lbl">Accepted</div></div>
    <div class="stat-box"><div class="stat-val">₹<?=number_format($totalRevenue,0)?></div><div class="stat-lbl">Revenue</div></div>
  </div>

  <!-- My Properties -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h2>My Properties</h2>
    <form method="GET" style="min-width:180px">
      <select name="sort" onchange="this.form.submit()" style="padding:8px">
        <option value="" <?=$priceSort===''?'selected':''?>>Sort: Default</option>
        <option value="asc" <?=$priceSort==='asc'?'selected':''?>>Price: Low → High</option>
        <option value="desc" <?=$priceSort==='desc'?'selected':''?>>Price: High → Low</option>
      </select>
    </form>
  </div>

  <?php if (empty($props)): ?>
    <div class="empty" style="margin-bottom:40px"><p>No properties yet.</p><a href="/wanderstays/index.php?page=add-property" class="btn-gold">+ Add Property</a></div>
  <?php else: ?>
  <div class="grid2" style="margin-bottom:40px">
    <?php foreach ($props as $pr):
      $propRevs = $conn->query("SELECT rating FROM reviews WHERE property_id={$pr['id']}")->fetch_all(MYSQLI_ASSOC);
      $avgR = count($propRevs) ? array_sum(array_column($propRevs,'rating'))/count($propRevs) : null;
      $lastRev = $conn->query("SELECT review_text, u.full_name FROM reviews r JOIN users u ON u.id=r.traveller_id WHERE r.property_id={$pr['id']} ORDER BY r.created_at DESC LIMIT 1")->fetch_assoc();
    ?>
    <div class="card" style="cursor:default">
      <div class="card-img" style="height:150px">
        <?php if ($pr['cover']): ?>
          <img src="/wanderstays/<?= sanitize($pr['cover']) ?>" alt="">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:36px">🏨</div>
        <?php endif; ?>
        <div class="card-type"><span class="tag"><?= sanitize($pr['type']) ?></span></div>
      </div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
          <h3 style="font-size:14px"><?= sanitize($pr['name']) ?></h3>
        </div>
        <p class="sf" style="font-size:12px;color:var(--gold)">📍 <?= sanitize($pr['place_name']) ?>, <?= sanitize($pr['place_state']) ?></p>
        <p class="sf" style="font-size:13px;color:var(--gold);margin-top:4px"><?= formatPrice($pr['price_per_night']) ?>/night · <?= $pr['max_guests'] ?> guests</p>
        <?php if ($avgR): ?><p class="sf" style="font-size:11px;color:var(--dim);margin-top:3px">★ <?= number_format($avgR,1) ?> avg · <?= count($propRevs) ?> reviews</p><?php endif; ?>
        <?php if ($lastRev): ?><p class="sf" style="font-size:12px;color:var(--muted);margin-top:8px;font-style:italic;border-left:2px solid rgba(197,165,90,.3);padding-left:8px">"<?= sanitize(substr($lastRev['review_text'],0,80)) ?>…"</p><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Booking Requests -->
  <h2 style="margin-bottom:18px">Booking Requests</h2>
  <?php if (empty($reqs)): ?>
    <div class="empty"><p>No booking requests yet.</p></div>
  <?php else: ?>
    <?php foreach ($reqs as $r):
      $nights = nightsBetween($r['check_in'],$r['check_out']);
    ?>
    <div class="req-row owner-view" style="margin-bottom:12px">
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:7px;flex-wrap:wrap">
          <span class="sf" style="font-size:14px;font-weight:500"><?= sanitize($r['traveller_name']) ?></span>
          <?= statusBadge($r['status']) ?>
          <?php if ($r['paid']): ?><span class="badge-paid">PAID <?= formatPrice($r['offered_price']*$nights) ?></span><?php endif; ?>
        </div>
        <p class="sf" style="font-size:12px;color:var(--muted);margin-bottom:3px">Property: <span style="color:var(--text)"><?= sanitize($r['prop_name']) ?></span></p>
        <p class="sf" style="font-size:12px;color:var(--dim)"><?= $r['check_in'] ?> → <?= $r['check_out'] ?> · <?= $r['guests'] ?> guests · <?= $nights ?> nights</p>
        <p class="sf" style="font-size:12px;margin-top:3px">
          Offer: <span style="color:var(--gold)"><?= formatPrice($r['offered_price']) ?>/night</span>
          <?php if ($r['offered_price'] < $r['owner_price']): ?>
            <span style="color:var(--yellow);margin-left:6px;font-size:11px">↓ below listed <?= formatPrice($r['owner_price']) ?></span>
          <?php endif; ?>
        </p>
        <?php if ($r['status']==='pending'): ?>
        <form method="POST" style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <input type="hidden" name="rid" value="<?= $r['id'] ?>">
          <input type="hidden" name="action" value="accept" id="action-<?= $r['id'] ?>">
          <label style="margin:0;white-space:nowrap">Accept by (optional)</label>
          <input type="datetime-local" name="deadline" style="flex:1;max-width:220px;padding:6px 10px">
          <button type="submit" class="btn-gold btn-sm" onclick="document.getElementById('action-<?=$r['id']?>').value='accept'">Accept</button>
          <button type="submit" class="btn-danger btn-sm" onclick="document.getElementById('action-<?=$r['id']?>').value='reject'">Reject</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

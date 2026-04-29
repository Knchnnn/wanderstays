<?php
$pageTitle = 'My Trips';
require_once __DIR__ . '/../includes/header.php';
requireRole('traveller');

$uid = $user['id'];
$reqs = $conn->query("SELECT br.*, pr.name as prop_name, pr.id as prop_id,
    pl.name as place_name, pl.state as place_state,
    (SELECT file_path FROM property_photos pp WHERE pp.property_id=br.property_id ORDER BY sort_order LIMIT 1) as cover
    FROM booking_requests br
    JOIN properties pr ON pr.id=br.property_id
    JOIN places pl ON pl.id=pr.place_id
    WHERE br.traveller_id=$uid ORDER BY br.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$total   = count($reqs);
$accepted = count(array_filter($reqs, fn($r)=>$r['status']==='accepted'));
$paid     = count(array_filter($reqs, fn($r)=>$r['paid']));
$spent    = array_sum(array_map(fn($r)=> $r['paid'] ? $r['offered_price']*nightsBetween($r['check_in'],$r['check_out']) : 0, $reqs));
?>
<div class="page-wrap">
  <h1 style="margin-bottom:6px">My Trips</h1>
  <p class="sf" style="color:var(--dim);margin-bottom:32px;font-size:13px">Your booking history and request status</p>

  <div class="stats-row s4">
    <div class="stat-box"><div class="stat-val"><?=$total?></div><div class="stat-lbl">Total Requests</div></div>
    <div class="stat-box"><div class="stat-val"><?=$accepted?></div><div class="stat-lbl">Accepted</div></div>
    <div class="stat-box"><div class="stat-val"><?=$paid?></div><div class="stat-lbl">Trips Paid</div></div>
    <div class="stat-box"><div class="stat-val">₹<?=number_format($spent,0)?></div><div class="stat-lbl">Total Spent</div></div>
  </div>

  <?php if (empty($reqs)): ?>
    <div class="empty"><p>No trips yet — start exploring!</p><a href="/wanderstays/index.php" class="btn-gold">Explore Places</a></div>
  <?php else: ?>
    <?php foreach ($reqs as $r):
      $nights = nightsBetween($r['check_in'],$r['check_out']);
      $total_cost = $r['offered_price'] * $nights;
    ?>
    <div class="req-row traveller-view" style="margin-bottom:14px">
      <?php if ($r['cover']): ?>
        <img src="/wanderstays/<?= sanitize($r['cover']) ?>" style="width:88px;height:62px;object-fit:cover;border-radius:4px">
      <?php else: ?>
        <div style="width:88px;height:62px;background:var(--bg3);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:22px">🏨</div>
      <?php endif; ?>
      <div>
        <div class="sf" style="font-size:14px;font-weight:500;margin-bottom:3px"><?= sanitize($r['prop_name']) ?></div>
        <div class="sf" style="font-size:12px;color:var(--gold);margin-bottom:3px">📍 <?= sanitize($r['place_name']) ?>, <?= sanitize($r['place_state']) ?></div>
        <div class="sf" style="font-size:12px;color:var(--dim)"><?= $r['check_in'] ?> → <?= $r['check_out'] ?> · <?= $r['guests'] ?> guests · <?= $nights ?> nights</div>
        <div class="sf" style="font-size:12px;color:var(--muted);margin-top:2px"><?= formatPrice($r['offered_price']) ?>/night · Total <?= formatPrice($total_cost) ?></div>
        <?php if ($r['accept_deadline']): ?>
          <div class="sf" style="font-size:11px;color:var(--yellow);margin-top:2px">Owner deadline: <?= date('d M Y H:i', strtotime($r['accept_deadline'])) ?></div>
        <?php endif; ?>
      </div>
      <div style="text-align:right;display:flex;flex-direction:column;gap:6px;align-items:flex-end">
        <?= statusBadge($r['status']) ?>
        <?php if ($r['paid']): ?>
          <span class="badge-paid">PAID</span>
        <?php endif; ?>
        <?php if ($r['status']==='accepted' && !$r['paid']): ?>
          <a href="/wanderstays/index.php?page=payment&rid=<?= $r['id'] ?>" class="btn-gold btn-sm">Pay Now</a>
        <?php elseif ($r['status']==='rejected'): ?>
          <a href="/wanderstays/index.php?page=property&id=<?= $r['prop_id'] ?>" class="btn-outline btn-sm">Revise Offer</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

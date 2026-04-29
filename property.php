<?php
$pageTitle = 'Property';
require_once __DIR__ . '/../includes/header.php';
requireRole('traveller');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /wanderstays/index.php'); exit; }

$stmt = $conn->prepare("SELECT pr.*, u.full_name as owner_name, pl.name as place_name, pl.state as place_state, pl.id as place_id
    FROM properties pr JOIN users u ON u.id=pr.owner_id JOIN places pl ON pl.id=pr.place_id WHERE pr.id=?");
$stmt->bind_param('i',$id); $stmt->execute();
$prop = $stmt->get_result()->fetch_assoc();
if (!$prop) { header('Location: /wanderstays/index.php'); exit; }

$photos = $conn->query("SELECT file_path FROM property_photos WHERE property_id=$id ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);
$reviews = $conn->query("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id=r.traveller_id WHERE r.property_id=$id ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$avgRating = $prop['rating_count']>0 ? number_format($prop['rating_total']/$prop['rating_count'],1) : null;

$uid = $user['id'];
$stmt2 = $conn->prepare("SELECT * FROM booking_requests WHERE property_id=? AND traveller_id=? ORDER BY created_at DESC LIMIT 1");
$stmt2->bind_param('ii',$id,$uid); $stmt2->execute();
$existingReq = $stmt2->get_result()->fetch_assoc();

// Handle booking form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'book') {
        $ci    = $_POST['check_in'] ?? '';
        $co    = $_POST['check_out'] ?? '';
        $guests = (int)($_POST['guests'] ?? 0);
        $offer  = (float)($_POST['offered_price'] ?? 0);
        $today  = date('Y-m-d');

        if (!$ci) $errors['ci']='Select check-in date';
        elseif ($ci < $today) $errors['ci']='Cannot be in the past';
        if (!$co) $errors['co']='Select check-out date';
        elseif ($co <= $ci) $errors['co']='Must be after check-in';
        if ($guests<1||$guests>$prop['max_guests']) $errors['guests']="Enter 1–{$prop['max_guests']}";
        if ($offer<1) $errors['offer']='Price must be at least ₹1';

        if (empty($errors)) {
            $stmt3 = $conn->prepare("INSERT INTO booking_requests (property_id,traveller_id,check_in,check_out,guests,offered_price,owner_price,status) VALUES (?,?,?,?,?,?,?,'pending')");
            $stmt3->bind_param('iissidd',$id,$uid,$ci,$co,$guests,$offer,$prop['price_per_night']);
            $stmt3->execute();
            header("Location: /wanderstays/index.php?page=property&id=$id&msg=".urlencode('Booking request sent!')); exit;
        }
    }

    if ($action === 'revise' && $existingReq && $existingReq['status']==='rejected') {
        $offer = (float)($_POST['offered_price'] ?? 0);
        if ($offer<1) $errors['offer']='Price must be at least ₹1';
        if (empty($errors)) {
            $rid = $existingReq['id'];
            $stmt4 = $conn->prepare("UPDATE booking_requests SET status='pending', offered_price=? WHERE id=?");
            $stmt4->bind_param('di',$offer,$rid); $stmt4->execute();
            // re-fetch
            $stmt2->execute(); $existingReq = $stmt2->get_result()->fetch_assoc();
            header("Location: /wanderstays/index.php?page=property&id=$id&msg=".urlencode('Revised offer sent!')); exit;
        }
    }

    if ($action === 'review') {
        $rating = (int)($_POST['rating'] ?? 0);
        $text   = trim($_POST['review_text'] ?? '');
        $bid    = (int)($_POST['booking_id'] ?? 0);
        if ($rating<1||$rating>5) $errors['rating']='Select a rating';
        if (strlen($text)<3) $errors['review']='Please write something';
        if (empty($errors)) {
            $stmt5 = $conn->prepare("INSERT INTO reviews (property_id,traveller_id,booking_id,rating,review_text) VALUES (?,?,?,?,?)");
            $stmt5->bind_param('iiiis',$id,$uid,$bid,$rating,$text); $stmt5->execute();
            // update rating totals
            $conn->query("UPDATE properties SET rating_total=rating_total+$rating, rating_count=rating_count+1 WHERE id=$id");
            header("Location: /wanderstays/index.php?page=property&id=$id&msg=".urlencode('Review submitted!')); exit;
        }
    }
}

$hasReviewed = $existingReq && $conn->query("SELECT id FROM reviews WHERE booking_id={$existingReq['id']} LIMIT 1")->num_rows > 0;
$amenities = array_filter(array_map('trim', explode(',', $prop['amenities'])));
?>
<div class="page-wrap">
  <a href="/wanderstays/index.php?page=place&id=<?= $prop['place_id'] ?>" class="btn-outline btn-sm" style="margin-bottom:24px;display:inline-block">← Back to Properties</a>

  <div class="prop-layout">
    <!-- LEFT -->
    <div>
      <!-- Main photo -->
      <div style="border-radius:10px;overflow:hidden;height:340px;background:var(--bg3);margin-bottom:8px">
        <?php if (!empty($photos)): ?>
          <img id="main-photo" src="/wanderstays/<?= sanitize($photos[0]['file_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px">🏨</div>
        <?php endif; ?>
      </div>
      <?php if (count($photos)>1): ?>
      <div class="thumbstrip" style="margin-bottom:18px">
        <?php foreach ($photos as $i=>$ph): ?>
          <img src="/wanderstays/<?= sanitize($ph['file_path']) ?>" class="<?=$i===0?'active':''?>"
               onclick="switchPhoto('/wanderstays/<?= sanitize($ph['file_path']) ?>',this)">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
        <h1 style="font-size:26px"><?= sanitize($prop['name']) ?></h1>
        <span class="tag"><?= sanitize($prop['type']) ?></span>
      </div>
      <p class="sf" style="color:var(--gold);font-size:13px;margin-bottom:6px">📍 <?= sanitize($prop['place_name']) ?>, <?= sanitize($prop['place_state']) ?></p>
      <p class="sf" style="color:var(--muted);font-size:13px;line-height:1.6;margin-bottom:14px"><?= sanitize($prop['description']) ?></p>
      <?php if (!empty($amenities)): ?>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
        <?php foreach ($amenities as $a): ?><span class="tag"><?= sanitize($a) ?></span><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="sf" style="font-size:13px;color:var(--dim)">Listed by <span style="color:var(--gold)"><?= sanitize($prop['owner_name']) ?></span>
        · Max <?= $prop['max_guests'] ?> guests<?= $avgRating ? " · ★ $avgRating ({$prop['rating_count']} reviews)" : '' ?></p>

      <!-- Reviews -->
      <?php if (!empty($reviews)): ?>
      <div style="margin-top:28px">
        <h2 style="font-size:18px;margin-bottom:16px">Guest Reviews</h2>
        <?php foreach ($reviews as $rv): ?>
        <div class="review-item">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <span class="sf" style="font-size:13px;color:var(--gold)"><?= sanitize($rv['full_name']) ?></span>
            <span class="review-stars"><?= str_repeat('★',$rv['rating']) . str_repeat('☆',5-$rv['rating']) ?></span>
          </div>
          <p class="sf" style="font-size:13px;color:var(--muted)"><?= sanitize($rv['review_text']) ?></p>
          <p class="sf" style="font-size:11px;color:var(--dim);margin-top:4px"><?= date('d M Y', strtotime($rv['created_at'])) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT SIDEBAR -->
    <div class="booking-sidebar">
      <div style="margin-bottom:18px">
        <span style="font-size:26px;font-weight:700;color:var(--gold)"><?= formatPrice($prop['price_per_night']) ?></span>
        <span class="sf" style="font-size:12px;color:var(--dim)">/night (listed)</span>
      </div>

      <?php if ($existingReq): ?>
        <div class="booking-summary-box" style="text-align:center">
          <p class="sf" style="color:var(--muted);font-size:12px;margin-bottom:6px">Your request</p>
          <?= statusBadge($existingReq['status']) ?>
          <p class="sf" style="font-size:12px;color:var(--muted);margin-top:8px"><?= formatPrice($existingReq['offered_price']) ?>/night · <?= $existingReq['guests'] ?> guests</p>
          <p class="sf" style="font-size:11px;color:var(--dim);margin-top:3px"><?= $existingReq['check_in'] ?> → <?= $existingReq['check_out'] ?></p>
          <?php if ($existingReq['accept_deadline']): ?>
            <p class="sf" style="font-size:11px;color:var(--yellow);margin-top:4px">Deadline: <?= date('d M Y H:i', strtotime($existingReq['accept_deadline'])) ?></p>
          <?php endif; ?>
        </div>

        <?php if ($existingReq['status']==='accepted' && !$existingReq['paid']): ?>
          <a href="/wanderstays/index.php?page=payment&rid=<?= $existingReq['id'] ?>" class="btn-gold btn-full" style="display:block;padding:12px;text-align:center">Proceed to Payment</a>
        <?php elseif ($existingReq['status']==='accepted' && $existingReq['paid']): ?>
          <span class="badge-paid" style="display:block;text-align:center;padding:8px;font-size:13px">✓ PAYMENT COMPLETE</span>
          <?php if (!$hasReviewed): ?>
          <div style="margin-top:14px">
            <p class="sf" style="font-size:13px;color:var(--muted);margin-bottom:12px">Share your experience</p>
            <form method="POST">
              <input type="hidden" name="action" value="review">
              <input type="hidden" name="booking_id" value="<?= $existingReq['id'] ?>">
              <div style="margin-bottom:10px">
                <label>Your Rating</label>
                <div style="display:flex;gap:4px;margin-top:6px">
                  <?php for($i=1;$i<=5;$i++): ?>
                    <span class="star-pick" data-val="<?=$i?>" style="font-size:26px;cursor:pointer;color:<?=$i<=3?'#c5a55a':'#3a3030'?>">★</span>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating_val" value="3">
                <?php if (isset($errors['rating'])): ?><p class="err"><?= $errors['rating'] ?></p><?php endif; ?>
              </div>
              <div class="form-group">
                <label>Your Review</label>
                <textarea name="review_text" rows="3" placeholder="Share your stay experience…" style="resize:vertical"><?= sanitize($_POST['review_text']??'') ?></textarea>
                <?php if (isset($errors['review'])): ?><p class="err"><?= $errors['review'] ?></p><?php endif; ?>
              </div>
              <button type="submit" class="btn-gold btn-full">Submit Review</button>
            </form>
          </div>
          <?php endif; ?>
        <?php elseif ($existingReq['status']==='rejected'): ?>
          <p class="sf" style="font-size:12px;color:var(--red);margin-bottom:12px">Owner rejected. Revise your offer or cancel.</p>
          <form method="POST">
            <input type="hidden" name="action" value="revise">
            <div class="form-group">
              <label>New Offered Price (₹/night)</label>
              <input type="number" name="offered_price" min="1" value="<?= $existingReq['offered_price'] ?>">
              <?php if (isset($errors['offer'])): ?><p class="err"><?= $errors['offer'] ?></p><?php endif; ?>
            </div>
            <div style="display:flex;gap:8px">
              <button type="submit" class="btn-gold" style="flex:1;padding:10px">Revise Offer</button>
              <a href="/wanderstays/index.php" class="btn-outline" style="flex:1;padding:10px;text-align:center">Cancel</a>
            </div>
          </form>
        <?php endif; ?>

      <?php else: ?>
        <!-- New booking form -->
        <form method="POST">
          <input type="hidden" name="action" value="book">
          <div class="form-group">
            <label>Check-in Date</label>
            <input type="date" name="check_in" id="check_in" min="<?= date('Y-m-d') ?>" value="<?= sanitize($_POST['check_in']??'') ?>" required>
            <?php if (isset($errors['ci'])): ?><p class="err"><?= $errors['ci'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label>Check-out Date</label>
            <input type="date" name="check_out" id="check_out" min="<?= date('Y-m-d') ?>" value="<?= sanitize($_POST['check_out']??'') ?>" required>
            <?php if (isset($errors['co'])): ?><p class="err"><?= $errors['co'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label>Guests (1–<?= $prop['max_guests'] ?>)</label>
            <input type="number" name="guests" id="guests" min="1" max="<?= $prop['max_guests'] ?>" value="<?= (int)($_POST['guests']??1) ?>" required>
            <?php if (isset($errors['guests'])): ?><p class="err"><?= $errors['guests'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label>Your Offered Price (₹/night)</label>
            <input type="number" name="offered_price" id="offered_price" min="1" value="<?= (int)($_POST['offered_price']??$prop['price_per_night']) ?>" data-listed="<?= $prop['price_per_night'] ?>" required>
            <p class="warn" id="bargain-warn" style="display:none">Below listed price — owner approval needed</p>
            <?php if (isset($errors['offer'])): ?><p class="err"><?= $errors['offer'] ?></p><?php endif; ?>
          </div>
          <div id="price-total" class="sf" style="background:var(--bg3);border-radius:4px;padding:10px;font-size:13px;color:var(--muted);display:none;margin-bottom:12px"></div>
          <button type="submit" class="btn-gold btn-full" style="padding:12px">Send Booking Request</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

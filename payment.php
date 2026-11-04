<?php
$pageTitle = 'Payment';
require_once __DIR__ . '/../includes/header.php';
requireRole('traveller');

$rid = (int)($_GET['rid'] ?? 0);
if (!$rid) { header('Location: /wanderstays/index.php?page=dashboard'); exit; }

$uid = $user['id'];
$stmt = $conn->prepare("SELECT br.*, pr.name as prop_name, pr.price_per_night,
    (SELECT file_path FROM property_photos pp WHERE pp.property_id=br.property_id ORDER BY sort_order LIMIT 1) as cover
    FROM booking_requests br JOIN properties pr ON pr.id=br.property_id
    WHERE br.id=? AND br.traveller_id=? AND br.status='accepted' AND br.paid=0");
$stmt->bind_param('ii',$rid,$uid); $stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
if (!$req) { header('Location: /wanderstays/index.php?page=dashboard&msg='.urlencode('Request not found').'&type=error'); exit; }

$nights = nightsBetween($req['check_in'],$req['check_out']);
$total  = $req['offered_price'] * $nights;
$errors = [];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $method = $_POST['pay_method'] ?? 'card';
    if ($method==='card') {
        $cardNum = preg_replace('/\s+/','',$_POST['card_number']??'');
        $expiry  = $_POST['card_expiry'] ?? '';
        $cvv     = $_POST['card_cvv'] ?? '';
        if (strlen($cardNum)!==16||!ctype_digit($cardNum)) $errors['card']='Enter valid 16-digit card number';
        if (!preg_match('/^\d{2}\/\d{2}$/',$expiry)) $errors['expiry']='Format MM/YY required';
        if (strlen($cvv)<3||!ctype_digit($cvv)) $errors['cvv']='Invalid CVV';
    } else {
        $upiId = trim($_POST['upi_id']??'');
        if (!str_contains($upiId,'@')) $errors['upi']='Enter valid UPI ID (e.g. name@upi)';
    }
    if (empty($errors)) {
        $now = date('Y-m-d H:i:s');
        $stmt2 = $conn->prepare("UPDATE booking_requests SET paid=1,payment_method=?,paid_at=? WHERE id=?");
        $stmt2->bind_param('ssi',$method,$now,$rid); $stmt2->execute();
        header('Location: /wanderstays/index.php?page=dashboard&msg='.urlencode('Payment successful! Booking confirmed!')); exit;
    }
}
?>
<div class="page-wrap-xs">
  <a href="/wanderstays/index.php?page=dashboard" class="btn-outline btn-sm" style="margin-bottom:24px;display:inline-block">← Back to My Trips</a>
  <h1 style="font-size:26px;margin-bottom:6px">Complete Payment</h1>
  <p class="sf" style="color:var(--dim);margin-bottom:28px;font-size:13px"><?= sanitize($req['prop_name']) ?></p>

  <!-- Summary -->
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:20px;margin-bottom:20px">
    <p class="sf" style="font-size:11px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px">Booking Summary</p>
    <?php if ($req['cover']): ?>
      <img src="/wanderstays/<?= sanitize($req['cover']) ?>" style="width:100%;height:130px;object-fit:cover;border-radius:4px;margin-bottom:14px">
    <?php endif; ?>
    <div class="sf" style="display:flex;flex-direction:column;gap:7px;font-size:13px">
      <div style="display:flex;justify-content:space-between"><span style="color:var(--dim)">Dates</span><span><?= $req['check_in'] ?> → <?= $req['check_out'] ?></span></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--dim)">Guests</span><span><?= $req['guests'] ?></span></div>
      <div style="display:flex;justify-content:space-between"><span style="color:var(--dim)">Rate</span><span><?= formatPrice($req['offered_price']) ?>/night × <?= $nights ?> nights</span></div>
      <div style="border-top:1px solid var(--border);padding-top:10px;margin-top:4px;display:flex;justify-content:space-between;font-size:18px">
        <span style="color:var(--muted)">Total</span>
        <strong style="color:var(--gold)"><?= formatPrice($total) ?></strong>
      </div>
    </div>
  </div>

  <!-- Payment form -->
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:22px">
    <form method="POST" id="pay-form">
      <div class="pay-tabs">
        <button type="button" class="pay-tab active" data-tab="card">Credit/Debit Card</button>
        <button type="button" class="pay-tab" data-tab="upi">UPI</button>
      </div>
      <input type="hidden" name="pay_method" id="pay-method-val" value="card">

      <div class="pay-section active" id="pay-card">
        <div class="form-group">
          <label>Card Number</label>
          <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19" value="<?= sanitize($_POST['card_number']??'') ?>">
          <?php if (isset($errors['card'])): ?><p class="err"><?= $errors['card'] ?></p><?php endif; ?>
        </div>
        <div class="form-row col2">
          <div class="form-group">
            <label>Expiry (MM/YY)</label>
            <input type="text" name="card_expiry" id="card_expiry" placeholder="12/27" maxlength="5" value="<?= sanitize($_POST['card_expiry']??'') ?>">
            <?php if (isset($errors['expiry'])): ?><p class="err"><?= $errors['expiry'] ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label>CVV</label>
            <input type="password" name="card_cvv" placeholder="•••" maxlength="4" value="">
            <?php if (isset($errors['cvv'])): ?><p class="err"><?= $errors['cvv'] ?></p><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="pay-section" id="pay-upi">
        <div class="form-group">
          <label>UPI ID</label>
          <input type="text" name="upi_id" placeholder="name@okicici" value="<?= sanitize($_POST['upi_id']??'') ?>">
          <?php if (isset($errors['upi'])): ?><p class="err"><?= $errors['upi'] ?></p><?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn-gold btn-full" style="padding:13px;font-size:15px;margin-top:8px">Pay <?= formatPrice($total) ?></button>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.pay-tab').forEach(btn=>{
  btn.addEventListener('click',function(){
    document.querySelectorAll('.pay-tab').forEach(b=>b.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.pay-section').forEach(s=>s.classList.remove('active'));
    document.getElementById('pay-'+this.dataset.tab).classList.add('active');
    document.getElementById('pay-method-val').value=this.dataset.tab;
  });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

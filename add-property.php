<?php
$pageTitle = 'Add Property';
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$uid    = $user['id'];
$types  = ['Hotel','Resort','Villa','Airbnb','Homestay','Guesthouse','Cottage'];
$errors = [];
$uploadMsg = '';

$places = $conn->query(
    "SELECT id, name, state FROM places ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']        ?? '');
    $placeId   = (int)($_POST['place_id']   ?? 0);
    $type      = $_POST['type']              ?? 'Hotel';
    $price     = (float)($_POST['price']    ?? 0);
    $maxGuests = (int)($_POST['max_guests'] ?? 0);
    $desc      = trim($_POST['description'] ?? '');
    $amenities = trim($_POST['amenities']   ?? '');

    if (strlen($name) < 2)   $errors['name']   = 'Property name is required';
    if (!$placeId)            $errors['place']  = 'Please select a destination';
    if ($price < 1)           $errors['price']  = 'Price must be at least ₹1';
    if ($maxGuests < 1)       $errors['guests'] = 'Enter a valid guest count (min 1)';
    if (strlen($desc) < 5)   $errors['desc']   = 'Description is required';

    $filesReceived = !empty($_FILES['photos']['tmp_name']) &&
                     $_FILES['photos']['tmp_name'][0] !== '';
    if (!$filesReceived) {
        $errors['photos'] = 'Please select at least one photo';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO properties
             (owner_id,place_id,name,type,price_per_night,max_guests,description,amenities)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('iissdiss',
            $uid, $placeId, $name, $type,
            $price, $maxGuests, $desc, $amenities
        );
        $stmt->execute();
        $propId = $conn->insert_id;

        $paths = uploadPhotos($_FILES['photos'], 'properties');

        if (empty($paths)) {
            $uploadMsg = 'Photos could not be saved. ';
            $dir = __DIR__ . '/../uploads/properties/';
            if (!is_writable($dir)) {
                $uploadMsg .= 'The uploads/properties/ folder is not writable. ';
                $uploadMsg .= 'Run: <code>chmod -R 777 /Applications/XAMPP/htdocs/wanderstays/uploads/</code>';
            } else {
                $uploadMsg .= 'Visit <a href="/wanderstays/fix-mac.php" target="_blank">fix-mac.php</a> for full diagnostics.';
            }
        } else {
            foreach ($paths as $i => $path) {
                $stmt2 = $conn->prepare(
                    "INSERT INTO property_photos (property_id,file_path,sort_order) VALUES (?,?,?)"
                );
                $stmt2->bind_param('isi', $propId, $path, $i);
                $stmt2->execute();
            }
            header('Location: /wanderstays/index.php?msg=' . urlencode('Property listed successfully!'));
            exit;
        }
    }
}
?>
<div class="page-wrap-sm">
  <a href="/wanderstays/index.php" class="btn-outline btn-sm"
     style="margin-bottom:24px;display:inline-block">← Back</a>
  <h1 style="margin-bottom:6px">List a Property</h1>
  <p class="sf" style="color:var(--dim);margin-bottom:32px;font-size:13px">
    Add your hotel, resort, villa or rental for travellers to discover
  </p>

  <?php if ($uploadMsg): ?>
  <div style="background:#1f0d0d;border:1px solid #7a2a2a;border-radius:6px;
              padding:14px 16px;margin-bottom:20px;font-family:'DM Sans',sans-serif;
              font-size:13px;color:#e07070;line-height:1.6">
    ⚠ <?= $uploadMsg ?>
    <br><br>
    <a href="/wanderstays/fix-mac.php" target="_blank"
       style="color:#c5a55a;text-decoration:underline">
      → Run the Mac fix script to auto-diagnose
    </a>
  </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

    <div class="form-group">
      <label>Property Name *</label>
      <input type="text" name="name"
             placeholder="e.g. Haveli Palace Hotel"
             value="<?= sanitize($_POST['name'] ?? '') ?>" required>
      <?php if (isset($errors['name'])): ?>
        <p class="err"><?= $errors['name'] ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Destination *</label>
      <select name="place_id" required>
        <option value="">Select destination…</option>
        <?php foreach ($places as $pl): ?>
          <option value="<?= $pl['id'] ?>"
            <?= (int)($_POST['place_id'] ?? 0) === $pl['id'] ? 'selected' : '' ?>>
            <?= sanitize($pl['name']) ?> — <?= sanitize($pl['state']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($places)): ?>
        <p class="warn">No destinations yet.
          <a href="/wanderstays/index.php?page=add-place"
             style="color:var(--gold)">Add one first →</a>
        </p>
      <?php endif; ?>
      <?php if (isset($errors['place'])): ?>
        <p class="err"><?= $errors['place'] ?></p>
      <?php endif; ?>
    </div>

    <div class="form-row col3">
      <div class="form-group">
        <label>Type</label>
        <select name="type">
          <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>"
              <?= ($_POST['type'] ?? '') === $t ? 'selected' : '' ?>>
              <?= $t ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Price / night (₹) *</label>
        <input type="number" name="price" min="1"
               placeholder="e.g. 4500"
               value="<?= (int)($_POST['price'] ?? '') ?>" required>
        <?php if (isset($errors['price'])): ?>
          <p class="err"><?= $errors['price'] ?></p>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Max Guests *</label>
        <input type="number" name="max_guests" min="1"
               placeholder="e.g. 4"
               value="<?= (int)($_POST['max_guests'] ?? '') ?>" required>
        <?php if (isset($errors['guests'])): ?>
          <p class="err"><?= $errors['guests'] ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-group">
      <label>Description *</label>
      <textarea name="description" rows="3"
                placeholder="Describe what makes your property special…"
                style="resize:vertical"><?= sanitize($_POST['description'] ?? '') ?></textarea>
      <?php if (isset($errors['desc'])): ?>
        <p class="err"><?= $errors['desc'] ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Amenities (comma separated)</label>
      <input type="text" name="amenities"
             placeholder="WiFi, Pool, AC, Breakfast, Spa…"
             value="<?= sanitize($_POST['amenities'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Property Photos * (up to 8 images, max 10 MB each)</label>
      <div class="upload-zone" id="upload-zone">
        <input type="file"
               name="photos[]"
               id="photos"
               multiple
               accept="image/*,image/heic,image/heif">
        <div style="font-size:28px;margin-bottom:8px">📷</div>
        <p class="sf" style="font-size:13px;color:var(--muted)">
          Click to browse or drag photos here
        </p>
        <p class="sf" style="font-size:11px;color:var(--dim);margin-top:4px">
          JPG · PNG · WebP · HEIC (Mac) supported
        </p>
      </div>
      <div class="thumb-grid" id="photo-preview"></div>
      <?php if (isset($errors['photos'])): ?>
        <p class="err"><?= $errors['photos'] ?></p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn-gold btn-full"
            style="padding:13px;margin-top:8px">
      Publish Property
    </button>

    <p class="sf" style="font-size:11px;color:var(--dim);margin-top:12px;text-align:center">
      Having trouble uploading?
      <a href="/wanderstays/fix-mac.php" target="_blank"
         style="color:var(--gold)">Run the Mac fix script →</a>
    </p>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
$pageTitle = 'Add Destination';
require_once __DIR__ . '/../includes/header.php';
requireRole('owner');

$states = ['Rajasthan','Himachal Pradesh','Goa','Kerala','Uttarakhand','Tamil Nadu',
           'Maharashtra','Jammu & Kashmir','Gujarat','Karnataka','West Bengal',
           'Meghalaya','Sikkim','Andaman & Nicobar'];
$types  = ['Hotel','Resort','Villa','Airbnb','Homestay','Guesthouse','Cottage'];
$errors = [];
$uploadMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $state = $_POST['state']       ?? '';
    $type  = $_POST['type']        ?? '';
    $desc  = trim($_POST['description'] ?? '');

    if (strlen($name) < 2)          $errors['name']   = 'Destination name is required';
    if (!in_array($state, $states)) $errors['state']  = 'Please select a valid state';
    if (strlen($desc) < 5)          $errors['desc']   = 'Description is required';

    // Check if any files were actually received
    $filesReceived = !empty($_FILES['photos']['tmp_name']) &&
                     $_FILES['photos']['tmp_name'][0] !== '';

    if (!$filesReceived) {
        $errors['photos'] = 'Please select at least one photo';
    }

    if (empty($errors)) {
        $uid = $user['id'];
        $stmt = $conn->prepare(
            "INSERT INTO places (owner_id,name,state,type,description) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('issss', $uid, $name, $state, $type, $desc);
        $stmt->execute();
        $placeId = $conn->insert_id;

        $paths = uploadPhotos($_FILES['photos'], 'places');

        if (empty($paths)) {
            // Upload function ran but nothing saved — diagnose why
            $uploadMsg = 'Photos could not be saved. ';
            $dir = __DIR__ . '/../uploads/places/';
            if (!is_writable($dir)) {
                $uploadMsg .= 'The uploads/places/ folder is not writable. ';
                $uploadMsg .= 'Open Terminal and run: <code>chmod -R 777 /Applications/XAMPP/htdocs/wanderstays/uploads/</code>';
            } else {
                $uploadMsg .= 'Check that upload_max_filesize and post_max_size are large enough in php.ini, ';
                $uploadMsg .= 'then visit <a href="/wanderstays/fix-mac.php" target="_blank">fix-mac.php</a> for full diagnostics.';
            }
        } else {
            foreach ($paths as $i => $path) {
                $stmt2 = $conn->prepare(
                    "INSERT INTO place_photos (place_id,file_path,sort_order) VALUES (?,?,?)"
                );
                $stmt2->bind_param('isi', $placeId, $path, $i);
                $stmt2->execute();
            }
            header('Location: /wanderstays/index.php?msg=' . urlencode('Destination added successfully!'));
            exit;
        }
    }
}
?>
<div class="page-wrap-sm">
  <a href="/wanderstays/index.php" class="btn-outline btn-sm"
     style="margin-bottom:24px;display:inline-block">← Back</a>
  <h1 style="margin-bottom:6px">Add Destination</h1>
  <p class="sf" style="color:var(--dim);margin-bottom:32px;font-size:13px">
    List a new destination where your property is located
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
    <!-- MAX_FILE_SIZE hint for PHP — 10 MB -->
    <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

    <div class="form-group">
      <label>Destination Name *</label>
      <input type="text" name="name"
             placeholder="e.g. Jaipur Pink City"
             value="<?= sanitize($_POST['name'] ?? '') ?>" required>
      <?php if (isset($errors['name'])): ?>
        <p class="err"><?= $errors['name'] ?></p>
      <?php endif; ?>
    </div>

    <div class="form-row col2">
      <div class="form-group">
        <label>State *</label>
        <select name="state" required>
          <option value="">Select state…</option>
          <?php foreach ($states as $s): ?>
            <option value="<?= $s ?>"
              <?= ($_POST['state'] ?? '') === $s ? 'selected' : '' ?>>
              <?= $s ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['state'])): ?>
          <p class="err"><?= $errors['state'] ?></p>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Primary Property Type</label>
        <select name="type">
          <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>"
              <?= ($_POST['type'] ?? '') === $t ? 'selected' : '' ?>>
              <?= $t ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Description *</label>
      <textarea name="description" rows="3"
                placeholder="Describe this destination…"
                style="resize:vertical"><?= sanitize($_POST['description'] ?? '') ?></textarea>
      <?php if (isset($errors['desc'])): ?>
        <p class="err"><?= $errors['desc'] ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Destination Photos * (up to 8 images, max 10 MB each)</label>
      <div class="upload-zone" id="upload-zone">
        <!--
          accept="image/*" covers HEIC/HEIF on Mac.
          capture attribute is intentionally omitted so the file picker opens normally.
        -->
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
      Publish Destination
    </button>

    <p class="sf" style="font-size:11px;color:var(--dim);margin-top:12px;text-align:center">
      Having trouble uploading?
      <a href="/wanderstays/fix-mac.php" target="_blank"
         style="color:var(--gold)">Run the Mac fix script →</a>
    </p>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

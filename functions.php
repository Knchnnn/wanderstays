<?php

/**
 * Upload photos from a multi-file input.
 * Mac-safe: uses finfo extension check instead of relying on browser MIME type,
 * and creates upload dirs with 0777 to avoid XAMPP permission issues on Mac.
 */
function uploadPhotos($files, $subfolder) {
    $paths    = [];
    $uploadDir = __DIR__ . '/../uploads/' . $subfolder . '/';

    // Create directory if missing — 0777 so XAMPP on Mac can write
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    // Ensure existing dir is writable
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0777);
    }

    // Allowed extensions and their valid magic-byte MIME types
    $allowedExts  = ['jpg','jpeg','png','gif','webp','heic','heif'];
    $allowedMimes = [
        'image/jpeg','image/jpg','image/png','image/gif',
        'image/webp','image/heic','image/heif',
        'application/octet-stream', // Mac sometimes sends this for HEIC
    ];

    // finfo for real MIME detection (ignores browser-reported type)
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    $names = $files['name']     ?? [];
    $tmps  = $files['tmp_name'] ?? [];
    $errs  = $files['error']    ?? [];
    $sizes = $files['size']     ?? [];

    foreach ($tmps as $i => $tmp) {
        // Skip slots with no file or upload errors
        if (empty($tmp) || ($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        // Skip oversized files (10 MB limit — generous for Mac HEIC)
        if (($sizes[$i] ?? 0) > 10 * 1024 * 1024) {
            continue;
        }

        // Determine extension from original filename
        $origName = $names[$i] ?? '';
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Convert HEIC/HEIF to jpg label for storage (actual conversion needs ImageMagick;
        // here we just store the original binary and keep the extension)
        if (!in_array($ext, $allowedExts)) {
            continue;
        }

        // Real MIME check via finfo (ignores what the browser claims)
        if ($finfo) {
            $realMime = finfo_file($finfo, $tmp);
            // Allow if real MIME is an image OR octet-stream (common on Mac for HEIC)
            if (!in_array($realMime, $allowedMimes) && strpos($realMime, 'image/') !== 0) {
                continue;
            }
        } else {
            // Fallback: trust browser MIME if finfo unavailable
            $browserMime = $files['type'][$i] ?? '';
            if (!in_array($browserMime, $allowedMimes) && strpos($browserMime, 'image/') !== 0) {
                continue;
            }
        }

        // Generate unique filename
        $safeName = uniqid('img_', true) . '.' . $ext;
        $dest     = $uploadDir . $safeName;

        if (move_uploaded_file($tmp, $dest)) {
            chmod($dest, 0644); // readable by Apache
            $paths[] = 'uploads/' . $subfolder . '/' . $safeName;
        }
    }

    if ($finfo) {
        finfo_close($finfo);
    }

    return $paths;
}

/**
 * Debug helper — call from add-place.php temporarily to see what PHP sees.
 * Remove after fixing.
 */
function debugUpload($files) {
    $out  = "<pre style='background:#111;color:#9f9;padding:16px;border-radius:6px;font-size:12px;text-align:left'>";
    $out .= "upload_max_filesize : " . ini_get('upload_max_filesize') . "\n";
    $out .= "post_max_size       : " . ini_get('post_max_size') . "\n";
    $out .= "file_uploads        : " . ini_get('file_uploads') . "\n";
    $out .= "upload_tmp_dir      : " . (ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) . "\n";
    $out .= "finfo available     : " . (function_exists('finfo_open') ? 'YES' : 'NO') . "\n\n";
    foreach (($files['tmp_name'] ?? []) as $i => $tmp) {
        $out .= "File $i: name={$files['name'][$i]}  size={$files['size'][$i]}  ";
        $out .= "error={$files['error'][$i]}  browser_mime={$files['type'][$i]}\n";
        if ($tmp && function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $out .= "        real_mime=" . finfo_file($fi, $tmp) . "\n";
            finfo_close($fi);
        }
    }
    $out .= "</pre>";
    return $out;
}

/* ── General helpers ─────────────────────────────────────── */

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
}

function formatPrice($n) {
    return '₹' . number_format((float)$n, 0, '.', ',');
}

function nightsBetween($checkin, $checkout) {
    try {
        $d1 = new DateTime($checkin);
        $d2 = new DateTime($checkout);
        return max(0, (int)$d1->diff($d2)->days);
    } catch (Exception $e) {
        return 0;
    }
}

function getFirstPhoto($photos) {
    return !empty($photos) ? $photos[0]['file_path'] : null;
}

function statusBadge($status) {
    $map = [
        'pending'  => ['bg'=>'#332a00','border'=>'#c8a000','color'=>'#f0c800','label'=>'PENDING'],
        'accepted' => ['bg'=>'#002a12','border'=>'#00843d','color'=>'#00c85a','label'=>'ACCEPTED'],
        'rejected' => ['bg'=>'#2a0000','border'=>'#c80000','color'=>'#ff4444','label'=>'REJECTED'],
    ];
    $s = $map[$status] ?? $map['pending'];
    return "<span style='background:{$s['bg']};border:1px solid {$s['border']};color:{$s['color']};
            padding:2px 10px;border-radius:20px;font-size:11px;font-family:DM Sans,sans-serif;
            display:inline-block'>{$s['label']}</span>";
}

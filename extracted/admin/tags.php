<?php
require_once __DIR__ . '/_admin.php';

$msg = '';

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    if (!csrf_check()) { echo json_encode(['success' => false, 'message' => 'Invalid token']); exit; }
    $response = ['success' => false];

    if ($_POST['ajax_action'] === 'delete_tag') {
        $tag = sanitize_tag_single($_POST['tag'] ?? '');
        if ($tag !== '') {
            $response['success'] = vl_rename_or_delete_tag($pdo, $tag, null);
        }
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_tag'])) {
    if (!csrf_check()) {
        $msg = 'Session expired. Please try again.';
    } else {
        $old = sanitize_tag_single($_POST['tag'] ?? '');
        $new = sanitize_tag_single($_POST['new_name'] ?? '');
        if ($old === '') {
            $msg = 'Invalid tag.';
        } elseif ($new === '') {
            $msg = 'Enter a new name for the tag.';
        } elseif (mb_strtolower($new) === mb_strtolower($old)) {
            $msg = 'Tag unchanged.';
        } else {
            vl_rename_or_delete_tag($pdo, $old, $new);
            $msg = 'Tag renamed to "' . $new . '".';
        }
    }
}

// Aggregate tags from all published + draft videos
$tag_counts = [];
try {
    $rows = $pdo->query("SELECT id, tags FROM videos WHERE tags IS NOT NULL AND tags != ''")->fetchAll();
    foreach ($rows as $row) {
        foreach (get_video_tags($row) as $t) {
            $key = mb_strtolower($t);
            if (!isset($tag_counts[$key])) $tag_counts[$key] = ['name' => $t, 'count' => 0];
            $tag_counts[$key]['count']++;
        }
    }
    usort($tag_counts, function ($a, $b) { return $b['count'] - $a['count']; });
} catch (Exception $e) {}

admin_header('Tags', 'tags');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">All Tags (<?php echo count($tag_counts); ?>)</div>
    </div>
    <div class="hint" style="margin-bottom:14px;">
        Tags live on each video (comma-separated in the video editor). Renaming a tag updates every video that uses it;
        deleting removes it from every video. Tags are also shown publicly on <a href="/tags" target="_blank">/tags</a>.
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Tag</th><th>Videos</th><th style="width:420px;">Rename</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($tag_counts as $t): ?>
                <tr>
                    <td style="font-weight:600;">
                        <a href="/tag/<?php echo urlencode($t['name']); ?>" target="_blank" style="color:#fff;text-decoration:none;">#<?php echo e($t['name']); ?></a>
                    </td>
                    <td><?php echo number_format($t['count']); ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="rename_tag" value="1">
                            <input type="hidden" name="tag" value="<?php echo e($t['name']); ?>">
                            <input type="text" name="new_name" value="<?php echo e($t['name']); ?>" style="max-width:220px;" required>
                            <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                        </form>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteTag('<?php echo e(addslashes($t['name'])); ?>')">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($tag_counts)): ?>
                <tr><td colspan="4" style="color:var(--text-muted);">No tags yet. Add comma-separated tags when editing a video.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteTag(tag) {
    if (!confirm('Delete the tag "' + tag + '" from every video? This cannot be undone.')) return;
    adminPost('ajax_action=delete_tag&tag=' + encodeURIComponent(tag), function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}
</script>

<?php admin_footer(); ?>

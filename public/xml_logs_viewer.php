<?php
$logDir = __DIR__ . '/xml_logs';
$files = glob($logDir . '/*');
$selected = null;
$content = null;

if (!empty($_GET['file'])) {
    $requested = realpath($logDir . '/' . basename($_GET['file']));
    $logDirReal = realpath($logDir);

    if ($requested && $logDirReal && strpos($requested, $logDirReal) === 0 && is_file($requested)) {
        $selected = basename($requested);
        $content = file_get_contents($requested);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XML Log Viewer</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; background: #f5f6f8; color: #24292f; }
        .header { background: #24292f; color: #fff; padding: 14px 24px; }
        .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        .container { display: flex; gap: 16px; padding: 16px 24px; align-items: flex-start; }
        .sidebar { width: 280px; flex-shrink: 0; background: #fff; border: 1px solid #e1e4e8; border-radius: 6px; overflow: hidden; }
        .sidebar h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .5px; color: #57606a; padding: 12px 14px 8px; margin: 0; border-bottom: 1px solid #e1e4e8; }
        .sidebar ul { list-style: none; margin: 0; padding: 6px 0; }
        .sidebar li a { display: block; padding: 8px 14px; color: #24292f; text-decoration: none; font-size: 14px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .sidebar li a:hover { background: #f6f8fa; }
        .sidebar li a.active { background: #0969da; color: #fff; }
        .empty { padding: 14px; color: #57606a; font-size: 14px; }
        .content { flex: 1; background: #fff; border: 1px solid #e1e4e8; border-radius: 6px; overflow: hidden; min-width: 0; }
        .content h2 { font-size: 14px; padding: 12px 14px; margin: 0; border-bottom: 1px solid #e1e4e8; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        pre { margin: 0; padding: 14px; overflow-x: auto; font-size: 13px; line-height: 1.5; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h1>XML Log Viewer</h1>
    </div>
    <div class="container">
        <div class="sidebar">
            <h2>Log Files</h2>
            <?php if (!$files): ?>
                <div class="empty">No log files found.</div>
            <?php else: ?>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <?php $name = basename($file); ?>
                        <li>
                            <a href="?file=<?= urlencode($name) ?>" class="<?= $selected === $name ? 'active' : '' ?>"><?= htmlspecialchars($name) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="content">
            <?php if ($selected !== null && $content !== null): ?>
                <h2><?= htmlspecialchars($selected) ?> (<?= number_format(strlen($content)) ?> bytes)</h2>
                <pre><?= htmlspecialchars($content) ?></pre>
            <?php else: ?>
                <h2>No file selected</h2>
                <pre>Select a log file from the list on the left.</pre>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

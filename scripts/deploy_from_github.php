<?php
require_once __DIR__ . '/sync_to_live.php';

$commit = trim(shell_exec('git rev-parse HEAD'));
$code = <<<PHP
\$base_raw = 'https://raw.githubusercontent.com/chidokato/sunshinegroup/{$commit}/';

\$files = [
    // Theme files
    'wp-content/themes/flatsome-child/style.css',
    'wp-content/themes/flatsome-child/functions.php',
    'wp-content/themes/flatsome-child/single-du_an.php',
    'wp-content/themes/flatsome-child/inc/sunshine-project-manager.php',
    'wp-content/themes/flatsome-child/template-parts/posts/archive.php',
    'wp-content/themes/flatsome-child/template-parts/posts/archive-3-col.php',
    'wp-content/themes/flatsome-child/template-parts/posts/partials/archive-title.php',
    // Plugin files
    'wp-content/plugins/sunshine-project-manager/sunshine-project-manager.php',
];

\$results = [];
foreach (\$files as \$file) {
    \$url = \$base_raw . \$file . '?t=' . time();
    \$ctx = stream_context_create(['http' => ['header' => "Cache-Control: no-cache\r\nPragma: no-cache\r\n"]]);
    \$content = @file_get_contents(\$url, false, \$ctx);
    if (\$content !== false && strlen(\$content) > 0) {
        \$dest = ABSPATH . \$file;
        \$dir = dirname(\$dest);
        if (!is_dir(\$dir)) {
            @mkdir(\$dir, 0755, true);
        }
        \$written = file_put_contents(\$dest, \$content);
        \$results[\$file] = (\$written > 0) ? 'OK (' . \$written . ' bytes)' : 'FAIL_WRITE';
    } else {
        \$results[\$file] = 'FAIL_DOWNLOAD';
    }
}

// Chuyển Theme sang Flatsome Child
switch_theme('flatsome-child');

// Kích hoạt Plugin Sunshine Project Manager
require_once ABSPATH . 'wp-admin/includes/plugin.php';
activate_plugin('sunshine-project-manager/sunshine-project-manager.php');

\$results['active_theme'] = get_stylesheet();
\$results['is_plugin_active'] = is_plugin_active('sunshine-project-manager/sunshine-project-manager.php');

update_option('sunshine_github_deploy_results', json_encode(\$results));
header('X-Sunshine-Deploy-Status: DONE');
PHP;

$sync = new SunshineLiveSync();

echo "1. Cài đặt code-snippets hỗ trợ triển khai...\n";
$sync->request('/wp/v2/plugins', 'POST', ['slug' => 'code-snippets', 'status' => 'active']);

echo "2. Đang tạo snippet tải code từ GitHub...\n";
$res = $sync->request('/code-snippets/v1/snippets', 'POST', [
    'name'   => 'Sunshine GitHub Deployer',
    'code'   => $code,
    'scope'  => 'global',
    'active' => true,
]);

$id = $res['body']['id'] ?? 0;
echo "Snippet ID: {$id}\n";

if ($id > 0) {
    $sync->request("/code-snippets/v1/snippets/{$id}/activate", 'POST');
    
    echo "3. Kích hoạt chạy trên server live...\n";
    $ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
    $f = @file_get_contents('https://sunshine-group.com.vn/', false, $ctx);
    
    // Đọc kết quả từ option
    $res_read = $sync->request('/code-snippets/v1/snippets', 'POST', [
        'name'   => 'ReadDeployResult',
        'code'   => 'header("X-Res: " . base64_encode(get_option("sunshine_github_deploy_results")));',
        'scope'  => 'global',
        'active' => true,
    ]);
    $read_id = $res_read['body']['id'] ?? 0;
    $sync->request("/code-snippets/v1/snippets/{$read_id}/activate", 'POST');
    
    $f2 = @file_get_contents('https://sunshine-group.com.vn/', false, $ctx);
    foreach ($http_response_header as $h) {
        if (stripos($h, 'X-Res:') !== false) {
            $val = trim(substr($h, strpos($h, ':') + 1));
            echo "\n=== KẾT QUẢ TRIỂN KHAI TỪ GITHUB ===\n";
            print_r(json_decode(base64_decode($val), true));
        }
    }
    
    echo "\n4. Dọn dẹp công cụ triển khai...\n";
    $sync->request("/code-snippets/v1/snippets/{$id}", 'DELETE');
    $sync->request("/code-snippets/v1/snippets/{$read_id}", 'DELETE');
    $sync->request('/wp/v2/plugins/code-snippets/code-snippets', 'POST', ['status' => 'inactive']);
    $sync->request('/wp/v2/plugins/code-snippets/code-snippets', 'DELETE');
    echo "[XONG] Hoàn tất triển khai!\n";
}

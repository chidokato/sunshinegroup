<?php
require_once __DIR__ . '/../wp-load.php';

echo "=== 1. AUDIT DỰ ÁN (PILLAR PAGES - CẤP 2) ===\n";
$projects = get_posts([
    'post_type'      => 'du_an',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

foreach ($projects as $p) {
    $focuskw = get_post_meta($p->ID, 'rank_math_focus_keyword', true);
    $title = get_post_meta($p->ID, 'rank_math_title', true);
    $desc = get_post_meta($p->ID, 'rank_math_description', true);
    $pillar = get_post_meta($p->ID, 'rank_math_pillar_content', true);
    
    echo "ID: {$p->ID} | Slug: {$p->post_name}\n";
    echo "  Post Title: {$p->post_title}\n";
    echo "  SEO Title:  " . ($title ? $title : '[MẶC ĐỊNH]') . "\n";
    echo "  Focus KW:   " . ($focuskw ? $focuskw : '[CHƯA CÓ]') . "\n";
    echo "  Pillar:     " . ($pillar ? $pillar : 'off') . "\n";
    echo "  Meta Desc:  " . ($desc ? substr($desc, 0, 80) . '...' : '[CHƯA CÓ]') . "\n\n";
}

echo "\n=== 2. AUDIT BÀI VIẾT VỆ TINH (CLUSTER POSTS - CẤP 3) ===\n";
$posts = get_posts([
    'post_type'      => 'post',
    'posts_per_page' => -1,
    'post_status'    => 'publish'
]);

foreach ($posts as $p) {
    $focuskw = get_post_meta($p->ID, 'rank_math_focus_keyword', true);
    $title = get_post_meta($p->ID, 'rank_math_title', true);
    $desc = get_post_meta($p->ID, 'rank_math_description', true);
    
    // Check internal links to /du-an/
    preg_match_all('/href=["\'](https?:\/\/[^"\']*\/du-an\/[^"\']*)["\']/i', $p->post_content, $links);
    $du_an_links = array_unique($links[1]);
    
    echo "ID: {$p->ID} | Slug: {$p->post_name}\n";
    echo "  Post Title: {$p->post_title}\n";
    echo "  SEO Title:  " . ($title ? $title : '[MẶC ĐỊNH]') . "\n";
    echo "  Focus KW:   " . ($focuskw ? $focuskw : '[CHƯA CÓ]') . "\n";
    echo "  Meta Desc:  " . ($desc ? substr($desc, 0, 80) . '...' : '[CHƯA CÓ]') . "\n";
    echo "  Internal Links to Pillar: " . (count($du_an_links) ? implode(', ', $du_an_links) : '[CHƯA CÓ LINK TRỎ VỀ PILLAR]') . "\n\n";
}

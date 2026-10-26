<?php
global $post;
if (is_null($post)) return;

// Fetch both meta values in one call (WP caches all post meta together after the first get_post_meta)
$dog = get_post_meta($post->ID, "disable_open_graph", TRUE);

// Bail early before any further queries if OG is disabled or this is the homepage
if ($dog === "1" || is_home()) {
    // Homepage path still needs these; non-home disabled path needs nothing more
    if (!is_home()) return;
}

$blog_name            = get_bloginfo('name');
$home_description     = get_bloginfo('description');
$homepage_object_type = get_option('homepage_object_type') ?: "website";

if (is_home()) {
    $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- homepage /-->
<meta property="og:type" content="' . $homepage_object_type . '" />
<meta property="og:url" content="' . get_option('home') . '" />
<meta property="og:title" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $home_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . get_option('default_fb_thumb') . '" />';
    return;
}

// --- Non-homepage, OG enabled ---

$ot                    = get_post_meta($post->ID, 'ftf_open_type', true);
$title                 = $post->post_title;  // free: already in memory, avoids a DB call
$post_page_object_type = $ot;

// source: https://isabelcastillo.com/get-permalink-on-custom-post-type-archive-or-taxonomy-pages
// instead of permalink:
if ( is_tax() ) {
    $permalink = get_term_link( get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
    // 2018-05-07 - gracefully handle URLs like https://www.tiperformance.com.au/brand/raceworks/?filter_angle=135deg%27A=0
    if (is_wp_error($permalink)) {
        $permalink = "";
    }
} elseif ( is_post_type_archive() ) {
    $permalink = get_post_type_archive_link( get_query_var('post_type') );
} else {
    $permalink = get_permalink();
}

if (!$post_page_object_type) {
    if (is_archive()) { //JB 2026-02 using is_archive to pick up archive (brand, category, engine) thumbnails rather than first product
        //reset things for product archive
        $post_page_object_type = "article";
        $title = (string) single_cat_title('', false);  //the_archive_title contains Category: prefix
        $post_page_description = strip_tags(term_description());
        if (strlen($post_page_description) >= 300) {
            $post_page_description = substr($post_page_description, 0, 297) . "...";
        }
        //get category thumb (source: https://docs.woocommerce.com/document/woocommerce-display-category-image-on-category-archive/ )
        //2024-04-10 fixed get_woocommerce_term_meta deprecated
        global $wp_query;
        $cat = $wp_query->get_queried_object();
        $featuredimg = ($cat instanceof WP_Term) ? wp_get_attachment_image_src( get_term_meta( $cat->term_id, 'thumbnail_id', true ), "Full") : false;
    } elseif ($post->post_type == 'product') { //JB 2017-08-23 - wooCommerce product
        $post_page_object_type = "product";
    } else {
        $post_page_object_type = "article";
    }
}

// Fetch post thumbnail only for non-archive pages (archives set $featuredimg directly above)
if (!isset($featuredimg)) {
    $featuredimg = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), "Full");
}

// 2025-11-17 handle pages with no excerpt set; skipped for archives which set $post_page_description above
if (!isset($post_page_description)) {
    $post_page_description = get_the_excerpt($post);
    if (!$post_page_description) {
        $content = strip_tags(get_the_content());
        $content = preg_replace('/\s+/', ' ', trim(str_replace(array("\n", "\t", "\r"), ' ', preg_replace("~\[[^\]]+\]~s", '', $content))));
        $content = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $content);
        $post_page_description = substr($content, 0, 300);
        if (strlen($post_page_description) >= 300) {
            $post_page_description = substr($post_page_description, 0, 297) . "...";
        }
    }
}

// If there is a post image...
if ((has_post_thumbnail() || isset($featuredimg[0])) && !is_bool($featuredimg)) {
    $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- has thumbnail/-->
<meta property="og:type" content="' . $post_page_object_type . '" />
<meta property="og:url" content="' . $permalink . '" />
<meta property="og:title" content="' . str_replace('"', '', $title) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . $featuredimg[0] . '" />
';

    if (!is_archive() && $post->post_type == 'product') { //JB 2017-08-23 - wooCommerce product
        //JB 2016-07-20 - add gallery images
        $product        = wc_get_product( $post->ID );
        $attachment_ids = $product->get_gallery_image_ids();
        foreach ($attachment_ids as $attachment_id) {
            $ftf_head .= '<meta property="og:image" content="' . wp_get_attachment_url($attachment_id) . '" />
';
        }
        // 2018-04-04 - structured data tool throws error
        // <meta itemscope itemtype="'. $post_page_object_type . '" />
        $ftf_head .= '<meta itemprop="description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta itemprop="image" content="' . $featuredimg[0] . '" />';
    }
} else { //...otherwise, if there is no post image.
    $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- no post image /-->
<meta property="og:type" content="' . $post_page_object_type . '" />
<meta property="og:url" content="' . $permalink . '" />
<meta property="og:title" content="' . str_replace('"', '', $title) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . get_option('default_fb_thumb') . '" />';
}


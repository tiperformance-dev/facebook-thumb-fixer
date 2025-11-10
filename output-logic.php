<?php
global $post;
if (is_null($post)) return;
$featuredimg 			= wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), "Full"); 
//print_r(wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), "Full"));
//print_r($featuredimg);

$ftf_description		= get_the_excerpt($post);
$ot 					= get_post_meta($post->ID, 'ftf_open_type', true);
$title 					= get_the_title($post->ID);
$blog_name 				= get_bloginfo('name');
$post_page_description 	= wp_kses($ftf_description, array ());
$home_description 		= get_bloginfo('description');
$homepage_object_type	= get_option( 'homepage_object_type');
$post_page_object_type  = $ot;
$dog                    = get_post_meta($post->ID, "disable_open_graph", TRUE);
// source: https://isabelcastillo.com/get-permalink-on-custom-post-type-archive-or-taxonomy-pages
// instead of permalink:
if ( is_tax() ) { 
    $permalink = get_term_link( get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
    // 2018-05-07 - graccefully handle URLs like https://www.tiperformance.com.au/brand/raceworks/?filter_angle=135deg%27A=0
    if (is_wp_error($permalink)) {
	$permalink = "";
    }
//echo get_query_var( 'term' ).'foo'.get_query_var( 'taxonomy' );
}
elseif( is_post_type_archive() ) {
    $permalink = get_post_type_archive_link( get_query_var('post_type') );
}
else {
    $permalink = get_permalink();
}


if($post_page_description) {
//if(1==1) {
    $post_page_description = $post_page_description;
} else {
    // 2025-11-06
    // don't we already have a $post global?
    // $post = get_post($post->ID);
    $content = apply_filters('get_the_content', $post->post_content);
    $content_chars = substr($content, 0, 300);
    $page_description = strip_tags($content_chars);
    $post_page_description = str_replace(array("\n", "\t", "\r"), '', $page_description);
}

if($homepage_object_type) { 
    //$homepage_object_type = get_option( 'homepage_object_type');
} else {
    $homepage_object_type = "website";
}

if($post_page_object_type) { 
    // $post_page_object_type = $ot;
} elseif (is_product_category() ) { //JB 2017-08-23 - wooCommerce product archive 2021-10-28 ammended as this was picking up brand archives which do not have an image set and therefore returning no image. 
	
    //reset things for product archive
    $post_page_object_type = "article";
    $title = single_cat_title('',false);  //the_archive_title contains Category: prefix
    $post_page_description = strip_tags(term_description());
    if (strlen($post_page_description) > 300) { 
    	$post_page_description = substr($post_page_description,0,297)."...";
    }
    //get category thumb (source: https://docs.woocommerce.com/document/woocommerce-display-category-image-on-category-archive/ )
	//2024-04-10 fixed get_woocommerce_term_meta deprecated
    global $wp_query;
    $cat = $wp_query->get_queried_object();
    $featuredimg = wp_get_attachment_image_src( get_term_meta( $cat->term_id, 'thumbnail_id', true ), "Full");
} elseif ($post->post_type == 'product') { //JB 2017-08-23 - wooCommerce product
    $post_page_object_type = "product";
} else {
    $post_page_object_type = "article";
}

// If not the homepage
if ( !is_home() ) {

    // If not disabled
    if($dog !== "1") {

        // If there is a post image...
        if ((has_post_thumbnail() || isset($featuredimg[0]))
                 && !is_bool($featuredimg)) {
            $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- has thumbnail/-->
<meta property="og:type" content="'. $post_page_object_type . '" />
<meta property="og:url" content="' . $permalink . '" />
<meta property="og:title" content="' . str_replace('"', '', $title) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . $featuredimg[0] .'" />
';
            
            if (!is_archive() && $post->post_type == 'product') { //JB 2017-08-23 - wooCommerce product
            	$ftf_head .= '';
                $product = wc_get_product( get_the_ID() );
                //2017-08-23 - commented out due to some issue with price mismatch in merchant?
                //$ftf_head .= '<meta property="product:price:amount" content="' . $product->price . '" />';
                //$ftf_head .= '<meta property="product:price:currency" content="' . get_option('woocommerce_currency') . '" />';
                
                //JB 2016-07-20 - add gallery images
                $attachment_ids = $product->get_gallery_image_ids();
                foreach( $attachment_ids as $attachment_id ) {
                	$ftf_head .= '<meta property="og:image" content="' .wp_get_attachment_url( $attachment_id ). '" />
';
                }
            // 2018-04-04 - structured data tool throws error
            // <meta itemscope itemtype="'. $post_page_object_type . '" />
            $ftf_head .= '<meta itemprop="description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta itemprop="image" content="' . $featuredimg[0] . '" />';

            }            
        } else { //...otherwise, if there is no post image.
            $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- no post image /-->
<meta property="og:type" content="'. $post_page_object_type . '" />
<meta property="og:url" content="' . $permalink . '" />
<meta property="og:title" content="' . str_replace('"', '', $title) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . get_option('default_fb_thumb') . '" />';
//<meta itemscope itemtype="'. $post_page_object_type . '" />
//<meta itemprop="description" content="' . strip_shortcodes(str_replace('"', '', $post_page_description)) . '" />
//<meta itemprop="image" content="' . get_option('default_fb_thumb') . 
//'" />';
        }

    } // End if not disabled

} else { //...otherwise, it must be the homepage so do this:

    $ftf_head = '<!--/ Facebook Thumb Fixer Open Graph -- homepage /-->
<meta property="og:type" content="' . $homepage_object_type . '" />
<meta property="og:url" content="' . get_option('home') . '" />
<meta property="og:title" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:description" content="' . strip_shortcodes(str_replace('"', '', $home_description)) . '" />
<meta property="og:site_name" content="' . str_replace('"', '', $blog_name) . '" />
<meta property="og:image" content="' . get_option('default_fb_thumb') . '" />';
// <meta itemscope itemtype="'. $homepage_object_type . '" />
// <meta itemprop="description" content="' . strip_shortcodes(str_replace('"', '', $home_description)) . '" />
// <meta itemprop="image" content="' . get_option('default_fb_thumb') . '" />';
}


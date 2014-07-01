<?php
/**
*Plugin Name: WooCommerce Product Slider
*Description: This is JQuery CarouFredSel library based woocommerce product slider.
*Author: subhansanjaya
*Version: 1.0
*Plugin URI: http://wordpress.org/plugins/woocommerce-product-slider/
*Author URI: http://www.weaveapps.com.com
*Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BXBCGCKDD74UE
*License: GPLv2 or later
*License URI: http://www.gnu.org/licenses/gpl-2.0.html
*
* @package WooCommerce-Product-Slider
* @author subhansanjaya
*/

add_shortcode( 'wa-wps', 'wa_wps' );
add_action('init', 'wa_wps_inc_scripts');
register_activation_hook(__FILE__, 'wa_wps_install');

//Page function
function wa_wps() {

	global $wpdb,$post;

	$wa_wps_display_image = get_option('wa_wps_display_image'); 
	$wa_wps_display_price = get_option('wa_wps_display_price');
	$wa_wps_display_add_to_cart = get_option('wa_wps_display_add_to_cart');
	$wa_wps_display_title = get_option('wa_wps_display_title');
	$wa_wps_display_excerpt = get_option('wa_wps_display_excerpt');
	$wa_wps_display_read_more_text = get_option('wa_wps_display_read_more_text');
	$wa_wps_word_limit = get_option('wa_wps_word_limit');
	$wa_wps_query_posts_showposts = get_option('wa_wps_query_posts_showposts');
	$wa_wps_query_posts_orderby= get_option('wa_wps_query_posts_orderby');
	$wa_wps_query_posts_order= get_option('wa_wps_query_posts_order');
	$wa_wps_query_posts_category= get_option('wa_wps_query_posts_category');
	$wa_wps_terms=explode(',', $wa_wps_query_posts_category);
	$wa_wps_posts_image_width = get_option('wa_wps_posts_image_width');
	$wa_wps_posts_image_height = get_option('wa_wps_posts_image_height');
	$wa_wps_display_page = get_option('wa_wps_display_page');
	$wa_wps_display_nav = get_option('wa_wps_display_nav');

	//Display slider
	$slider_gallery = "";
	$slider_gallery.= '<div class="wa_wps_image_carousel">';
	$slider_gallery.='<div id="wa_wps_foo1">';

	$args_custom = array(
	 	'posts_per_page' => $wa_wps_query_posts_showposts,
	 	'order'=> $wa_wps_query_posts_order,
	 	'orderby' => $wa_wps_query_posts_orderby,
        'post_type' => explode(',', 'product'),
        'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $wa_wps_terms
                    )
                )
        );

	$get_products = get_posts($args_custom);

	foreach($get_products as $post ){

		$post_title = $post->post_title;
		$post_link =  get_permalink($post->ID);
		$post_content = strip_shortcodes($post->post_excerpt);
		$post_id=	$post->ID; 

		//woocommerce get data
		if ( function_exists( 'get_product' ) ) {
		$_product = get_product( $post->ID );
		} else {
		$_product = new WC_Product( $post->ID );
		}

		$slider_gallery.= '<div id="wa_wps_foo_content">';

		if($wa_wps_display_image){
			if (has_post_thumbnail( $post->ID ) ): 
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' ); 
			endif; 
			
			//featured image
			if(empty($image[0])) {
    		$image = plugins_url()."/woocommerce-product-slider/images/default-image.jpg";
    					$featured_img = "<img  src='". $image . "' width='". $wa_wps_posts_image_width . "'  height='". $wa_wps_posts_image_height . "' />";
  			}else{
						$featured_img = "<img src='". $image[0] . "' width='". $wa_wps_posts_image_width . "'  height='". $wa_wps_posts_image_height . "' />";

  			}
			$slider_gallery.= '<div><a href="'.$post_link.'">'.$featured_img.'</a></div>';
		}
		
		//display title
		if($wa_wps_display_title){
		$slider_gallery.= '<div class="wa_wps_title"><a  style=" text-decoration:none;" href="'.$post_link.'">'.$post_title.'</a></div>'; }

		//display excerpt
		if($wa_wps_display_excerpt){
		$slider_gallery.= '<div class="wa_wps_foo_con"><p>'.wa_wps_clean($post_content, $wa_wps_word_limit).'</p></div>'; }

		//display read more text
		if($wa_wps_display_read_more_text){
		$slider_gallery.= '<div class="wa_wps_more"><a href="'.$post_link.'">read more</a></div>'; }

		//display price
		if($wa_wps_display_price){
		$slider_gallery.= '<div class="wa_wps_price">'.$_product->get_price_html().'</div>';
		}

		//display add to cart
		if($wa_wps_display_add_to_cart){
		$slider_gallery.= '<div class="wa_wps_add_to_cart"><a  rel="nofollow" data-product_id="'.$post_id.'" data-product_sku="'.$_product->get_sku().'" class="wa_wps_button add_to_cart_button product_type_simple" href="'.do_shortcode('[add_to_cart_url id="'.$post_id.'"]').'">Add to cart</a></div>';
		}

		$slider_gallery.= '</div>';
	}

	$slider_gallery.='</div>';
	$slider_gallery.='<div class="wa_wps_clearfix"></div>';

	if($wa_wps_display_nav){
	$slider_gallery.='<a class="wa_wps_prev" href="#"><span>prev</span></a>';
	$slider_gallery.='<a class="wa_wps_next" href="#"><span>next</span></a>';
	}
	if($wa_wps_display_page){
	$slider_gallery.='<div class="wa_wps_pagination"></div>';
	}
	$slider_gallery.='</div>';
	return $slider_gallery;
}

//include css and js files
function wa_wps_inc_scripts()
{
	if (!is_admin())
	{
		wp_register_style('wa_wps_css_file', plugins_url('/assets/css/wa-wps-custom-style.css',__FILE__ ));
		wp_enqueue_style('wa_wps_css_file');

		wp_enqueue_script('jquery');

		wp_register_script( 'wa_wps_caroufredsel_js', plugins_url('/assets/inc/jquery.carouFredSel-6.2.1-packed.js',__FILE__ ));
		wp_enqueue_script('wa_wps_caroufredsel_js');

		wp_register_script( 'wa_wps_custom_js', plugins_url('/assets/inc/custom.js',__FILE__ ));
		wp_enqueue_script('wa_wps_custom_js');

	}
}


// limit words (remove images, html tags and retrieve text only)
function wa_wps_clean($excerpt, $substr) {

	$string = $excerpt;
	$string = strip_shortcodes(wp_trim_words( $string, $substr ));

	return $string;
}

function wa_wps_admin_options()
{
	include_once("slider-management.php");
}

function wa_wps_add_to_menu()
{
	add_options_page('WooCommerce Product Slider', 'WooCommerce Product Slider', 'manage_options', __FILE__, 'wa_wps_admin_options' );
}

if (is_admin())
{
	add_action('admin_menu', 'wa_wps_add_to_menu');
}

//installation default value
function wa_wps_install()
{
	add_option('wa_wps_display_image', 1);
	add_option('wa_wps_display_price', 1);
	add_option('wa_wps_display_add_to_cart', 1);
	add_option('wa_wps_display_title', "true");
	add_option('wa_wps_display_excerpt', 0);
	add_option('wa_wps_display_read_more_text', 0);
	add_option('wa_wps_posts_image_width', 150);
	add_option('wa_wps_posts_image_height', 150);
	add_option('wa_wps_display_page', 1);
	add_option('wa_wps_display_nav', 1);
	add_option('wa_wps_word_limit', 20);
	add_option('wa_wps_query_posts_showposts', 5);
	add_option('wa_wps_query_posts_orderby', "id");
	add_option('wa_wps_query_posts_order', "desc");
	add_option('wa_wps_query_posts_category', "clothing");
}
?>
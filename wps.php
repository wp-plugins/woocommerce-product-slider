<?php
/*
Plugin Name: WooCommerce Product Slider
Description: A simple WooCommerce product slider plugin.
Author: subhansanjaya
Version: 1.2.1
Plugin URI: http://wordpress.org/plugins/woocommerce-product-slider/
Author URI: http://www.weaveapps.com.com
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BXBCGCKDD74UE
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if(! defined( 'ABSPATH' )) exit; // Exit if accessed directly

class WooCommerce_Product_Slider {

	//default settings
	private $defaults = array(
	'settings' => array(
		'display_image' => true,
		'word_limit' => '8',
		'read_more_text' => 'Read more',
		'number_of_posts_to_display' => '10',
		'posts_order' => 'desc',
		'posts_orderby' => 'id',
		'category' => '',
		'display_pagination' => false,
		'display_excerpt' => true,
		'display_title' => true,
		'display_read_more' => true,
		'display_controls' => true,
		'auto_scroll' => false,
		'circular' => false,
		'fx' => 'scroll',
		'deactivation_delete' => false,
		'loading_place' => 'header',
		'easing_effect' => 'linear',
		'item_width' => '180',
		'item_height' => '350',
		'infinite' => true,
		'item_align' => 'center',
		'excerpt_type' => false,
		'touch_swipe' => false,
		'arrows_colour' => 'rgb(0, 0, 0)',
		'arrows_bg_colour' => 'rgba(168, 32, 32, 0)',
		'arrows_hover_colour' => 'rgb(170, 170, 170)',
		'size_of_direction_arrows' => '32',
		'css_transition' => false,
		'timeout' => '3000',
		'direction' => 'left',
		'custom_css' => '',
		'default_image_url' => '',
		'image_size' => 'thumbnail',
		'display_price' => true,
		'display_add_to_cart' => true
	),
	'version' => '1.2',
	'configuration' => array(
		'deactivation_delete' => false,
		'loading_place' => 'header',
		'load_touch_swipe' => true,
		'load_jquery' => false,
		'load_transit' => true,
		'load_caroufredsel' => true,
	)
);
	private $options = array();
	private $tabs = array();

	public function __construct() {

		register_activation_hook(__FILE__, array(&$this, 'wa_wps_activation'));
		register_deactivation_hook(__FILE__, array(&$this, 'wa_wps_deactivation'));

		//create widget
		require('classes/class-wps-widget.php');
		$CHPCS_Widget = new WPS_widget();

		//Add admin option
		add_action('admin_menu', array(&$this, 'admin_menu_options'));
		add_action('admin_init', array(&$this, 'register_settings'));

		//add text domain for localization
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));

		//load defaults
		add_action('plugins_loaded', array(&$this, 'load_defaults'));

		//update plugin version
		update_option('wa_wps_version', $this->defaults['version'], '', 'no');
		$this->options['settings'] = array_merge($this->defaults['settings'], (($array = get_option('wa_wps_settings')) === FALSE ? array() : $array));
		$this->options['configuration'] = array_merge($this->defaults['configuration'], (($array = get_option('wa_wps_configuration')) === FALSE ? array() : $array));
	
		//insert js and css files
		add_action('wp_enqueue_scripts', array(&$this, 'include_scripts'));

		add_action('admin_enqueue_scripts', array(&$this, 'admin_include_scripts'));

		//settings link
		add_filter('plugin_action_links', array(&$this, 'wa_wps_settings_link'), 2, 2);

		//add shortcode
		add_shortcode( 'wa-wps', array(&$this, 'wa_wps_display_slider'));
	}

	/* multi site activation hook */
	public function wa_wps_activation($networkwide) {

		if(is_multisite() && $networkwide) {
			global $wpdb;

			$activated_blogs = array();
			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col($wpdb->prepare('SELECT blog_id FROM '.$wpdb->blogs, ''));

			foreach($blogs_ids as $blog_id)
			{
				switch_to_blog($blog_id);
				$this->activate_single();
				$activated_blogs[] = (int)$blog_id;
			}

			switch_to_blog($current_blog_id);
			update_site_option('wa_wps_activated_blogs', $activated_blogs, array());
		}
		else
			$this->activate_single();
	}

	public function activate_single() {

		add_option('wa_wps_settings', $this->defaults['settings'], '', 'no');
		add_option('wa_wps_version', $this->defaults['version'], '', 'no');
		add_option('wa_wps_configuration', $this->defaults['configuration'], '', 'no');

	}

	/*  multi-site deactivation hook */
	public function wa_wps_deactivation($networkwide) {

		if(is_multisite() && $networkwide) {
			global $wpdb;

			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col($wpdb->prepare('SELECT blog_id FROM '.$wpdb->blogs, ''));

			if(($activated_blogs = get_site_option('wa_wps_activated_blogs', FALSE, FALSE)) === FALSE)
				$activated_blogs = array();

			foreach($blogs_ids as $blog_id)
			{
				switch_to_blog($blog_id);
				$this->deactivate_single(TRUE);

				if(in_array((int)$blog_id, $activated_blogs, TRUE))
					unset($activated_blogs[array_search($blog_id, $activated_blogs)]);
			}

			switch_to_blog($current_blog_id);
			update_site_option('wa_wps_activated_blogs', $activated_blogs);
		}
		else
			$this->deactivate_single();
	}

	public function deactivate_single($multi = FALSE) {

		if($multi === TRUE) {
			$options = get_option('wa_wps_settings');
			$check = $options['deactivation_delete'];
		}
		else {
		$check = $this->options['settings']['deactivation_delete'];
		
		if($check === TRUE) {
			delete_option('wa_wps_settings');
			delete_option('wa_wps_version');
			delete_option('wa_wps_configuration');
			}

		}
	}

	/* settings link in management screen */
	public function wa_wps_settings_link($actions, $file) {
		if(false !== strpos($file, 'woocommerce-product-slider'))
		 $actions['settings'] = '<a href="options-general.php?page=woocommerce-product-slider">Settings</a>';
		return $actions; 
	}

	/* display slider */
	public function wa_wps_display_slider() {

	
	global $wpdb, $post;

	$display_image = $this->options['settings']['display_image'];

	$word_limit = $this->options['settings']['word_limit'];

	$number_of_posts_to_display = $this->options['settings']['number_of_posts_to_display'];

	$posts_order= $this->options['settings']['posts_order'];

	$posts_orderby= $this->options['settings']['posts_orderby'];

	$category= $this->options['settings']['category'];

	$display_pagination= $this->options['settings']['display_pagination'];

	$display_excerpt= $this->options['settings']['display_excerpt'];

	$display_title= $this->options['settings']['display_title'];

	$display_read_more= $this->options['settings']['display_read_more'];

	$read_more_text= $this->options['settings']['read_more_text'];

	$display_controls= $this->options['settings']['display_controls'];

	$auto_scroll= $this->options['settings']['auto_scroll'];

	$fx= $this->options['settings']['fx'];

	$item_width= $this->options['settings']['item_width'];

	$item_height= $this->options['settings']['item_height'];

	$align_items= $this->options['settings']['item_align'];

	$infinite= $this->options['settings']['infinite'];

	$excerpt_type= $this->options['settings']['excerpt_type'];

	$touch_swipe= $this->options['settings']['touch_swipe'];

	$arrows_colour= $this->options['settings']['arrows_colour'];

	$arrows_bg_colour= $this->options['settings']['arrows_bg_colour'];

	$arrows_hover_colour= $this->options['settings']['arrows_hover_colour'];

	$size_of_direction_arrows= $this->options['settings']['size_of_direction_arrows'];

	$css_transition= $this->options['settings']['css_transition'];

	$timeout= $this->options['settings']['timeout'];

	$direction= $this->options['settings']['direction'];

	$custom_css= $this->options['settings']['custom_css'];

	$image_size= $this->options['settings']['image_size'];

	$display_price= $this->options['settings']['display_price'];

	$display_add_to_cart= $this->options['settings']['display_add_to_cart'];

	?>
	<style>

	<?php if(!empty($custom_css)) { echo $custom_css;  } ?>

	.wps_foo_content img {

		max-width: <?php echo $item_width;?>;
	}

			.wps_image_carousel .wps_prev, .wps_image_carousel .wps_next{

			background: <?php echo $arrows_bg_colour; ?>;

			color: <?php echo $arrows_colour; ?>;

			font-size: <?php echo $size_of_direction_arrows; ?>px;

			line-height: <?php echo $size_of_direction_arrows+7;?>px;

			width: <?php echo $size_of_direction_arrows+10;?>px;

			height: <?php echo $size_of_direction_arrows+10;?>px;

			margin-top: -<?php echo $size_of_direction_arrows; ?>px;

		}

		.wps_image_carousel.wps_prev:hover, .wps_image_carousel .wps_next:hover {

			color: <?php echo $arrows_hover_colour;?>;

		}

		#wa_wps_pager a {

			background: <?php echo $arrows_hover_colour; ?>;

		}

	</style>

	<?php
	$slider_gallery = '';

	$slider_gallery.= '<div class="wps_image_carousel">';

	$slider_gallery.='<div id="wa_chpc_slider" style="height:'.$item_height.'px; overflow: hidden;">';

	if($posts_order=="rand") {  

		$posts_orderby="rand"; 

	}

	$args_custom = array(
		 	'posts_per_page' => $number_of_posts_to_display,
		    'post_type' => 'product',
		    'order'=> $posts_order, 
		    'orderby' => $posts_orderby,
		    'post_status'  => 'publish',
		    'tax_query' => array(
		                array(
		                    'taxonomy' => 'product_cat',
		                    'field' => 'slug',
		                    'terms' => $category
		                )
		            )
		    );

	$myposts = get_posts( $args_custom );


	foreach( $myposts as $post ){

		$post_title = $post->post_title;

		$post_link =  get_permalink($post->ID);

		$post_content = strip_shortcodes($post->post_content);

		$text_type = $this->get_text_type($post, $excerpt_type);

		//woocommerce get data
		if ( function_exists( 'get_product' ) ) {
		$_product = get_product( $post->ID );
		} else {
		$_product = new WC_Product( $post->ID );
		}
		
		$slider_gallery.= '<div class="wps_foo_content" style="width:'.$item_width.'px; height:'.$item_height.'px;">';

		if($display_image){
		
			$slider_gallery.= '<span class="wps_img"><a href="'.$post_link.'">'.$this->get_post_image($post->ID,$image_size).'</a></span>';
		}
		
		//Post title, Post Description, Post read more
		if($display_title){
		$slider_gallery.= '<br/><span class="wps_title"><a  style=" text-decoration:none;" href="'.$post_link.'">'.$post_title.'</a></span><br/>';
		}

		if($display_excerpt){
		$slider_gallery.= '<p><span class="wps_foo_con">'.$this->wa_wps_clean($text_type, $word_limit).'</span></p>';
		}

		if($display_read_more){
		$slider_gallery.= '<br/><span class="wps_more"><a href="'.$post_link.'">'.$read_more_text.'</a></span>';
		}

		//display price
		if($display_price){
		$slider_gallery.= '<div class="wa_wps_price">'.$_product->get_price_html().'</div>';
		}

		//display add to cart
		if($display_add_to_cart){
		$slider_gallery.= '<div class="wa_wps_add_to_cart"><a  rel="nofollow" data-product_id="'.$post->ID.'" data-product_sku="'.$_product->get_sku().'" class="wa_wps_button add_to_cart_button product_type_simple" href="'.do_shortcode('[add_to_cart_url id="'.$post->ID.'"]').'">Add to cart</a></div>';
		}

		$slider_gallery.= '</div>';
	}

	$slider_gallery.='</div>';

	$slider_gallery.='<div class="wps_clearfix"></div>';

	if($display_controls) {

		$slider_gallery.='<a class="wps_prev" id="wa_chpc_slider_prev" href="#"><span>‹</span></a>';

		$slider_gallery.='<a class="wps_next" id="wa_chpc_slider_next" href="#"><span>›</span></a>';	

	}

	if($display_pagination) {

	$slider_gallery.='<div class="wps_pagination" id="wa_wps_pager"></div>'; 

	}
	$slider_gallery.='</div>';

	wp_reset_postdata();

	return $slider_gallery;

	}


	//get text type to display
	public function get_text_type($wa_post, $wa_wps_query_display_from_excerpt) {

		$text_type = '';

		if($wa_wps_query_display_from_excerpt==1) {

			$text_type = $wa_post->post_excerpt;
		} else {

			$text_type = $wa_post->post_content;

		}

		return $text_type;

	}


	/* Get image url */
	public	function get_post_image($post_image_id, $img_size) {
  
  			if (has_post_thumbnail( $post_image_id ) ): 
			 $img_arr = wp_get_attachment_image_src( get_post_thumbnail_id( $post_image_id ), $img_size ); $first_img = $img_arr[0];
			endif; 
	
	 		if(empty($first_img)) {


	  		if(empty($this->options['settings']['default_image_url'])) {

				 $first_img = plugins_url('assets/images/default-image.jpg', __FILE__);

			} else {

				$first_img = $this->options['settings']['default_image_url'];

			}
	  }
	  	$first_img = "<img src='". $first_img. "'/>";
	  	return $first_img;
	}


	/* insert css files js files */
	public function include_scripts() {	

	$args = apply_filters('wps_args', array(
		'auto_scroll' => $this->options['settings']['auto_scroll']? 'true' : 'false',
		'circular' => $this->options['settings']['circular']? 'true' : 'false',
		'easing_effect' => $this->options['settings']['easing_effect'],
		'item_align' => $this->options['settings']['item_align'],
		'direction' => $this->options['settings']['direction'],
		'touch_swipe' =>$this->options['settings']['touch_swipe']? 'true' : 'false',
		'item_width' => $this->options['settings']['item_width'],
		'time_out' => $this->options['settings']['timeout'],
		'css_transition' => $this->options['settings']['css_transition']? 'true' : 'false',
		'infinite' =>$this->options['settings']['infinite']? 'true' : 'false',
		'fx' => $this->options['settings']['fx']));

		wp_register_style('wa_wps_style',plugins_url('assets/css/custom-style.css', __FILE__));
		wp_enqueue_style('wa_wps_style');

		if($this->options['configuration']['load_jquery'] === TRUE) {

			wp_register_script('wa_wps_jquery',plugins_url('/assets/js/caroufredsel/jquery-1.8.2.min.js', __FILE__),array('jquery'),'',($this->options['settings']['loading_place'] === 'header' ? false : true));
		    wp_enqueue_script('wa_wps_jquery'); 

		}

		if($this->options['configuration']['load_transit'] === TRUE) {

			wp_register_script('wa_wps_transit',plugins_url('/assets/js/caroufredsel/jquery.transit.min.js',__FILE__),array('jquery'),'',($this->options['settings']['loading_place'] === 'header' ? false : true));
			wp_enqueue_script('wa_wps_transit');

		 }


		if($this->options['configuration']['load_caroufredsel'] === TRUE) {

		    wp_register_script('wa_wps_caroufredsel_script',plugins_url('/assets/js/caroufredsel/jquery.carouFredSel-6.2.1-packed.js', __FILE__),array('jquery'),'',($this->options['settings']['loading_place'] === 'header' ? false : true));
		    wp_enqueue_script('wa_wps_caroufredsel_script');

		}

		if($this->options['configuration']['load_touch_swipe'] === TRUE) {

		    wp_register_script('wa_wps_touch_script',plugins_url('/assets/js/caroufredsel/jquery.touchSwipe.min.js', __FILE__),array('jquery'),'',($this->options['settings']['loading_place'] === 'header' ? false : true));
		    wp_enqueue_script('wa_wps_touch_script'); 

		}

		wp_register_script('wa_wps_custom',plugins_url('assets/js/script.js', __FILE__),array('jquery'),'',($this->options['settings']['loading_place'] === 'header' ? false : true));
		wp_enqueue_script('wa_wps_custom');

		wp_localize_script('wa_wps_custom','wpsArgs',$args);

	}


	/* limit words (remove images, html tags and retrieve text only) */
	public function wa_wps_clean($excerpt, $substr) {

		$string = $excerpt;
		$string = strip_shortcodes(wp_trim_words( $string, (int)$substr ));
		return $string;

	}

	/* insert css files for admin area */
	public function admin_include_scripts() {

			wp_register_style('wa_wps_admin',plugins_url('assets/css/admin.css', __FILE__ ));
			wp_enqueue_style('wa_wps_admin');

			//add spectrum colour picker
			wp_register_style('wa-wps-admin-spectrum',plugins_url('assets/css/spectrum/spectrum.css', __FILE__));
			wp_enqueue_style('wa-wps-admin-spectrum');

			wp_register_script('wa-wps-admin-spectrum-js',plugins_url('assets/js/spectrum/spectrum.js', __FILE__));
			wp_enqueue_script('wa-wps-admin-spectrum-js');

			wp_register_script('wa-wps-admin-script',plugins_url('assets/js/admin-script.js', __FILE__));
			wp_enqueue_script('wa-wps-admin-script');
	}

	/* admin menu */
	public function admin_menu_options(){
		add_options_page(
			__('WooCommerce product slider', 'woocommerce-product-slider'),
			__('WooCommerce product slider', 'woocommerce-product-slider'),
			'manage_options',
			'woocommerce-product-slider',
			array(&$this, 'options_page')
		);
	}

	/* register setting for plugins page */
	public function register_settings() {
		
		register_setting('wa_wps_settings', 'wa_wps_settings', array(&$this, 'validate_options'));
		//general settings
		add_settings_section('wa_wps_settings', __('', 'wa-wps-txt'), '', 'wa_wps_settings');

		add_settings_field('wa_wps_display_image', __('Show featured image', 'wa-wps-txt'), array(&$this, 'wa_wps_display_image'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_controls', __('Show direction arrows', 'wa-wps-txt'), array(&$this, 'wa_wps_display_controls'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_read_more', __('Show read more text', 'wa-wps-txt'), array(&$this, 'wa_wps_display_read_more'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_title', __('Show title', 'wa-wps-txt'), array(&$this, 'wa_wps_display_title'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_excerpt', __('Show excerpt', 'wa-wps-txt'), array(&$this, 'wa_wps_display_excerpt'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_price', __('Show price', 'wa-wps-txt'), array(&$this, 'wa_wps_display_price'), 'wa_wps_settings', 'wa_wps_settings');
	
		add_settings_field('wa_wps_display_add_to_cart', __('Show add to cart button', 'wa-wps-txt'), array(&$this, 'wa_wps_display_add_to_cart'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_display_pagination', __('Show pagination', 'wa-wps-txt'), array(&$this, 'wa_wps_display_pagination'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_image_size', __('Featured image size', 'wa-wps-txt'), array(&$this, 'wa_wps_image_size'), 'wa_wps_settings', 'wa_wps_settings');
	
		add_settings_field('wa_wps_auto_scroll', __('Auto scroll', 'wa-wps-txt'), array(&$this, 'wa_wps_auto_scroll'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_circular', __('Circular', 'wa-wps-txt'), array(&$this, 'wa_wps_circular'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_word_limit', __('Excerpt length', 'wa-wps-txt'), array(&$this, 'wa_wps_word_limit'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_read_more_text', __('Read more text', 'wa-wps-txt'), array(&$this, 'wa_wps_read_more_text'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_posts_category', __('Categories/Terms', 'wa-wps-txt'), array(&$this, 'wa_wps_posts_category'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_number_of_posts_to_display', __('Number of posts', 'wa-wps-txt'), array(&$this, 'wa_wps_number_of_posts_to_display'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_posts_order', __('Posts order', 'wa-wps-txt'), array(&$this, 'wa_wps_posts_order'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_posts_orderby', __('Posts orderby', 'wa-wps-txt'), array(&$this, 'wa_wps_posts_orderby'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_fx', __('Transition effect', 'wa-wps-txt'), array(&$this, 'wa_wps_fx'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_easing_effect', __('Easing effect', 'wa-wps-txt'), array(&$this, 'wa_wps_easing_effect'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_timeout', __('Timeout between elements', 'wa-wps-txt'), array(&$this, 'wa_wps_timeout'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_infinite', __('Infinite', 'wa-wps-txt'), array(&$this, 'wa_wps_infinite'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_item_align', __('Align the items in Slider', 'wa-wps-txt'), array(&$this, 'wa_wps_item_align'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_item_width', __('General width of items', 'wa-wps-txt'), array(&$this, 'wa_wps_item_width'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_item_height', __('General height of items', 'wa-wps-txt'), array(&$this, 'wa_wps_item_height'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_excerpt_type', __('Pick text in excerpt field', 'wa-wps-txt'), array(&$this, 'wa_wps_excerpt_type'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_touch_swipe', __('Touch Swipe', 'wa-wps-txt'), array(&$this, 'wa_wps_touch_swipe'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_css_transtition', __('CSS3 Transtitions', 'wa-wps-txt'), array(&$this, 'wa_wps_css_transtition'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_direction', __('Direction to scroll the carousel', 'wa-wps-txt'), array(&$this, 'wa_wps_direction'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_direction_arrows_colour', __('Direction arrows colour', 'wa-wps-txt'), array(&$this, 'wa_wps_direction_arrows_colour'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_direction_arrows_bg_colour', __('Direction arrows background colour', 'wa-wps-txt'), array(&$this, 'wa_wps_direction_arrows_bg_colour'), 'wa_wps_settings', 'wa_wps_settings');
		
		add_settings_field('wa_wps_direction_arrows_hover_colour', __('Direction arrows hover colour', 'wa-wps-txt'), array(&$this, 'wa_wps_direction_arrows_hover_colour'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_size_of_direction_arrows', __('Size of direction arrows', 'wa-wps-txt'), array(&$this, 'wa_wps_size_of_direction_arrows'), 'wa_wps_settings', 'wa_wps_settings');
	
		add_settings_field('wa_wps_default_image_url', __('Default image URL', 'wa-wps-txt'), array(&$this, 'wa_wps_default_image_url'), 'wa_wps_settings', 'wa_wps_settings');

		add_settings_field('wa_wps_custom_css', __('Custom styles', 'wa-wps-txt'), array(&$this, 'wa_wps_custom_css'), 'wa_wps_settings', 'wa_wps_settings');
	

		//advance settings
		register_setting('wa_wps_configuration', 'wa_wps_configuration', array(&$this, 'validate_options'));
		
		add_settings_section('wa_wps_configuration', __('', 'wa-wps-txt'), '', 'wa_wps_configuration');

		add_settings_field('wa_wps_load_jquery', __('Load jQuery', 'wa-wps-txt'), array(&$this, 'wa_wps_load_jquery'), 'wa_wps_configuration', 'wa_wps_configuration');
		
		add_settings_field('wa_wps_load_transit', __('Load transit', 'wa-wps-txt'), array(&$this, 'wa_wps_load_transit'), 'wa_wps_configuration', 'wa_wps_configuration');
		
		add_settings_field('wa_wps_load_caroufredsel', __('Load caroufredsel', 'wa-wps-txt'), array(&$this, 'wa_wps_load_caroufredsel'), 'wa_wps_configuration', 'wa_wps_configuration');
		
		add_settings_field('wa_wps_load_touch_swipe', __('Load TouchSwipe', 'wa-wps-txt'), array(&$this, 'wa_wps_load_touch_swipe'), 'wa_wps_configuration', 'wa_wps_configuration');
	
		add_settings_field('wa_wps_loading_place', __('Loading place', 'wa-wps-txt'), array(&$this, 'wa_wps_loading_place'), 'wa_wps_configuration', 'wa_wps_configuration');
		
		add_settings_field('wa_wps_deactivation_delete', __('Deactivation', 'wa-wps-txt'), array(&$this, 'wa_wps_deactivation_delete'), 'wa_wps_configuration', 'wa_wps_configuration');
	
	}

	/* arrows colour */
	public function wa_wps_direction_arrows_colour() {

		$value = '';

		if(empty($this->options['settings']['arrows_colour'])){ $value = '#000'; }else{$value = $this->options['settings']['arrows_colour']; }

		echo '<input type="text" id="wa_wps_arrows_colour" name="wa_wps_settings[arrows_colour]" value="'.$value .'" />';

	}

	/* arrows bg colour */
	public function wa_wps_direction_arrows_bg_colour() {

		$value = '';

		if(empty($this->options['settings']['arrows_bg_colour'])){ $value = '#000'; }else{$value = $this->options['settings']['arrows_bg_colour']; }

		echo '<input type="text" id="wa_wps_arrows_bg_colour" name="wa_wps_settings[arrows_bg_colour]" value="'.$value .'" />';

	}

	/* arrows hover colour */
	public function wa_wps_direction_arrows_hover_colour() {

		$value = '';

		if(empty($this->options['settings']['arrows_hover_colour'])){ $value = '#000'; }else{$value = $this->options['settings']['arrows_hover_colour']; }

		echo '<input type="text" id="wa_wps_arrows_hover_colour" name="wa_wps_settings[arrows_hover_colour]" value="'.$value .'" />';

	}


	/* size of direction arrows */
	public function wa_wps_size_of_direction_arrows() {

		echo '<div id="wa_wps_size_of_direction_arrows">
			<input type="text"  value="'.esc_attr($this->options['settings']['size_of_direction_arrows']).'" name="wa_wps_settings[size_of_direction_arrows]" onkeypress="return event.charCode >= 48 && event.charCode <= 57" />
			<p class="description">'.__('e.g. 32', 'wa-wps-txt').'</p>
		</div>';

	}

	/* align  items */
	public function wa_wps_item_align(){

		$options = $this->options['settings']['item_align'];
	    $html = '<select id="wa_wps_item_align" name="wa_wps_settings[item_align]">';
        $html .= '<option value="center"' . selected( esc_attr($this->options['settings']['item_align']), 'center', false) . '>Center</option>';
        $html .= '<option value="left"' . selected( esc_attr($this->options['settings']['item_align']), 'left', false) . '>Left</option>';
        $html .= '<option value="right"' . selected( esc_attr($this->options['settings']['item_align']), 'right', false) . '>Right</option>';
    	$html .= '</select>';    
	    echo $html;
	} 

	/* Featured image size */
	public function wa_wps_image_size() {

		$options = $this->options['settings']['image_size'];
	    $html = '<select id="wa_wps_image_size" name="wa_wps_settings[image_size]">';
        $html .= '<option value="thumbnail"' . selected( esc_attr($this->options['settings']['image_size']), 'thumbnail', false) . '>Thumbnail</option>';
        $html .= '<option value="medium"' . selected( esc_attr($this->options['settings']['image_size']), 'medium', false) . '>Medium</option>';
        $html .= '<option value="large"' . selected( esc_attr($this->options['settings']['image_size']), 'large', false) . '>Large</option>';
         $html .= '<option value="full"' . selected( esc_attr($this->options['settings']['image_size']), 'full', false) . '>Full</option>';
    	$html .= '</select>';    
	    echo $html;
	}

	/* direction to scroll   */
	public function wa_wps_direction(){
		$options = $this->options['settings']['direction'];
	    $html = '<select id="wa_wps_direction" name="wa_wps_settings[direction]">';
        $html .= '<option value="left"' . selected( esc_attr($this->options['settings']['direction']), 'left', false) . '>Left</option>';
        $html .= '<option value="right"' . selected( esc_attr($this->options['settings']['direction']), 'right', false) . '>Right</option>';
    	$html .= '</select>';    
	    echo $html;
	} 

	/* item width */
	public function wa_wps_item_width() {

		echo '<div id="wa_wps_item_width">
			<input type="text"  value="'.esc_attr($this->options['settings']['item_width']).'" name="wa_wps_settings[item_width]" onkeypress="return event.charCode >= 48 && event.charCode <= 57" />
			<p class="description">'.__('Width of one item in the carousel(PX). e.g. 180', 'wa-wps-txt').'</p>
		</div>';

	}

	/*item height */
	public function wa_wps_item_height() {

		echo '<div id="wa_wps_item_height">
			<input type="text"  value="'.esc_attr($this->options['settings']['item_height']).'" name="wa_wps_settings[item_height]" onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
			<p class="description">'.__('Height of one item in the carousel(PX). e.g. 240', 'wa-wps-txt').'</p>
		</div>';

	}

	/* time out */
	public function wa_wps_timeout() {

		echo '<div id="wa_wps_timeout">
			<input type="text"  value="'.esc_attr($this->options['settings']['timeout']).'" name="wa_wps_settings[timeout]" onkeypress="return event.charCode >= 48 && event.charCode <= 57"/>
			<p class="description">'.__('Set the time between transitions. Only applies if Auto scroll enabled.', 'wa-wps-txt').'</p>
		</div>';

	}

	/* css 3 transitions */
	public function wa_wps_css_transtition() {

		echo '<div id="wa_wps_css_transtition" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[css_transition]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['css_transition'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('Transition effect will be used CSS3 or hardware acceleration. Uses jquery.transit plugin.', 'wa-wps-txt').'</p></div>';
	}

	/* excerpt type */
	public function wa_wps_excerpt_type() {

		echo '<div id="wa_wps_excerpt_type" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[excerpt_type]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['excerpt_type'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('If enabled, text will be picked from excerpt field instead of post content area.', 'wa-wps-txt').'</p></div>';
	}


	/* touch swipe */
	public function wa_wps_touch_swipe() {

		echo '<div id="wa_wps_touch_swipe" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[touch_swipe]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['touch_swipe'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('A carousel scrolled by swiping (or dragging on non-touch-devices). Uses touchSwipe plugin.', 'wa-wps-txt').'</p></div>';
	}


	/* Load touch swipe */
	public function wa_wps_load_touch_swipe() {

		echo '<div id="wa_wps_load_touch_swipe" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_configuration[load_touch_swipe]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['configuration']['load_touch_swipe'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('Disable this option, if this script has already loaded on your web site.', 'wa-wps-txt').'</p></div>';
	}

	/* load jQuery */
	public function wa_wps_load_jquery() {

		echo '<div id="wa_wps_load_jquery" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_configuration[load_jquery]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['configuration']['load_jquery'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('Disable this option, if this script has already loaded on your web site.', 'wa-wps-txt').'</p></div>';
	}


	/* load caroufredsel */
	public function wa_wps_load_caroufredsel() {

		echo '<div id="wa_wps_load_caroufredsel" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_configuration[load_caroufredsel]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['configuration']['load_caroufredsel'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('Disable this option, if this script has already loaded on your web site.', 'wa-wps-txt').'</p></div>';
	}


	/* load transit */
	public function wa_wps_load_transit() {

		echo '<div id="wa_wps_load_transit" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_configuration[load_transit]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['configuration']['load_transit'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '<p class="description">'.__('Disable this option, if this script has already loaded on your web site.', 'wa-wps-txt').'</p></div>';
	}
	

	/* infinite carousel */
	public function wa_wps_infinite(){
		echo '
		<div id="wa_wps_infinite" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[infinite]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['infinite'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '
			<p class="description">'.__('Determines whether the carousel should be infinite.', 'wa-wps-txt').'</p>
		</div>';
	}

	/* loading place */
	public function wa_wps_loading_place() {

		echo '<div id="wa_wps_loading_place" class="wplikebtns">';

		foreach($this->loading_places as $val => $trans) {
			$val = esc_attr($val);

			echo '
			<input id="rll-loading-place-'.$val.'" type="radio" name="wa_wps_configuration[loading_place]" value="'.$val.'" '.checked($val, $this->options['configuration']['loading_place'], false).' />
			<label for="rll-loading-place-'.$val.'">'.esc_html($trans).'</label>';
		}

		echo '<p class="description">'.__('Select where all the scripts should be placed.', 'wa-wps-txt').'</p></div>';
	}

	/* display image */
	public function wa_wps_display_image() {
		
		echo '<div id="wa_wps_display_image" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_image]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_image'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display controls */
	public function wa_wps_display_controls(){
		
		echo '<div id="wa_wps_display_controls" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_controls]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_controls'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display add to cart */
	public function wa_wps_display_add_to_cart(){
		
		echo '<div id="wa_wps_display_add_to_cart" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_add_to_cart]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_add_to_cart'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display price */
	public function wa_wps_display_price(){
		
		echo '<div id="wa_wps_display_price" class="wplikebtns">';

		foreach($this->choices as $val => $trans) {
			echo '<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_price]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_price'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display read more text */
	public function wa_wps_display_read_more(){
		echo '
		<div id="wa_wps_display_read_more" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_read_more]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_read_more'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display title */
	public function wa_wps_display_title(){
		echo '
		<div id="wa_wps_display_title" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_title]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_title'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display excerpt*/
	public function wa_wps_display_excerpt(){
		echo '
		<div id="wa_wps_display_excerpt" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_excerpt]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_excerpt'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '</div>';
	}

	/* display pagination */
	public function wa_wps_display_pagination(){
		echo '
		<div id="wa_wps_display_pagination" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[display_pagination]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['display_pagination'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}
		echo '</div>';
	}

	/* auto scroll slider */
	public function wa_wps_auto_scroll(){
		echo '
		<div id="wa_wps_auto_scroll" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[auto_scroll]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['auto_scroll'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '
			<p class="description">'.__('Determines whether the carousel should be auto scroll.', 'wa-wps-txt').'</p>
		</div>';
	}


	/* circular */
	public function wa_wps_circular() {
		echo '
		<div id="wa_wps_circular" class="wplikebtns">';

		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="rll-galleries-'.$val.'" type="radio" name="wa_wps_settings[circular]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['settings']['circular'], FALSE).' />
			<label for="rll-galleries-'.$val.'">'.$trans.'</label>';
		}

		echo '
			<p class="description">'.__('Determines whether the carousel should be circular.', 'wa-wps-txt').'</p>
		</div>';
	}



	/* number of posts to display on the slider */
	public function wa_wps_number_of_posts_to_display(){
		echo '
		<div id="wa_wps_number_of_posts_to_display">
			<input type="text" name="wa_wps_settings[number_of_posts_to_display]" value="'.esc_attr($this->options['settings']['number_of_posts_to_display']).'" />
		</div>';

			echo '
			<p class="description">'.__('Number of posts to be displayed on the slider', 'wa-wps-txt').'</p>
		</div>';	
	}

	/* post order */
	public	function wa_wps_posts_order() {
	    $options = $this->options['settings']['posts_order'];
	     
    	$html = '<select id="wa_wps_posts_order" name="wa_wps_settings[posts_order]">';
        $html .= '<option value="asc"' . selected( esc_attr($this->options['settings']['posts_order']), 'asc', false) . '>Ascending </option>';
        $html .= '<option value="desc"' . selected( esc_attr($this->options['settings']['posts_order']), 'desc', false) . '>Descending</option>';
		$html .= '<option value="rand"' . selected( esc_attr($this->options['settings']['posts_order']), 'rand', false) . '>Random</option>';
    	$html .= '</select>';    
	    echo $html;
	} 

	/* posts order by */
	public function wa_wps_posts_orderby(){
		echo '
		<div id="wa_wps_posts_orderby">
			<input type="text" name="wa_wps_settings[posts_orderby]" value="'.esc_attr($this->options['settings']['posts_orderby']).'" />
		</div>';
	}

	/* category */
	public function wa_wps_posts_category(){
		
		echo '<div id="wa_wps_posts_category">';
		$tax_selected = 'product_cat';
		?>

					<select  name="wa_wps_settings[category][]" multiple required>
					<option value=''>choose...</option>
					<?php

					 $categories = get_terms( $tax_selected , array(
						    'post_type' => 'product' ,
						    'fields' => 'all'

						));

					 if(!empty( $categories )) {

					 foreach ($categories as $key => $value) { ?>

						<option value="<?php echo $value->slug; ?>"
					<?php 
					if(!empty($this->options['settings']['category'] )) {

						$arr = $this->options['settings']['category'];

						if(is_string($this->options['settings']['category'])) {

							$arr = explode(",",$this->options['settings']['category']);
						}

					foreach ($arr as $contractor) {

							if($value->slug==$contractor){ selected( $value->slug, $value->slug ); }
					}
				}
					?> ><?php echo $value->name; ?></option><?php } }?>
				</select>
	<?php		

	echo '</div><p class="description">'.__('Please, hold down the control or command button to select multiple options.', 'wa-wps-txt').'</p></div>';
		
	}

	/* read more text */
	public function wa_wps_read_more_text(){
		echo '
		<div id="wa_wps_read_more_text">
			<input type="text" name="wa_wps_settings[read_more_text]" value="'.esc_attr($this->options['settings']['read_more_text']).'" />
		</div>';
	}


	/* custom css */
	public function wa_wps_custom_css(){
		echo '
		<div id="wa_wps_custom_css">
			<textarea  name="wa_wps_settings[custom_css]" placeholder=".wa_wps_slider_title { color: #ccc !important; }"  >'.esc_attr($this->options['settings']['custom_css']).'</textarea>';
		echo '<p class="description">'.__('custom styles or override existing styles to meet your requirements.', 'wa-wps-txt').'</p></div>';

	}


	/* word limit */
	public function wa_wps_word_limit(){
		echo '
		<div id="wa_wps_word_limit">
			<input type="text" name="wa_wps_settings[word_limit]" value="'.esc_attr($this->options['settings']['word_limit']).'" />
		</div>';

		echo '
			<p class="description">'.__('Limit the no of words in excerpt', 'wa-wps-txt').'</p>
		</div>';
	}

	/* word limit */
	public function wa_wps_default_image_url(){
		echo '
		<div id="wa_wps_default_image_url">
			<input type="text" name="wa_wps_settings[default_image_url]" placeholder="http://yourwebsite.com/images/default-img.png" value="'.esc_attr($this->options['settings']['default_image_url']).'" />
		</div>';

		echo '
			<p class="description">'.__('Custom default image URL.', 'wa-wps-txt').'</p>
		</div>';
	}

	/* transition effects */
	public function wa_wps_fx() {

 		$options = $this->options['settings']['fx'];
	    $html = '<select id="wa_wps_fx" name="wa_wps_settings[fx]">';
        $html .= '<option value="none"' . selected( esc_attr($this->options['settings']['fx']), 'none', false) . '>none</option>';
        $html .= '<option value="scroll"' . selected( esc_attr($this->options['settings']['fx']), 'scroll', false) . '>scroll</option>';
        $html .= '<option value="directscroll"' . selected( esc_attr($this->options['settings']['fx']), 'directscroll', false) . '>directscroll</option>';
    	$html .= '<option value="fade"' . selected( esc_attr($this->options['settings']['fx']), 'fade', false) . '>fade</option>';
    	$html .= '<option value="crossfade"' . selected( esc_attr($this->options['settings']['fx']), 'crossfade', false) . '>crossfade</option>';
    	$html .= '<option value="cover"' . selected( esc_attr($this->options['settings']['fx']), 'cover', false) . '>cover</option>';
    	$html .= '<option value="cover-fade"' . selected( esc_attr($this->options['settings']['fx']), 'cover-fade', false) . '>cover-fade</option>';
    	$html .= '<option value="uncover"' . selected( esc_attr($this->options['settings']['fx']), 'uncover', false) . '>uncover</option>';
    	$html .= '<option value="uncover-fade"' . selected( esc_attr($this->options['settings']['fx']), 'uncover-fade', false) . '>uncover-fade</option>';
    	$html .= '</select>';    
	    echo $html;

	} 

	/* easing effects */
	public function wa_wps_easing_effect(){
		$options = $this->options['settings']['easing_effect'];
	    $html = '<select id="wa_wps_easing_effect" name="wa_wps_settings[easing_effect]">';
        $html .= '<option value="linear"' . selected( esc_attr($this->options['settings']['easing_effect']), 'linear', false) . '>linear</option>';
        $html .= '<option value="swing"' . selected( esc_attr($this->options['settings']['easing_effect']), 'swing', false) . '>swing</option>';
        $html .= '<option value="quadratic"' . selected( esc_attr($this->options['settings']['easing_effect']), 'quadratic', false) . '>quadratic</option>';
    	$html .= '<option value="cubic"' . selected( esc_attr($this->options['settings']['easing_effect']), 'cubic', false) . '>cubic</option>';
    	$html .= '<option value="elastic"' . selected( esc_attr($this->options['settings']['easing_effect']), 'elastic', false) . '>elastic</option>';
    	$html .= '</select>';    
	    echo $html;
	} 

	/* deactivation on delete */
	public function wa_wps_deactivation_delete(){
		echo '
		<div id="wa_wps_deactivation_delete" class="wplikebtns">';
		foreach($this->choices as $val => $trans)
		{
			echo '
			<input id="wa-wps-deactivation-delete-'.$val.'" type="radio" name="wa_wps_configuration[deactivation_delete]" value="'.esc_attr($val).'" '.checked(($val === 'yes' ? TRUE : FALSE), $this->options['configuration']['deactivation_delete'], FALSE).' />
			<label for="wa-wps-deactivation-delete-'.$val.'">'.$trans.'</label>';
		}
		echo '
			<p class="description">'.__('Delete settings on plugin deactivation.', 'wa-wps-txt').'</p>
		</div>';
	}

	/* options page */
	public function options_page() {

		$tab_key = (isset($_GET['tab']) ? $_GET['tab'] : 'general-settings');
		echo '<div class="wrap">'.screen_icon().'
			<h2>'.__('WooCommerce product slider', 'wa-wps-txt').'</h2>
			<h2 class="nav-tab-wrapper">';

		foreach($this->tabs as $key => $name) {

		echo '
			<a class="nav-tab '.($tab_key == $key ? 'nav-tab-active' : '').'" href="'.esc_url(admin_url('options-general.php?page=woocommerce-product-slider&tab='.$key)).'">'.$name['name'].'</a>';
		}
		echo '</h2><div class="wa-wps-settings"><div class="wa-wps-credits"><h3 class="hndle">'.__('Carousel horizontal posts content slider', 'wa-wps-txt').'</h3>
					<div class="inside">
					<p class="inner">'.__('Configuration: ', 'wa-wps-txt').' <a href="http://weaveapps.com/shop/wordpress-plugins/woocommerce-product-slider/#installation" target="_blank" title="'.__('Plugin URL', 'wa-wps-txt').'">'.__('Plugin URI', 'wa-wps-txt').'</a></p>
					</p><hr />
					<h4 class="inner">'.__('Do you like this plugin?', 'wa-wps-txt').'</h4>
					<p class="inner">'.__('Please, ', 'wa-wps-txt').'<a href="http://wordpress.org/support/view/plugin-reviews/woocommerce-product-slider" target="_blank" title="'.__('rate it', 'wa-wps-txt').'">'.__('rate it', 'wa-wps-txt').'</a> '.__('on WordPress.org', 'wa-wps-txt').'<br />          
					<hr />
					<div style="width:auto; margin:auto; text-align:center;"><a href="http://weaveapps.com/shop/wordpress-plugins/woocommerce-product-slider-pro/" target="_blank"><img width="270" height="70" src="'.plugins_url('assets/images/wps-pro.png',__FILE__).'"/></a></div>
					</div>
				</div><form action="options.php" method="post">';
	
		wp_nonce_field('update-options');
		
		settings_fields($this->tabs[$tab_key]['key']);
		
		do_settings_sections($this->tabs[$tab_key]['key']);
		
		echo '<p class="submit">';
		
		submit_button('', 'primary', $this->tabs[$tab_key]['submit'], FALSE);
	
		echo ' ';
		
		echo submit_button(__('Reset to defaults', 'wa-wps-txt'), 'secondary', $this->tabs[$tab_key]['reset'], FALSE);
		
		echo '</p></form></div><div class="clear"></div></div>';
	}

	/* load default settings */
	public function load_defaults(){
		
		$this->choices = array(
			'yes' => __('Enable', 'wa-wps-txt'),
			'no' => __('Disable', 'wa-wps-txt')
		);

		$this->loading_places = array(
			'header' => __('Header', 'wa-wps-txt'),
			'footer' => __('Footer', 'wa-wps-txt')
		);

		$this->tabs = array(
			'general-settings' => array(
				'name' => __('General', 'wa-wps-txt'),
				'key' => 'wa_wps_settings',
				'submit' => 'save_wa_wps_settings',
				'reset' => 'reset_wa_wps_settings',
			),
            'configuration' => array(
                'name' => __('Advanced', 'wa-wps-txt'),
                'key' => 'wa_wps_configuration',
                'submit' => 'save_wa_wps_configuration',
                'reset' => 'reset_wa_wps_configuration'
            )
		);
	}

	/* load text domain for localization */
	public function load_textdomain(){
		load_plugin_textdomain('wa-wps-txt', FALSE, dirname(plugin_basename(__FILE__)).'/lang/');
	}

	/* validate options and register settings */
	public function validate_options($input) {

		if(isset($_POST['save_wa_wps_settings'])) {

			$input['display_image'] = (isset($input['display_image'], $this->choices[$input['display_image']]) ? ($input['display_image'] === 'yes' ? true : false) : $this->defaults['settings']['display_image']);
			
			$input['display_pagination'] = (isset($input['display_pagination'], $this->choices[$input['display_pagination']]) ? ($input['display_pagination'] === 'yes' ? true : false) : $this->defaults['settings']['display_pagination']);
			
			$input['display_excerpt'] = (isset($input['display_excerpt'], $this->choices[$input['display_excerpt']]) ? ($input['display_excerpt'] === 'yes' ? true : false) : $this->defaults['settings']['display_excerpt']);
			
			$input['display_title'] = (isset($input['display_title'], $this->choices[$input['display_title']]) ? ($input['display_title'] === 'yes' ? true : false) : $this->defaults['settings']['display_title']);
			
			$input['display_read_more'] = (isset($input['display_read_more'], $this->choices[$input['display_read_more']]) ? ($input['display_read_more'] === 'yes' ? true : false) : $this->defaults['settings']['display_read_more']);
			
			$input['display_controls'] = (isset($input['display_controls'], $this->choices[$input['display_controls']]) ? ($input['display_controls'] === 'yes' ? true : false) : $this->defaults['settings']['display_controls']);

			$input['display_price'] = (isset($input['display_price'], $this->choices[$input['display_price']]) ? ($input['display_price'] === 'yes' ? true : false) : $this->defaults['settings']['display_price']);

			$input['display_add_to_cart'] = (isset($input['display_add_to_cart'], $this->choices[$input['display_add_to_cart']]) ? ($input['display_add_to_cart'] === 'yes' ? true : false) : $this->defaults['settings']['display_add_to_cart']);

			$input['auto_scroll'] = (isset($input['auto_scroll'], $this->choices[$input['auto_scroll']]) ? ($input['auto_scroll'] === 'yes' ? true : false) : $this->defaults['settings']['auto_scroll']);
			
			$input['circular'] = (isset($input['circular'], $this->choices[$input['circular']]) ? ($input['circular'] === 'yes' ? true : false) : $this->defaults['settings']['circular']);
			
			$input['infinite'] = (isset($input['infinite'], $this->choices[$input['infinite']]) ? ($input['infinite'] === 'yes' ? true : false) : $this->defaults['settings']['infinite']);
		
			$input['fx'] = sanitize_text_field(isset($input['fx']) && $input['fx'] !== '' ? $input['fx'] : $this->defaults['settings']['fx']);			
			
			$input['word_limit'] = sanitize_text_field(isset($input['word_limit']) && $input['word_limit'] !== '' ? $input['word_limit'] : $this->defaults['settings']['word_limit']);
			
			$input['read_more_text'] = sanitize_text_field(isset($input['read_more_text']) && $input['read_more_text'] !== '' ? $input['read_more_text'] : $this->defaults['settings']['read_more_text']);
			
			$input['number_of_posts_to_display'] = sanitize_text_field(isset($input['number_of_posts_to_display']) && $input['number_of_posts_to_display'] !== '' ? $input['number_of_posts_to_display'] : $this->defaults['settings']['number_of_posts_to_display']);
			
			$input['posts_order'] = sanitize_text_field(isset($input['posts_order']) && $input['posts_order'] !== '' ? $input['posts_order'] : $this->defaults['settings']['posts_order']);
			
			$input['posts_orderby'] = sanitize_text_field(isset($input['posts_orderby']) && $input['posts_orderby'] !== '' ? $input['posts_orderby'] : $this->defaults['settings']['posts_orderby']);

			$input['category'] =$input['category'];

			$input['item_width'] =isset($input['item_width']) ? $input['item_width'] : $this->defaults['settings']['item_width'];
			
			$input['item_height'] =isset($input['item_height']) ? $input['item_height'] : $this->defaults['settings']['item_height'];
			
			$input['item_align'] =isset($input['item_align']) ? $input['item_align'] : $this->defaults['settings']['item_align'];
	
			$input['excerpt_type'] = (isset($input['excerpt_type'], $this->choices[$input['excerpt_type']]) ? ($input['excerpt_type'] === 'yes' ? true : false) : $this->defaults['settings']['excerpt_type']);
			
			$input['touch_swipe'] = (isset($input['touch_swipe'], $this->choices[$input['touch_swipe']]) ? ($input['touch_swipe'] === 'yes' ? true : false) : $this->defaults['settings']['touch_swipe']);

			$input['css_transition'] = (isset($input['css_transition'], $this->choices[$input['css_transition']]) ? ($input['css_transition'] === 'yes' ? true : false) : $this->defaults['settings']['css_transition']);

			$input['arrows_colour'] =isset($input['arrows_colour']) ? $input['arrows_colour'] : $this->defaults['settings']['arrows_colour'];
		
			$input['arrows_bg_colour'] =isset($input['arrows_bg_colour']) ? $input['arrows_bg_colour'] : $this->defaults['settings']['arrows_bg_colour'];
			
			$input['arrows_hover_colour'] =isset($input['arrows_hover_colour']) ? $input['arrows_hover_colour'] : $this->defaults['settings']['arrows_hover_colour'];
		
			$input['size_of_direction_arrows'] =isset($input['size_of_direction_arrows']) ? $input['size_of_direction_arrows'] : $this->defaults['settings']['size_of_direction_arrows'];

			$input['timeout'] =isset($input['timeout']) ? $input['timeout'] : $this->defaults['settings']['timeout'];

			$input['custom_css'] =isset($input['custom_css']) ? $input['custom_css'] : $this->defaults['settings']['custom_css'];

			$default_img = plugins_url('assets/images/default-image.jpg', __FILE__);

			$input['default_image_url'] =isset($input['default_image_url']) ? $input['default_image_url'] : $default_img;

			$input['direction'] =isset($input['direction']) ? $input['direction'] : $this->defaults['settings']['direction'];

			$input['image_size'] =isset($input['image_size']) ? $input['image_size'] : $this->defaults['settings']['image_size'];
		

		}elseif(isset($_POST['reset_wa_wps_settings'])) {

			$input = $this->defaults['settings'];

			add_settings_error('reset_general_settings', 'general_reset', __('Settings restored to defaults.', 'wa-wps-txt'), 'updated');
		
		}	elseif(isset($_POST['reset_wa_wps_configuration'])) {

				$input = $this->defaults['configuration'];

				add_settings_error('reset_nivo_settings', 'nivo_reset', __('Settings of were restored to defaults.', 'wa-wps-txt'), 'updated');

		}	else if(isset($_POST['save_wa_wps_configuration'])) {

			$input['loading_place'] = (isset($input['loading_place'], $this->loading_places[$input['loading_place']]) ? $input['loading_place'] : $this->defaults['configuration']['loading_place']);
		
			$input['deactivation_delete'] = (isset($input['deactivation_delete'], $this->choices[$input['deactivation_delete']]) ? ($input['deactivation_delete'] === 'yes' ? true : false) : $this->defaults['configuration']['deactivation_delete']);
		
			$input['load_touch_swipe'] = (isset($input['load_touch_swipe'], $this->choices[$input['load_touch_swipe']]) ? ($input['load_touch_swipe'] === 'yes' ? true : false) : $this->defaults['configuration']['load_touch_swipe']);
		
			$input['load_jquery'] = (isset($input['load_jquery'], $this->choices[$input['load_jquery']]) ? ($input['load_jquery'] === 'yes' ? true : false) : $this->defaults['configuration']['load_jquery']);
			
			$input['load_transit'] = (isset($input['load_transit'], $this->choices[$input['load_transit']]) ? ($input['load_transit'] === 'yes' ? true : false) : $this->defaults['configuration']['load_transit']);
		
			$input['load_caroufredsel'] = (isset($input['load_caroufredsel'], $this->choices[$input['load_caroufredsel']]) ? ($input['load_caroufredsel'] === 'yes' ? true : false) : $this->defaults['configuration']['load_caroufredsel']);
		
		}

		return $input;
	}
}
$WooCommerce_Product_Slider = new WooCommerce_Product_Slider();
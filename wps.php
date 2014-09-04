<?php
/*
Plugin Name: WooCommerce Product Slider
Description: This is JQuery CarouFredSel library based woocommerce product slider.
Author: subhansanjaya
Version: 1.1
Plugin URI: http://wordpress.org/plugins/woocommerce-product-slider/
Author URI: http://www.weaveapps.com.com
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BXBCGCKDD74UE
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if(! defined( 'ABSPATH' )) exit; // Exit if accessed directly
require('classes/class-wps.php');

global $WPS;
$WPS = new WP_Slider();
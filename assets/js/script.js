jQuery(document).ready(function($) {
auto_s = (wpsArgs.auto_scroll=="true") ? true : false;
cir = (wpsArgs.circular=="true") ? true : false;
$("#wa_wp_slider").carouFredSel({
	circular: cir,
	width: '100%',
	height: 'auto',
	infinite: false,
	auto 	: auto_s,
	prev	: {	
		button	: "#wa_wp_slider_prev",
		key		: "left"
	},
	next	: { 
		button	: "#wa_wp_slider_next",
		key		: "right"
	},
	pagination	: "#wa_wp_slider_pag",
	scroll : {
items			: parseInt(wpsArgs.no_of_items_to_scroll),
fx: wpsArgs.fx,
easing : wpsArgs.easing_effect,
duration: 500,					
pauseOnHover	: true
},
	swipe: {
onMouse: false,
onTouch: true
}
});
});
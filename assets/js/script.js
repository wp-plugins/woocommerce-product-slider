jQuery(document).ready(function($) {

auto_s = (wpsArgs.auto_scroll=="true") ? true : false;
cir = (wpsArgs.circular=="true") ? true : false;
infinite = (wpsArgs.infinite=="true") ? true : false;
css_transition = (wpsArgs.css_transition=="true") ? true : false;
touch_swipe = (wpsArgs.touch_swipe=="true") ? true : false;

$("#wa_chpc_slider").carouFredSel({
	width:'100%',
	circular: cir,
	direction: wpsArgs.direction,
	align: 'center',
	infinite: infinite,
	auto 	: {
			play:auto_s,
			timeoutDuration:wpsArgs.time_out
			},
	prev	: {	
		button	: "#wa_chpc_slider_prev",
		key		: "left"
	},
	next	: { 
		button	: "#wa_chpc_slider_next",
		key		: "right"
	},
	pagination	: "#wa_wps_pager",
	scroll : {
fx: wpsArgs.fx,
easing : wpsArgs.easing_effect,
duration: 500,					
pauseOnHover	: true
},
transition:css_transition 
});




if ( touch_swipe ) {

			//touch swipe
			jQuery("#wa_chpc_slider").swipe({ 
			excludedElements: "button, input, select, textarea, .noSwipe", 
			swipeLeft: function() { 
			jQuery('#wa_chpc_slider').trigger('next', 'auto'); 
			}, 
			swipeRight: function() { 
			jQuery('#wa_chpc_slider').trigger('prev', 'auto'); 
			console.log("swipeRight"); 
			}, 
			tap: function(event, target) { 
			jQuery(target).closest('.wps_title').find('a').click(); 
			}
			});

}

});
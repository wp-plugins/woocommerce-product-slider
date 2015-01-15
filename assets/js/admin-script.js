jQuery(document).ready(function() {

	jQuery("#wa_wps_arrows_colour").spectrum({

		showAlpha:true,showInput:false,preferredFormat:"rgb",move:function(c){

		jQuery(this).val(c.toRgbString())

		}

	});

		jQuery("#wa_wps_arrows_bg_colour").spectrum({

		showAlpha:true,showInput:false,preferredFormat:"rgb",move:function(c){

		jQuery(this).val(c.toRgbString())

		}

	});

	jQuery("#wa_wps_arrows_hover_colour").spectrum({

		showAlpha:true,showInput:false,preferredFormat:"rgb",move:function(c){

		jQuery(this).val(c.toRgbString())

		}

	});


	jQuery("#wa_wps_image_hover_colour").spectrum({

		showAlpha:true,showInput:false,preferredFormat:"rgb",move:function(c){

		jQuery(this).val(c.toRgbString())

		}

	});

});
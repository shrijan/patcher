console.log("- nsw_admin_scripts.js v2 loaded");


jQuery(document).ready(function(){

	//Add hero image preview in admin
	jQuery("select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]']").after("<img src='/modules/custom/dphi_components/themes/nsw_admin_theme/images/hero/" + jQuery("select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]']").val() +".png' id='hero-example'>");

	jQuery(document).ajaxComplete(function(){
	//	console.log('ajax complete');
		if(!jQuery("#hero-example").length && jQuery("select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]']").length){
			jQuery("select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]']:focusable").after("<img src='/modules/custom/dphi_components/themes/nsw_admin_theme/images/hero/" + jQuery("select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]']").val() +".png' id='hero-example'>");
		}
	});

	jQuery("select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']").after("<img src='/modules/custom/dphi_components/themes/nsw_admin_theme/images/hero/" + jQuery("select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']").val() +".png' id='hero-example'>");

	jQuery(document).ajaxComplete(function(){
	//	console.log('ajax complete');
		if(!jQuery("#hero-example").length && jQuery("select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']").length){
			jQuery("select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']:focusable").after("<img src='/modules/custom/dphi_components/themes/nsw_admin_theme/images/hero/" + jQuery("select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']").val() +".png' id='hero-example'>");
		}
	});



});//end onready



jQuery(document).on("change", "select[name='field_top_section[0][subform][field_section_content][0][subform][field_hero_type]'], select[name='field_top_section[0][subform][field_section_content][1][subform][field_hero_type]']", function(e){

	jQuery("#hero-example").attr("src", "/modules/custom/dphi_components/themes/nsw_admin_theme/images/hero/" + jQuery(this).val() + ".png");
	
});
import * as $ from "jquery";
export default class Gateway{
	private static $form = $('body.transaction-settings-gateway .create_form');
	private static showGatewayFields(){
		$('select[name=gateway]', Gateway.$form).change(function(){
			var $val = $(this).val();
			$('.gatewayfields:not(.gateway-'+$val+")",Gateway.$form).hide();
			$('.gatewayfields.gateway-'+$val, Gateway.$form).show();
		}).trigger('change');
	}
	public static init(){
		Gateway.showGatewayFields();
	}
	public static initIfNeeded(){
		if(Gateway.$form.length){
			Gateway.init();
		}
	}
}
export default class Gateway {
	public static init() {
		Gateway.showGatewayFields();
	}
	public static initIfNeeded() {
		if (Gateway.$form.length) {
			Gateway.init();
		}
	}
	private static $form = $("body.transaction-settings-gateway .create_form");
	private static showGatewayFields() {
		$("select[name=gateway]", Gateway.$form).change(function() {
			const val = $(this).val();
			$(".gatewayfields:not(.gateway-" + val + ")", Gateway.$form).hide();
			$(".gatewayfields.gateway-" + val, Gateway.$form).show();
		}).trigger("change");
	}
}

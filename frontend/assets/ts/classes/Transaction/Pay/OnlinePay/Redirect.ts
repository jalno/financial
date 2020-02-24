import * as $ from "jquery";
export default class Redirect {
	public static init() {
		Redirect.runFormSubmiter();
	}
	public static initIfNeeded() {
		if (Redirect.$form.length) {
			Redirect.init();
		}
	}
	private static $form = $("#onlinepay_redirect_form");
	private static runFormSubmiter() {
		Redirect.$form.submit();
	}
}

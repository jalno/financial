import * as $ from "jquery";
export default class Redirect{
	private static $form = $('#onlinepay_redirect_form');
	private static runFormSubmiter(){
		Redirect.$form.submit();
	}
	public static init(){
		Redirect.runFormSubmiter();
	}
	public static initIfNeeded(){
		if(Redirect.$form.length){
			Redirect.init();
		}
	}
}
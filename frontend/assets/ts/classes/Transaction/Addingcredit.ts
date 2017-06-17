import * as $ from "jquery";
import "../jquery.userAutoComplete";
export default class Addingcredit{
	private static $form = $('.addingcredit_form');
	private static runUserSearch(){
		$('input[name=client_name]', Addingcredit.$form).userAutoComplete();
	}
	public static init(){
		if($('input[name=client_name]', Addingcredit.$form).length){
			Addingcredit.runUserSearch();
		}
	}
	public static initIfNeeded(){
		if(Addingcredit.$form.length){
			Addingcredit.init();
		}
	}
}
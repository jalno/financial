import * as $ from "jquery";
import "../jquery.financialUserAutoComplete";
export default class List{
	private static $form = $('#transactionsearch');
	private static runUserSearch(){
		$('input[name=user_name]', List.$form).financialUserAutoComplete();
	}
	public static init(){
		if($('input[name=user_name]', List.$form).length){
			List.runUserSearch();
		}
	}
	public static initIfNeeded(){
		if(List.$form.length){
			List.init();
		}
	}
}
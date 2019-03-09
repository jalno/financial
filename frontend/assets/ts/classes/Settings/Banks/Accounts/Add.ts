import * as $ from "jquery";
import "../../../jquery.financialUserAutoComplete";

export default class Add {
	public static initIfNeeded() {
		Add.$form = $("body.settings-banks-accounts.banks-accounts-add form#add-banks-account");
		if (Add.$form.length) {
			Add.init();
		}
	}
	protected static $form: JQuery;
	protected static init() {
		Add.runUserAutoComplete();
	}
	protected static runUserAutoComplete() {
		const $input = $("input[name=user_name]", Add.$form);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
	}
}

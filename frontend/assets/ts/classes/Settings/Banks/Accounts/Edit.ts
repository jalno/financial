import * as $ from "jquery";
import "../../../jquery.financialUserAutoComplete";

export default class Edit {
	public static initIfNeeded() {
		Edit.$form = $("body.settings-banks-accounts.banks-accounts-edit form#edit-banks-account");
		if (Edit.$form.length) {
			Edit.init();
		}
	}
	protected static $form: JQuery;
	protected static init() {
		Edit.runUserAutoComplete();
	}
	protected static runUserAutoComplete() {
		const $input = $("input[name=user_name]", Edit.$form);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
	}
}

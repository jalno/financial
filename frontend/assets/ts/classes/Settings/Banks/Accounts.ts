import $ from "jquery";
import "../../jquery.financialUserAutoComplete";
import Add from "./Accounts/Add";
import Edit from "./Accounts/Edit";

export default class Accounts {
	public static initIfNeeded() {
		Add.initIfNeeded();
		Edit.initIfNeeded();
		Accounts.$searchForm = $("body.settings-banks-accounts form#search-form");
		if (Accounts.$searchForm.length) {
			Accounts.init();
		}
	}
	protected static $searchForm: JQuery;
	protected static init() {
		Accounts.runUserAutoComplete();
	}
	protected static runUserAutoComplete() {
		const $input = $("input[name=user_name]", Accounts.$searchForm);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
	}
}

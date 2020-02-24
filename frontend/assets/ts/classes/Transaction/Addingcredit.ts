import * as $ from "jquery";
import "../jquery.financialUserAutoComplete";
import { IUser } from "../jquery.financialUserAutoComplete";
export default class Addingcredit {
	public static init() {
		if ($("input[name=client_name]", Addingcredit.$form).length) {
			Addingcredit.runUserSearch();
		}
	}
	public static initIfNeeded() {
		if (Addingcredit.$form.length) {
			Addingcredit.init();
		}
	}
	private static $form = $(".addingcredit_form");
	private static runUserSearch() {
		const $input = $("input[name=client_name]", Addingcredit.$form);
		$input.financialUserAutoComplete();
		const $currency = $("input[name=price]").parents(".input-group").find(".input-group-addon");
		$currency.data("default", $currency.html());
		$input.on("financialUserAutoComplete.select", (e, user: IUser) => {
			$currency.html(user.currency);
		});
		$input.on("financialUserAutoComplete.unselect", () => {
			$currency.html($currency.data("default"));
		});
	}
}

import * as $ from "jquery";
import "webuilder/formAjax";
import "../jquery.financialUserAutoComplete";
import { IUser } from "../jquery.financialUserAutoComplete";
import Transaction from "../Transaction";

export default class Addingcredit {
	public static init() {
		if ($("input[name=client_name]", Addingcredit.$form).length) {
			Addingcredit.runUserSearch();
		}
		Addingcredit.runNumberFormatListener();
		Addingcredit.runSubmitFormListener();
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
	private static runNumberFormatListener() {
		$("input[name=price]", Addingcredit.$form).on("keyup", function(e) {
			let val = Transaction.deFormatNumber($(this).val() as string);
			const isDot = e.keyCode === 110;
			const number = parseInt(val, 10);
			if (isNaN(number)) {
				$(this).val(isDot ? "0." : "");
				return;
			}
			val = Transaction.formatFloatNumber(parseFloat(val));
			if (isDot) {
				val += ".";
			}
			$(this).val(val);
		});
	}
	private static runSubmitFormListener() {
		const $price = $("input[name=price]", Addingcredit.$form);
		Addingcredit.$form.on("submit", function(e) {
			e.preventDefault();
			const price = parseFloat(Transaction.deFormatNumber($price.val()));
			$price.inputMsg("reset");
			if (isNaN(price) || price <= 0) {
				$price.inputMsg({
					type: "error",
					message: t("packages.financial.data_validation.enter.price.morethan", {
						price: 0,
					}),
				});
				return;
			}
			const data = new FormData(this);
			data.set("price", price.toString());
			$(this).formAjax({
				data,
				processData: false,
				contentType: false,
				success: (response) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					window.location.href = response.redirect;
				},
				error: (response) => {
					if (response.error === "data_duplicate" || response.error === "data_validation") {
						const $input = $(`[name="${response.input}"]`, this);
						const $params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${response.error}`),
						};
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$.growl.error($params);
						}
					} else {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("packages.financial.request.error"),
						});
					}
				},
			});
		});
	}
}

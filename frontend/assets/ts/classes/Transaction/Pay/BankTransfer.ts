import * as $ from "jquery";
import Transaction from "../../Transaction";
import "webuilder/formAjax";

export default class BankTransfer {
	public static initIfNeeded() {
		BankTransfer.$form = $("body.transaction-pay-banktransfer form.pay_banktransfer_form");
		if (BankTransfer.$form.length) {
			BankTransfer.init();
		}
	}
	private static init() {
		BankTransfer.runNumberFormatListener();
		BankTransfer.runSubmitFormListener();
	}
	private static $form: JQuery;
	private static runNumberFormatListener() {
		$("input[name=price]", BankTransfer.$form).on("keyup change", function(e) {
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
		}).trigger("change");
	}
	private static runSubmitFormListener() {
		const $price = $("input[name=price]", BankTransfer.$form);
		BankTransfer.$form.on("submit", function(e) {
			e.preventDefault();
			const price = parseFloat(Transaction.deFormatNumber($price.val()));
			$(".has-error input, .has-error select").inputMsg("reset");
			const data = new FormData(this);
			data.set("price", price.toString());
			$(this).formAjax({
				data: data,
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

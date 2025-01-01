import "@jalno/translator";
import "bootstrap/js/tooltip";
import * as $ from "jquery";
import "webuilder/formAjax";
import Transaction from "../../Transaction";

interface IFormAjaxError {
	input?: string;
	error: "data_duplicate" | "data_validation" | "unknown" | string;
	type: "fatal" | "warning" | "notice";
	code?: string;
	message?: string;
}

export default class ByCredit {
	public static initIfNeeded() {
		ByCredit.$form = $("form.pay_credit_form");
		if (ByCredit.$form.length) {
			ByCredit.init();
		}
	}
	public static init() {
		ByCredit.runFormatListener();
		ByCredit.runSubmitListener();
	}
	private static $form: JQuery;
	private static runFormatListener() {
		$("input[name=price]", ByCredit.$form).on("change keyup", function(e) {
			$(this).inputMsg("reset");
			const val = $(this).val() as string;
			if (!val || (e.keyCode === 110 && val.match(/\./g).length === 1)) {
				return;
			}
			$(this).val(Transaction.formatFloatNumber(parseFloat(Transaction.deFormatNumber(val.toString()))));
		}).trigger("change");
	}
	private static runSubmitListener() {
		const $priceInput = $("input[name=price]", ByCredit.$form);
		ByCredit.$form.on("submit", (e) => {
			e.preventDefault();
			const data = {
				price: Transaction.deFormatNumber($priceInput.val() as string),
			};

			(ByCredit.$form as any).formAjax({
				data: data,
				success: (response: {status: true, redirect: string}) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					window.location.href = response.redirect;
				},
				error: (error: IFormAjaxError) => {
					const params = {
						title: t("error.fatal.title"),
						message: error.message ? error.message : (error.code ? t(`error.${error.code}`) : t("userpanel.formajax.error")),
					};
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						params.message = t(`packages.financial.${error.error}`);
						const $input = $(`[name="${error.input}"]`, ByCredit.$form);
						if ($input.length) {
							$input.inputMsg(params);
						} else {
							$.growl.error(params);
						}
						return;
					}
					$.growl.error(params);
				},
			});
		});
	}
}

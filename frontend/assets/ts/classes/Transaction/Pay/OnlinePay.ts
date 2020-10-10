import * as $ from "jquery";
import Transaction from "../../Transaction";

export default class OnlinePay {
	public static initIfNeeded() {
		if (OnlinePay.$form.length) {
			OnlinePay.init();
		}
	}
	public static init() {
		OnlinePay.payPortsCurrecyListener();
		OnlinePay.runFormatListener();
		OnlinePay.runSubmitListener();
	}
	protected static readonly $form = $("body.transaction-pay-online form.online-pay-form");
	protected static userCurrency: number;
	protected static payPortsCurrecyListener() {
		$("select[name=payport]", OnlinePay.$form).on("change", function() {
			const $selected = $("option:selected", $(this));
			const price = $selected.data("price");
			const title = $selected.data("title");
			const currency = $selected.data("currency");
			if (price !== undefined && title !== undefined) {
				const $price = $("input[name=price]", OnlinePay.$form);
				$price.val(price);
				$price.parents(".input-group").find(".input-group-addon").html(title);
				$("input[name=currency]", OnlinePay.$form).val(currency);
			}
		});
		$("select[name=payport] option", OnlinePay.$form).first().trigger("change");
	}
	protected static runFormatListener(): void {
		$("input[name=price]", OnlinePay.$form).on("change keyup", function(e) {
			const val = $(this).val() as string;
			if (!val || (e.keyCode === 110 && val.match(/\./g).length === 1)) {
				return;
			}
			$(this).val(Transaction.formatFloatNumber(parseFloat(Transaction.deFormatNumber(val.toString()))));
		}).trigger("change");
	}
	protected static runSubmitListener(): void {
		OnlinePay.$form.on("submit", () => {
			const $price = $("input[name=price]", OnlinePay.$form);
			$price.val(parseFloat(Transaction.deFormatNumber($price.val().toString())));
		});
	}
}

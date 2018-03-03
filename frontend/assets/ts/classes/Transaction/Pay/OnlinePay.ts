import * as $ from "jquery";

export default class OnlinePay{
	public static init(){
		OnlinePay.payPortsCurrecyListener();
	}
	public static initIfNeeded(){
		if(OnlinePay.$form.length){
			OnlinePay.init();
		}
	}
	protected static readonly $form = $("form.pay_credit_form");
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
}
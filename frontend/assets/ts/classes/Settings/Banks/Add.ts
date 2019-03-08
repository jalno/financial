import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import "webuilder/formAjax";
import Banks, { IBank } from "../Banks";

export default class Add {
	public static initIfNeeded() {
		Add.$form = $("body.settings-banks form#add-bank-form");
		if (Add.$form.length) {
			Add.init();
		}
	}
	protected static $form: JQuery;
	protected static init() {
		Add.runFormSubmitListener();
	}
	protected static runFormSubmitListener() {
		const $panel = Add.$form.parents(".panel");
		const $btn = $(".panel-footer .btn.btn-submit", $panel);
		Add.$form.on("submit", function(e) {
			e.preventDefault();
			$btn.prop("disabled", true);
			$(this).formAjax({
				success: (data) => {
					$.growl.notice({
						title: "موفق",
						message: "بانک با موفقیت اضافه شد.",
						location: "bl",
					});
					$btn.prop("disabled", false);
					(this as HTMLFormElement).reset();
					Banks.prependBank(data.bank as IBank);
				},
				error: (data) => {
					$btn.prop("disabled", false);
					if (data.error === "data_duplicate" || data.error === "data_validation") {
						const $input = $(`[name="${data.input}"]`, this);
						const params = {
							title: "خطا",
							message: "",
							location: "bl",
						};
						if (data.error === "data_validation") {
							params.message = "داده وارد شده معتبر نیست";
						} else if (data.error === "data_duplicate") {
							params.message = "داده وارد شده تکراری میباشد";
						}
						if ($input.length) {
							$input.inputMsg(params);
						} else {
							$.growl.error(params);
						}
					} else {
						$.growl.error({
							title: "خطا",
							message: "درخواست شما توسط سرور قبول نشد",
							location: "bl",
						});
					}
				},
			});
		});
	}
}

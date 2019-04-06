import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import "webuilder/formAjax";

export default class View {
	public static initIfNeeded() {
		View.$acceptForm = $("#refund-accept-modal #refund-accept-form");
		View.$rejectForm = $("#refund-reject-modal #refund-reject-form");
		if (View.$acceptForm.length || View.$rejectForm.length) {
			View.init();
		}
	}
	protected static $acceptForm: JQuery;
	protected static $rejectForm: JQuery;
	protected static init() {
		if (View.$acceptForm.length) {
			View.acceptFormListener();
		}
		if (View.$rejectForm.length) {
			View.rejectFormListener();
		}
	}
	protected static acceptFormListener() {
		View.$acceptForm.on("submit", function(e) {
			e.preventDefault();
			$(this).formAjax({
				success: (data) => {
					$.growl.notice({
						title: "موفق",
						message: "انجام شد .",
					});
					setTimeout(() => {
						window.location.reload();
					}, 500);
				},
				error: (error) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $("[name=" + error.input + "]");
						const $params = {
							title: "خطا",
							message: "",
							location: "bl",
						};
						if (error.error === "data_validation") {
							$params.message = "داده وارد شده معتبر نیست";
						} else if (error.error === "data_duplicate") {
							$params.message = "داده وارد شده تکراری میباشد";
						}
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$.growl.error($params);
						}
					} else {
						$.growl.error({
							title: "خطا",
							message: "درخواست شما توسط سرور قبول نشد",
						});
					}
				},
			});
		});
	}
	protected static rejectFormListener() {
		View.$rejectForm.on("submit", function(e) {
			e.preventDefault();
			$(this).formAjax({
				success: (data) => {
					$.growl.notice({
						title: "موفق",
						message: "انجام شد .",
					});
					setTimeout(() => {
						window.location.reload();
					}, 500);
				},
				error: (error) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $("[name=" + error.input + "]");
						const $params = {
							title: "خطا",
							message: "",
							location: "bl",
						};
						if (error.error === "data_validation") {
							$params.message = "داده وارد شده معتبر نیست";
						} else if (error.error === "data_duplicate") {
							$params.message = "داده وارد شده تکراری میباشد";
						}
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$.growl.error($params);
						}
					} else {
						$.growl.error({
							title: "خطا",
							message: "درخواست شما توسط سرور قبول نشد",
						});
					}
				},
			});
		});
	}
}

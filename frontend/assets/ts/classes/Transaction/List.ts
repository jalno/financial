import "@jalno/translator";
import "bootstrap-inputmsg";
import "jalali-daterangepicker";
import * as moment from "jalali-moment";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import { Router } from "webuilder";
import "webuilder/formAjax";
import "../jquery.financialUserAutoComplete";
import { IUser } from "../jquery.financialUserAutoComplete";
import Transaction from "../Transaction";

export default class List {
	public static initIfNeeded() {
		List.$form = $("#transactionsearch");
		List.$refundForm = $(".refund-request #refund-form");
		if (List.$form.length || List.$refundForm.length) {
			List.init();
		}
	}
	protected static $form: JQuery;
	protected static $refundForm: JQuery;
	protected static init() {
		if (List.$form.length) {
			List.runUserSearch();
			List.runDateRangePicker();
			List.exportListener();
		}
		if (List.$refundForm.length) {
			List.runRefundUserAutoComplete();
			List.runNumberFormatListener();
			List.refundFormSubmitListener();
		}
	}
	protected static runUserSearch() {
		const $input = $("input[name=user_name]", List.$form);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
	}
	protected static runRefundUserAutoComplete() {
		const $input = $("input[name=refund_user_name]", List.$refundForm);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
		const $currency = $("input[name=refund_price]", List.$refundForm).parents(".input-group").find(".input-group-addon");
		const $userinfo = $(".user-currenct-credit", List.$refundForm);
		$input.on("financialUserAutoComplete.select", (e, user: IUser) => {
			List.$refundForm.data("credit", user.credit);
			$(".btn.btn-refund", List.$refundForm).prop("disabled", !user.credit);
			$currency.html(user.currency);
			$(".user-credit", $userinfo).html(user.credit.toString());
			$(".user-currency", $userinfo).html(user.currency);
			$userinfo.slideDown();
		});
		const $accounts = $("select[name=refund_account]", List.$refundForm);
		$("input[name=refund_user]", List.$refundForm).on("change", function() {
			const val = parseInt($(this).val(), 10);
			if (!val) {
				return;
			}
			let $selected: JQuery;
			$("option", $accounts).each(function() {
				const userid = parseInt($(this).data("user"), 10);
				if (userid === val) {
					$(this).show();
					if (!$selected) {
						$selected = $(this);
					}
				} else {
					$(this).hide();
				}
			});
			$accounts.val($selected ? $selected.val() : "");
		}).trigger("change");
	}
	protected static refundFormSubmitListener() {
		List.$refundForm.on("submit", function(e) {
			e.preventDefault();
			const $price = $("input[name=refund_price]", this);
			const price = parseFloat(Transaction.deFormatNumber($price.val() as string));
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
			if (price > $(this).data("credit")) {
				$price.inputMsg({
					type: "error",
					message: t("packages.financial.data_validation.price.morethan.blance"),
				});
				return;
			}
			const data = new FormData(this);
			data.set("refund_price", price.toString());
			$(this).formAjax({
				data: data,
				contentType: false,
				processData: false,
				success: (data: any) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					setTimeout(() => {
						window.location.href = data.redirect;
					}, 500);
				},
				error: (error: any) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $("[name=" + error.input + "]");
						const $params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${error.error}`),
							location: "bl",
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
	private static runDateRangePicker() {
		moment.locale(Translator.getActiveShortLang());
		const config: any = {
			autoUpdateInput: false,
			showDropdowns: true,
			singleDatePicker: true,
		};
		if (Translator.getActiveShortLang() === "fa") {
			config.locale = {
				format: "YYYY/MM/DD",
				monthNames: (moment.localeData() as any)._jMonthsShort,
				firstDay: 6,
				direction: "rtl",
				separator: " - ",
				applyLabel: t("packages.financial.action"),
				cancelLabel: t("cancel"),
			};
		}
		$('input[name="create_from"], input[name="create_to"]', List.$form).daterangepicker(config, function(start) {
			$(this.element).val(start.format("YYYY/MM/DD"));
		});
	}
	private static exportListener() {
		const $refund = $("input[name=refund]", List.$form);
		const $exportType = $(".core-box select[name=download]");
		const updateDownloadUrl = () => {
			const query = {
				download: $exportType.val(),
			};
			$("input,select", List.$form).each(function() {
				const name = $(this).attr("name");
				const type = $(this).attr("type");
				if (type === "checkbox") {
					query[name] = $(this).prop("checked");
				} else {
					query[name] = $(this).val();
				}
			});
			$("#download-export-file").attr("href", Router.url("userpanel/transactions", query as any));
		};
		$exportType.on("change", function() {
			const refund = $refund.prop("checked");
			$("option", this).each(function() {
				if ($(this).data("refund")) {
					if (refund) {
						$(this).show();
					} else {
						$(this).hide();
					}
				}
			});
			updateDownloadUrl();
		}).trigger("change");
	}
	private static runNumberFormatListener() {
		$("input[name=refund_price]", List.$refundForm).on("keyup", function(e) {
			let val = Transaction.deFormatNumber($(this).val() as string);
			const isDot = e.keyCode === 110;
			const number = parseInt(val, 10);
			if (isNaN(number)) {
				$(this).val(isDot ? "0." : "");
				return;
			}
			val = Transaction.formatFlotNumber(parseFloat(val));
			if (isDot) {
				val += ".";
			}
			$(this).val(val);
		});
	}
}

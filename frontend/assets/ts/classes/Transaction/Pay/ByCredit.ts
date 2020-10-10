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
		if (ByCredit.$form.length) {
			ByCredit.init();
		}
	}
	public static init() {
		ByCredit.runFormatListener();
		if ($("input[name=user]", ByCredit.$form).length) {
			ByCredit.runUserListener();
		}
		ByCredit.runSubmitListener();
	}
	private static $form = $(".pay_credit_form");
	private static runUserListener(): void {
		$("input[name=user]", ByCredit.$form).on("change", function(e) {
			const $this = $(this);
			if ($this.data("credit") === 0) {
				$this.prop("checked", false);
				$this.prop("disabled", true);
				$this.parent().css({
					opacity: 0.5,
					cursor: "not-allowed",
				});
				$this.parent().tooltip({
					title: t("shortcut.transactions.user.credit.iszero"),
					trigger: "hover",
				});
				$this.tooltip();
			}
			const $parent = ByCredit.$form.parent();
			if ($this.prop("checked")) {
				const price = ByCredit.$form.data("price");
				const credit = $this.data("credit");
				$("input[name=currentcredit]", ByCredit.$form).attr("value", credit);
				$("input[name=credit]", ByCredit.$form).attr("value", Math.min(credit, price));
				$(".alert", $parent).remove();
				if (credit < price) {
					if (!$(".alertes", $parent).length) {
						$parent.prepend(`<div class="alertes"></div>`);
					}
					const alert = `<div class="alert alert-block alert-info fade in">
										<button data-dismiss="alert" class="close" type="button">&times;</button>
										<h4 class="alert-heading"><i class="fa fa-info-circle"></i> ${t("attention")}!</h4>
										<p>${t("pay.credit.attention.notpaidcomplatly", {
											remain: price - credit,
										})}</p>
									</div>`;
					$(".alertes", $parent).html(alert);
				}
			}
		});
	}
	private static runFormatListener() {
		$("input[name=credit]", ByCredit.$form).on("change keyup", function(e) {
			$(this).inputMsg("reset");
			const val = $(this).val() as string;
			if (!val || (e.keyCode === 110 && val.match(/\./g).length === 1)) {
				return;
			}
			$(this).val(Transaction.formatFloatNumber(parseFloat(Transaction.deFormatNumber(val.toString()))));
		}).trigger("change");
	}
	private static runSubmitListener() {
		const $credit = $("input[name=credit]", ByCredit.$form);
		const $user = $("input[name=user]", ByCredit.$form);
		ByCredit.$form.on("submit", (e) => {
			e.preventDefault();
			const data: any = {
				credit: Transaction.deFormatNumber($credit.val() as string),
			};
			if ($user.length) {
				data.user = $user.val() as string;
			}
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

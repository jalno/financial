import "@jalno/translator";
import "bootstrap/js/tooltip";
import * as $ from "jquery";
export default class ByCredit {
	public static init() {
		if ($("input[name=user]", ByCredit.$form).length) {
			ByCredit.runUserListener();
		}
	}
	public static initIfNeeded() {
		if (ByCredit.$form.length) {
			ByCredit.init();
		}
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
		}).trigger("change");
	}
}

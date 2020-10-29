
import * as Clipboard from "clipboard";
import PayByCredit from "./Pay/ByCredit";
import EditPay from "./Pay/Edit";
import OnlinePay from "./Pay/OnlinePay";
import BankTransfer from "./Pay/BankTransfer";
import Redirect from "./Pay/OnlinePay/Redirect";

export default class Pay {
	public static initIfNeeded() {
		PayByCredit.initIfNeeded();
		EditPay.initIfNeeded();
		OnlinePay.initIfNeeded();
		Redirect.initIfNeeded();
		BankTransfer.initIfNeeded();
		if ($("body").hasClass("transaction-pay")) {
			this.init();
		}
	}
	public static init() {
		this.initGuestPayClickBtn();
	}
	private static initGuestPayClickBtn() {
		const clipboard = new Clipboard(".btn-copy-link");
		clipboard.on("success", (e) => {
			$(e.trigger).tooltip({
				title: "کپی شد!",
				trigger: "manual",
			}).tooltip("show");
			setTimeout(() => {
				$(e.trigger).tooltip("hide");
			}, 1500);
			e.clearSelection();
		});
	}
}

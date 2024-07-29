import "bootstrap-inputmsg";
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
			const form = this as HTMLFormElement;
			$(form).formAjax({
				success: () => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					setTimeout(() => {
						window.location.reload();
					}, 500);
				},
				error: (error: any) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`, form);
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
	protected static rejectFormListener() {
		View.$rejectForm.on("submit", function(e) {
			e.preventDefault();
			const form = this as HTMLFormElement;
			$(form).formAjax({
				success: () => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					setTimeout(() => {
						window.location.reload();
					}, 500);
				},
				error: (error: any) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`, form);
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
							message: t("packages.financial.request.success"),
						});
					}
				},
			});
		});
	}
}

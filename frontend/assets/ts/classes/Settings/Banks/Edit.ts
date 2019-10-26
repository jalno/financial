import "@jalno/translator";
import "bootstrap";
import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import "webuilder/formAjax";
import Banks, { IBank, Status } from "../Banks";

export default class Edit {
	public static run($container: JQuery) {
		Edit.$container = $container;
		Edit.setEvnets();
	}
	protected static $container: JQuery;
	protected static $element: JQuery;
	protected static $modal: JQuery;
	protected static bank: IBank;
	protected static $form: JQuery;
	protected static $btn: JQuery;
	protected static setEvnets() {
		$(`[data-action="edit"]`, Edit.$container).on("click", function(e) {
			e.preventDefault();
			Edit.$element = $(this).parents("tr");
			Edit.bank = Edit.$element.data("bank") as IBank;
			Edit.openModal();
		});
	}
	protected static openModal() {
		if (!Edit.$modal) {
			Edit.$modal = $(`<div class="modal fade" tabindex="-1" data-show="true" role="dialog">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">${t("financial.banks.edit.title")}</h4>
			</div>
			<div class="modal-body">
				<form id="edit-form" class="form-horizontal" method="POST">
					<div class="form-group">
						<label class="control-label col-sm-3">${t("packages.financial.bank.title")}</label>
						<div class="col-sm-9">
							<input type="text" value=""  name="title" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">${t("packages.financial.bank.status")}</label>
						<div class="col-sm-9">
							<select name="status" class="form-control">
								<option value="${Status.Active}">${t("packages.financial.bank.status.Active")}</option>
								<option value="${Status.Deactive}">${t("packages.financial.bank.status.Deactive")}</option>
							</select>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="submit" form="edit-form" class="btn btn-teal btn-success">
					<div class="btn-icons"> <i class="fa fa-edit"></i> </div>
					${t("packages.financial.update")}
				</button>
				<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">${t("packages.financial.cancel")}</button>
			</div>
		</div>`).appendTo("body");
			Edit.$form = $("form", Edit.$modal);
			Edit.$btn = $(".btn-success", Edit.$modal);
			Edit.runFormListener();
		}
		$("input[name=title]", Edit.$form).val(Edit.bank.title);
		$("select[name=status]", Edit.$form).val(Edit.bank.status);
		Edit.$modal.modal("show");
	}
	protected static closeModal() {
		if (!Edit.$modal.length) {
			return;
		}
		Edit.$btn.prop("disabled", false);
		Edit.$modal.modal("hide");
	}
	protected static runFormListener() {
		Edit.$form.on("submit", function(e) {
			e.preventDefault();
			Edit.$btn.prop("disabled", true);
			$(this).formAjax({
				url: `userpanel/settings/financial/banks/${Edit.bank.id}/edit?ajax=1`,
				success: (data) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("userpanel.formajax.success"),
						location: "bl",
					});
					Edit.closeModal();
					if (data.hasOwnProperty("bank")) {
						const bank = data.bank as IBank;
						bank.status = parseInt(data.bank.status as string, 10);
						if (bank.title !== Edit.bank.title) {
							$("td", Edit.$element).eq(1).html(bank.title);
						}
						if (bank.status !== Edit.bank.status) {
							$("td", Edit.$element).eq(2).html(Banks.getStatus(bank.status));
						}
						Edit.$element.data("bank", bank);
					}
				},
				error: (data) => {
					Edit.$btn.prop("disabled", false);
					if (data.error === "data_duplicate" || data.error === "data_validation") {
						const $input = $(`[name="${data.input}"]`, this);
						const params = {
							title: t("error.fatal.title"),
							message: "",
							location: "bl",
						};
						if (data.error === "data_validation") {
							params.message = t("packages.financial.data_validation");
						} else if (data.error === "data_duplicate") {
							params.message = t("packages.financial.data_duplicate");
						}
						if ($input.length) {
							$input.inputMsg(params);
						} else {
							$.growl.error(params);
						}
					} else {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("userpanel.formajax.error"),
							location: "bl",
						});
					}
				},
			});
		});
	}
}

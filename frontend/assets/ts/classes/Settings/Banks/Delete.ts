import "bootstrap";
import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder";
import "webuilder/formAjax";
import Banks, { IBank, Status } from "../Banks";

export default class Delete {
	public static run($container: JQuery) {
		Delete.$container = $container;
		Delete.setEvnets();
	}
	protected static $container: JQuery;
	protected static $element: JQuery;
	protected static $modal: JQuery;
	protected static bank: IBank;
	protected static $form: JQuery;
	protected static $btn: JQuery;
	protected static setEvnets() {
		$(`[data-action="delete"]`, Delete.$container).on("click", function(e) {
			e.preventDefault();
			Delete.$element = $(this).parents("tr");
			Delete.bank = Delete.$element.data("bank") as IBank;
			Delete.openModal();
		});
	}
	protected static openModal() {
		if (!Delete.$modal) {
			Delete.$modal = $(`<div class="modal fade" tabindex="-1" data-show="true" role="dialog">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">حذف بانک</h4>
			</div>
			<div class="modal-body">
				<form id="delete-form" class="form-horizontal" method="POST">
					<div class="alert alert-warning">
						<h4 class="alert-heading"> <i class="fa fa-exclamation-triangle"></i> هشدار </h4>
						تمامی سوابق تراکنش های مربوط به این بانک از بین خواهد رفت.
						<br>
						این عملیات غیر قابل بازگشت است.
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">شناسه</label>
						<div class="col-sm-9 bank-id"></div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">عنوان</label>
						<div class="col-sm-9 bank-title"></div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3">وضعیت</label>
						<div class="col-sm-9 bank-status"></div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="submit" form="delete-form" class="btn btn-danger btn-success">
					<div class="btn-icons"> <i class="fa fa-trash"></i> </div>
					حذف
				</button>
				<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">انصراف</button>
			</div>
		</div>`).appendTo("body");
			Delete.$form = $("form", Delete.$modal);
			Delete.$btn = $(".btn-success", Delete.$modal);
			Delete.runFormListener();
		}
		$(".bank-id", Delete.$form).html(`#${Delete.bank.id}`);
		$(".bank-title", Delete.$form).html(Delete.bank.title);
		$(".bank-status", Delete.$form).html(Banks.getStatus(Delete.bank.status));
		Delete.$modal.modal("show");
	}
	protected static closeModal() {
		if (!Delete.$modal.length) {
			return;
		}
		Delete.$btn.prop("disabled", false);
		Delete.$modal.modal("hide");
	}
	protected static runFormListener() {
		Delete.$form.on("submit", function(e) {
			e.preventDefault();
			Delete.$btn.prop("disabled", true);
			$(this).formAjax({
				url: `userpanel/settings/financial/banks/${Delete.bank.id}/delete?ajax=1`,
				success: () => {
					$.growl.warning({
						title: "موفق",
						message: "اطلاعات با موفقیت حذف شد.",
						location: "bl",
					});
					Delete.closeModal();
					Banks.removeBank(Delete.$element);
				},
				error: (data) => {
					Delete.$btn.prop("disabled", false);
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

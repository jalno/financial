import "bootstrap";
import * as $ from "jquery";
import "jquery.growl";
import "webuilder/formAjax";
import { IPay } from "./Edit";

export default class Delete {
	private static $modal: JQuery;
	private pay: IPay;
	public constructor(private $table: JQuery, private DeleteBtns: string) {
		this.appendModal();
		this.setEvents($table);
	}
	public setEvents($container: JQuery) {
		const that = this;
		$(this.DeleteBtns, $container).on("click", function(e) {
			e.preventDefault();
			const $tr = $(this).parents("tr");
			const pay = $tr.data("pay") as IPay;
			if (!pay) {
				$.growl.error({
					title: "خطا",
					message: "اطلاعات پرداخت نامعتبر است.",
				});
				return;
			}
			that.pay = pay;
			that.showModal();
		});
	}
	public deletePay(newpay: IPay) {
		$("tbody tr", this.$table).each(function() {
			const pay = $(this).data("pay") as IPay;
			if (pay && pay.id === newpay.id) {
				$(this).remove();
				return false;
			}
		});
	}
	private showModal() {
		Delete.$modal.modal("show");
	}
	private appendModal() {
		if (Delete.$modal && Delete.$modal.length) {
			return;
		}
		Delete.$modal = $(`<div class="modal fade modal-danger" id="financial-delete-paymenys-modal" tabindex="-1" role="dialog" aria-hidden="false">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h4 class="modal-title">حذف پرداخت</h4>
		</div>
		<div class="modal-body">
			<form id="financial-delete-paymenys-form" method="POST">
				<p>آیا از حذف این پرداخت اطمینان دارید؟</p>
				<p>در حذف پرداخت ها دقت کنید. تمامی اطلاعات پاک خواهند شد و عملیات بدون بازگشت است.</p>
			</form>
		</div>
		<div class="modal-footer">
			<button type="submit" form="financial-delete-paymenys-form" class="btn btn-danger btn-submit">حذف</button>
			<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">انصراف</button>
		</div>
	</div>`).appendTo("body");
		const $btn = $(".btn-submit", Delete.$modal);
		const that = this;
		$("form", Delete.$modal).on("submit", function(e) {
			e.preventDefault();
			$btn.prop("disabled", true);
			$(this).formAjax({
				url: `userpanel/transactions/pay/delete/${that.pay.id}?ajax=1`,
				success: () => {
					$btn.prop("disabled", false);
					that.deletePay(that.pay);
					that.pay = undefined;
					Delete.$modal.modal("hide");
				},
				error: () => {
					$btn.prop("disabled", false);
					$.growl.error({
						title: "خطا",
						message: "ارتباط شما با سامانه به درستی برقرار نشد. لطفا مجددا تلاش کنید.",
					});
				},
			});
		});
	}
}

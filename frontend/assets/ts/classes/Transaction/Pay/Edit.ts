import "@jalno/translator";
import "bootstrap";
import "bootstrap-inputmsg";
import "jalali-daterangepicker";
import * as moment from "jalali-moment";
import $ from "jquery";
import "jquery.growl";
import "webuilder/formAjax";
import { ICurrency } from "../../Currency";

export enum Method {
	CREADIT = 1,
	BANKTRANSFER = 2,
	ONLINE = 3,
	ACCEPTED = 4,
}

export enum Status {
	REJECTED,
	ACCEPTED,
	PENDING,
}

export interface IPay {
	id: number;
	date: number;
	method?: Method;
	description?: string;
	price: number;
	status: Status;
	currency: ICurrency;
}

export default class Edit {
	public static initIfNeeded() {
		Edit.$editBtns = $('body.transaction-edit .table-pays [data-action="edit"]');
		if (Edit.$editBtns.length) {
			Edit.init();
		}
	}
	public static init() {
		const handler = new Edit($("body.transaction-edit .table-pays"), '[data-action="edit"]');
	}
	private static $modal: JQuery;
	private static $editBtns: JQuery;
	private pay: IPay;
	// tslint:disable-next-line: ban-types
	private onupdate: Function;
	public constructor(private $table: JQuery, private editBtns: string) {
		this.appendModal();
		this.setEvents($table);
	}
	public setEvents($container: JQuery) {
		const that = this;
		$(this.editBtns, $container).on("click", function(e) {
			e.preventDefault();
			const $tr = $(this).parents("tr");
			const pay = $tr.data("pay") as IPay;
			if (!pay) {
				$.growl.error({
					title: t("error.fatal.title"),
					message: t("packages.financial.data_validation.pay"),
				});
				return;
			}
			that.pay = pay;
			that.showModal();
		});
	}
	public updatePay(newpay: IPay) {
		if (this.onupdate) {
			this.onupdate(newpay);
		} else {
			const that = this;
			$("tbody tr", this.$table).each(function() {
				const pay = $(this).data("pay") as IPay;
				if (pay && pay.id === newpay.id) {
					if (pay.date !== newpay.date) {
						$("td", this).eq(1).html(moment(newpay.date * 1000).format("YYYY/MM/DD HH:ss"));
					}
					if (pay.description !== newpay.description) {
						$(".pay-description", this).html(newpay.description ? newpay.description.replace(/\n/g, "<br>") : "");
					}
					if (pay.price !== newpay.price) {
						$("td", this).eq(4).html(newpay.price.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,") + " " + newpay.currency.title);
					}
					if (pay.status !== newpay.status) {
						$("td", this).eq(5).html(that.getStatusLabel(newpay.status));
					}
				}
			});
		}
	}
	public getStatusLabel(status: Status) {
		let classes = "";
		switch (status) {
			case Status.REJECTED:
				classes = "label-danger";
				break;
			case Status.ACCEPTED:
				classes = "label-success";
				break;
			case Status.PENDING:
				classes = "label-warning";
				break;
		}
		return `<span class="label ${classes}">${this.getStatusTranslate(status)}</span>`;
	}
	public getStatusTranslate(status: Status) {
		switch (status) {
			case Status.REJECTED:
				return "رد شده";
			case Status.ACCEPTED:
				return "تائید شده";
			case Status.PENDING:
				return "منتظر تائید";
		}
	}
	public onUpdate(cb: Function) {
		this.onupdate = cb;
	}
	private showModal() {
		$("input[name=date]", Edit.$modal).val(moment(this.pay.date * 1000).format("YYYY/MM/DD HH:ss"));
		$("input[name=price]", Edit.$modal).val(this.pay.price.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,"));
		$("textarea[name=description]", Edit.$modal).val(this.pay.description || "");
		$(".input-group-addon", Edit.$modal).html(this.pay.currency.title);
		Edit.$modal.modal("show");
	}
	private appendModal() {
		if (Edit.$modal && Edit.$modal.length) {
			return;
		}
		Edit.$modal = $(`<div class="modal fade" id="financial-edit-paymenys-modal" tabindex="-1" role="dialog" aria-hidden="false">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h4 class="modal-title">${t("packages.financial.edit.pay")}</h4>
		</div>
		<div class="modal-body">
			<form id="financial-edit-paymenys-form" method="POST">
				<div class="form-group">
					<label class="control-label">${t("packages.financial.date")}</label>
					<input type="text" value="" name="date" required="" class="form-control ltr">
				</div>
				<div class="form-group">
					<label class="control-label">${t("transaction.price")}</label>
					<div class="input-group">
						<input type="text" value="" required="" name="price" class="form-control ltr">
						<span class="input-group-addon"></span>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label">${t("description")}</label>
					<textarea name="description" rows="4" class="form-control"></textarea>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button type="submit" form="financial-edit-paymenys-form" class="btn btn-teal btn-submit">${t("packages.financial.edit")}</button>
			<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true">${t("cancel")}</button>
		</div>
	</div>`).appendTo("body");
		moment.locale(Translator.getActiveShortLang());
		const config: any = {
			showDropdowns: true,
			singleDatePicker: true,
		};
		if (Translator.getActiveShortLang() === "fa") {
			config.locale = {
				format: "YYYY/MM/DD HH:ss",
				monthNames: (moment.localeData() as any)._jMonthsShort,
				firstDay: 6,
				direction: "rtl",
				separator: " - ",
				applyLabel: t("packages.financial.action"),
				cancelLabel: t("cancel"),
			};
		}
		$("input[name=date]", Edit.$modal).daterangepicker(config);
		$("input[name=price]", Edit.$modal).on("keyup", function() {
			const val = ($(this).val() as string);
			if (!val.length) {
				return;
			}
			const price = parseFloat(val.replace(/\,/g, ""));
			if (isNaN(price)) {
				$(this).val("");
			} else {
				$(this).val(price.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,"));
			}
		});
		const $btn = $(".btn-submit", Edit.$modal);
		const that = this;
		$("form", Edit.$modal).on("submit", function(e) {
			e.preventDefault();
			const form = this as HTMLFormElement;
			$btn.prop("disabled", true);
			const data = {};
			for (const item of $(form).serializeArray()) {
				if (item.name === "price") {
					item.value = item.value.replace(/\,/g, "");
				}
				data[item.name] = item.value;
			}
			$(this).formAjax({
				url: `userpanel/transactions/pay/${that.pay.id}/edit?ajax=1`,
				data,
				success: (response) => {
					$btn.prop("disabled", false);
					that.updatePay(response.pay);
					form.reset();
					that.pay = undefined;
					Edit.$modal.modal("hide");
				},
				error: (response) => {
					$btn.prop("disabled", false);
					if (response.error === "data_duplicate" || response.error === "data_validation") {
						const $input = $(`[name="${response.input}"]`, form);
						const $params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${response.error}`),
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
}

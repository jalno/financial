import "@jalno/translator";
import "bootstrap";
import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import { Router , webuilder } from "webuilder";
import "../jquery.financialUserAutoComplete";
import Transaction from "../Transaction";
export default class Edit {
	public static init() {
		if ($("input[name=user_name]", Edit.$form).length) {
			Edit.runUserSearch();
		}
		Edit.productEditListener();
		Edit.ModalSubmitListener();
		Edit.runSubmitFormListener();
		Edit.ADDproductListener();
		Edit.runNumberFormatListener();
	}
	public static initIfNeeded() {
		if (Edit.$form.length) {
			Edit.init();
		}
	}
	private static $form = $("body.transaction-edit .create_form");
	private static table = $(".product-table", Edit.$form);
	private static productEdit: string = '<a class="btn btn-xs btn-teal product-edit" href="#product-edit" data-toggle="modal" data-original-title=""><i class="fa fa-edit"></i></a>';
	private static runUserSearch() {
		$("input[name=user_name]", Edit.$form).financialUserAutoComplete();
	}
	private static productEditListener() {
		$(".product-table .product-edit").on("click", function() {
			const tr = $(this).parents("tr");
			const product = tr.data("product");
			const ModalEditProduct = $("#product-edit");
			$("input[name=product]", ModalEditProduct).val(product.id);
			$("input[name=product_title]", ModalEditProduct).val(product.title);
			$("textarea[name=description]", ModalEditProduct).val(product.description);
			$("input[name=number]", ModalEditProduct).val(product.number);
			$("input[name=product_price]", ModalEditProduct).val(Transaction.formatFloatNumber(product.price));
			$("input[name=discount]", ModalEditProduct).val(Transaction.formatFloatNumber(product.discount));
			$("input[name=vat]", ModalEditProduct).val(product.vat);
			$("select[name=product_currency]", ModalEditProduct).val(product.currency);
			$("#editproductform", ModalEditProduct).data("tr", tr);
		});
	}	private static RmoveProduct() {
		$(".delete").on("click", function(e) {
			e.preventDefault();
			$(this).parents("tr").remove();
			$("tbody > tr", Edit.table).each(function(i) {
				$("td:first-child", this).html((i + 1).toString());
			});
		});
	}
	private static rebuildProductsTable(items?: Object[]) {
		let $trs = "";
		if (items) {
			let index = 0;
			for (const item of items) {
				$trs += Edit.buildProductRow(item, ++index);
			}
		} else {
			$("tbody > tr", Edit.table).each(function(index) {
				$trs += Edit.buildProductRow($(this).data("product"), index + 1);
			});
		}
		$("tbody", Edit.table).html($trs);
		Edit.RmoveProduct();
		Edit.productEditListener();
	}
	private static buildProductRow(product, index: number) {
		if (!product.number) {
			product.number = 1;
		}
		if (!product.price || isNaN(product.price)) {
			product.price = 0;
		}
		if (!product.discount  || isNaN(product.price)) {
			product.discount = 0;
		}

		product.vat = product.vat || 0;

		const price = ((product.price * product.number) - product.discount);

		const finalPrice = price + ((price * product.vat) / 100);

		let code = `
			<tr data-product='${JSON.stringify(product)}'>
				<td>${index}</td>
				<td>${product.title}</td>
				<td>${product.description}</td>
				<td>${product.number} عدد</td>
				<td>${Transaction.formatFloatNumber(product.price)} ${product.currency_title}</td>
				<td>${Transaction.formatFloatNumber(product.discount)} ${product.currency_title}</td>
				<td>${product.vat} %</td>
				<td>${Transaction.formatFloatNumber(finalPrice)} ${product.currency_title}</td>
				<td class="center">
		`;
		let className: string = "";
		let link = "";
		if (product?.id) {
			link = Router.url("userpanel/transactions/product/delete/" + product.id);
			code += Edit.productEdit + " ";
		} else {
			link = "#";
			className = "delete";
		}
		code += '<a href="' + link + '" class="btn btn-xs btn-bricky product-delete ' + className + '" title="حذف"><i class="fa fa-times fa fa-white"></i></a></td></tr>';

		return code;
	}
	private static ModalSubmitListener() {
		$("#editproductform").on("submit", function(e) {
			e.preventDefault();
			if ($("input[name=product_title]", this).val() === "" || $("input[name=product_price]", this).val() === "") {
				$.growl.error({
					title: t("error.fatal.title"),
					message: t("packages.financial.data_validation.products.price_or_title_is_not_defined"),
				});
				return;
			}
			$(this).parents(".modal").modal("hide");
			const newdata = {
				id: $("input[name=product]", this).val(),
				title: $("input[name=product_title]", this).val(),
				description: $("textarea[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: parseFloat(Transaction.deFormatNumber($("input[name=product_price]", this).val())),
				discount: parseFloat(Transaction.deFormatNumber($("input[name=discount]", this).val())),
				vat: $("input[name=vat]", this).val() || 0,
				currency: $("select[name=product_currency] option:selected", this).val(),
				currency_title: $("select[name=product_currency] option:selected", this).data("title"),
			};
			const $tr = $(this).data("tr");
			$tr.data("product", newdata);
			Edit.rebuildProductsTable();
		});
	}
	private static serialize() {
		const info = {
			title: $("input[name=title]", Edit.$form).val(),
			user: $("input[name=user]", Edit.$form).val(),
			create_at: $("input[name=create_at]", Edit.$form).val(),
			expire_at: $("input[name=expire_at]", Edit.$form).val(),
			products: [],
		};
		$("tbody > tr", Edit.table).each(function() {
			info.products.push($(this).data("product"));
		});
		return info;
	}
	private static ADDproductListener() {
		$("#addproductform").on("submit", function(e) {
			e.preventDefault();
			if ($("input[name=product_title]", this).val() === "" || $("input[name=product_price]", this).val() === "") {
				$.growl.error({
					title: t("error.fatal.title"),
					message: t("packages.financial.data_validation.products.price_or_title_is_not_defined"),
				});
				return;
			}
			$(this).parents(".modal").modal("hide");
			const newdata = {
				title: $("input[name=product_title]", this).val(),
				description: $("textarea[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: parseFloat(Transaction.deFormatNumber($("input[name=product_price]", this).val())),
				discount: parseFloat(Transaction.deFormatNumber($("input[name=discount]", this).val())),
				vat: parseFloat(Transaction.deFormatNumber($("input[name=vat]", this).val())),
				currency: $("select[name=product_currency] option:selected", this).val(),
				currency_title: $("select[name=product_currency] option:selected", this).data("title"),
			};
			const $tr = $("<tr></tr>").appendTo($("tbody", Edit.table));
			$tr.data("product", newdata);
			Edit.rebuildProductsTable();
		});
	}
	private static runSubmitFormListener() {
		const $btn = $('.btn-submit', Edit.$form);
		const $icon = $('.fa', $btn);
		Edit.$form.on("submit", async function(e) {
			e.preventDefault();
			const preSubmitEvent = $.Event('pre_submit');
			const preSubmitHandlers = $(this).data('pre_submit');

			if (preSubmitHandlers) {
				$btn.prop('disabled', true);
				$icon.removeClass('fa-check-square-o').addClass('fa-spinner fa-spin');
				for (const handler of preSubmitHandlers) {
					await handler(preSubmitEvent);
				}
			}

			if (preSubmitEvent.isDefaultPrevented()) {
				$btn.prop('disabled', false);
				$icon.attr('class', 'fa fa-check-square-o');
				return;
			}

			const form = this as HTMLFormElement;
			$(form).formAjax({
				data: Edit.serialize(),
				success: async(response) => {
					$icon.attr('class', 'fa fa-check-square-o');
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});

					const postSubmitEvent = $.Event('post_submit');
					const postSubmitHandlers = $(this).data('post_submit');

					if (postSubmitHandlers) {
						$btn.prop('disabled', true);
						$icon.removeClass('fa-check-square-o').addClass('fa-spinner fa-spin');
						for (const handler of postSubmitHandlers) {
							await handler(postSubmitEvent, response.transaction);
						}
					}

					if (postSubmitEvent.isDefaultPrevented()) {
						return;
					}

					Edit.rebuildProductsTable(response.products);
				},
				error: (error: webuilder.AjaxError) => {
					$icon.attr('class', 'fa fa-check-square-o');
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`, form);
						const $params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${error.error}`),
						};
						if ($input.length) {
							$input.inputMsg($params);
						} else {
							$.growl.error($params);
						}
					} else if (error.hasOwnProperty("type") && error.type === "fatal") {
						const ErrorHtml = `
							<div class="alert alert-block alert-danger ">
								<button data-dismiss="alert" class="close" type="button">&times;</button>
								<h4 class="alert-heading"><i class="fa fa-times-circle"></i> خطا</h4>
								<p>${error.message}</p>
							</div>
						`;
						if (!$(".errors .currencyError").length) {
							$(".errors").append('<div class="currencyError"></div>');
						}
						$(".errors .currencyError").html(ErrorHtml);
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
	private static runNumberFormatListener() {
		$("#addproductform input[name=product_price], #addproductform input[name=discount], #editproductform input[name=product_price], #editproductform input[name=discount]").on("keyup", function(e) {
			let val = Transaction.deFormatNumber($(this).val() as string);
			const isDot = e.keyCode === 110;
			const number = parseInt(val, 10);
			if (isNaN(number)) {
				$(this).val(isDot ? "0." : "");
				return;
			}
			val = Transaction.formatFloatNumber(parseFloat(val));
			if (isDot) {
				val += ".";
			}
			$(this).val(val);
		});
	}
}

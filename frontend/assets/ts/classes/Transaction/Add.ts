import "@jalno/translator";
import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import { webuilder } from "webuilder";
import "../jquery.financialUserAutoComplete";
import Transaction from "../Transaction";

export default class Add {
	public static init() {
		if ($("input[name=user_name]", Add.$form).length) {
			Add.runUserSearch();
		}
		Add.transactionProduct();
		Add.runNumberFormatListener();
		Add.runSubmitFormListener();
	}
	public static initIfNeeded() {
		if (Add.$form.length) {
			Add.init();
		}
	}
	private static $form = $("body.transaction-add .create_form");
	private static runUserSearch() {
		$("input[name=user_name]", Add.$form).financialUserAutoComplete();
	}
	private static getProductsCode() {
		const code = '<table class="table table-striped table-hover product-table"> <thead> <tr> <th> # </th> <th> محصول </th> <th class="hidden-480"> توضیحات </th> <th class="hidden-480"> تعداد </th> <th class="hidden-480"> قیمت واحد </th> <th>تخفیف</th> <th> قیمت نهایی </th> <th></th> </tr> </thead> <tbody> </tbody></table> ';
		const $table = $(".products > table");
		if ($table.length) {
			return $table;
		} else {
			return $(code).appendTo(".products");
		}
	}
	private static addProductTocode(product) {
		// product.title, product.price, product.number, product.discount, product.desc, product.currency, product.currency_title
		if (!product.number.length) {
			product.number = 1;
		}
		if (!product.price || isNaN(product.price)) {
			product.price = 0;
		}
		if (!product.discount || isNaN(product.discount)) {
			product.discount = 0;
		}
		const finalPrice = (product.number * product.price) - product.discount;
		const $table = Add.getProductsCode();
		const id = $("tr", $table).length;
		const code = `
			<tr>
				<td>${id}</td>
				<td>${product.title}</td>
				<td>${product.description}</td>
				<td>${t("product.xnumber", {number: product.number})}</td>
				<td>${Transaction.formatFlotNumber(product.price)} ${product.currency_title}</td>
				<td>${Transaction.formatFlotNumber(product.discount)} ${product.currency_title}</td>
				<td>${Transaction.formatFlotNumber(finalPrice)} ${product.currency_title}</td> <td><a href="#" class="btn btn-xs btn-bricky btn-remove tooltips" title="${t("delete")}"><i class="fa fa-times fa fa-white"></i></a></td>
			</tr>
		`;
		const $row = $(code).appendTo($("tbody", $table));
		$row.data(product);
		$(".btn-remove", $row ).click(Add.productRemove);
		$table.css("display", "table");
	}
	private static productRemove(e) {
		e.preventDefault();
		const $table = $(this).parents("table").first();
		$(this).parents("tr").remove();
		if (!$("tbody > tr", $table).length) {
			$(".product-table").hide();
			$(".no-product").show();
		} else {
			Add.resetId();
		}
	}
	private static resetId() {
		$(".product-table tbody > tr").each(function(i: number) {
			$("td:first-child", this).html((i + 1).toString());
		});
	}
	private static serialize() {
		const info = {
			title: $("input[name=title]", Add.$form).val(),
			user: $("input[name=user]", Add.$form).val(),
			create_at: $("input[name=create_at]", Add.$form).val(),
			expire_at: $("input[name=expire_at]", Add.$form).val(),
			notification: $("input[name=notification]", Add.$form).prop("checked"),
			notification_support: $("input[name=notification_support]", Add.$form).prop("checked"),
			products: [],
		};
		$(".product-table tbody > tr").each(function() {
			info.products.push($(this).data());
		});
		return info;
	}
	private static runSubmitFormListener() {
		Add.$form.on("submit", function(e) {
			e.preventDefault();
			$(this).formAjax({
				data: Add.serialize(),
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					window.location.href = data.redirect;
				},
				error: (error: webuilder.AjaxError) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`);
						const $params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${error.error}`),
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
	private static transactionProduct() {
		$("#addproductform").on("submit", function(e) {
			e.preventDefault();
			const product = {
				title: $("input[name=product_title]", this).val(),
				description: $("input[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: parseFloat(Transaction.deFormatNumber($("input[name=price]", this).val())),
				discount: parseFloat(Transaction.deFormatNumber($("input[name=discount]", this).val())),
				currency: $("select[name=currency] option:selected", this).val(),
				currency_title: $("select[name=currency] option:selected", this).data("title"),
			};
			$(this).parents(".modal").modal("hide");
			$(".no-product").hide();
			Add.addProductTocode(product);
		});
	}
	private static runNumberFormatListener() {
		$("#addproductform input[name=price], #addproductform input[name=discount]").on("keyup", function(e) {
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

import * as $ from "jquery";
import "../jquery.financialUserAutoComplete";
import "bootstrap";
import { Router , webuilder } from "webuilder";
import "jquery.growl";
import "bootstrap-inputmsg";
export default class Edit{
	private static $form = $('body.transaction-edit .create_form');
	private static table = $(".product-table", Edit.$form);
	private static productEdit:string = '<a class="btn btn-xs btn-teal product-edit" href="#product-edit" data-toggle="modal" data-original-title=""><i class="fa fa-edit"></i></a>';
	private static runUserSearch(){
		$('input[name=user_name]', Edit.$form).financialUserAutoComplete();
	}
	private static productEditListener(){
		$(".product-table .product-edit").on('click', function(){
			const tr = $(this).parents("tr");
			const product = tr.data('product');
			const ModalEditProduct = $("#product-edit");
			$("input[name=product]", ModalEditProduct).val(product.id);
			$("input[name=product_title]", ModalEditProduct).val(product.title);
			$("textarea[name=description]", ModalEditProduct).val(product.description);
			$("input[name=number]", ModalEditProduct).val(product.number);
			$("input[name=product_price]", ModalEditProduct).val(product.price);
			$("input[name=discount]", ModalEditProduct).val(product.discount);
			$("select[name=product_currency]", ModalEditProduct).val(product.currency);
			$("#editproductform", ModalEditProduct).data("tr", tr);
		});
	};
	private static RmoveProduct(){
		$(".delete").on('click', function(e){
			e.preventDefault();
			$(this).parents('tr').remove();
			$("tbody > tr", Edit.table).each(function(i){
				$("td:first-child", this).html((i + 1).toString());
			});
		});
	}
	private static rebuildProductsTable(){
		let $trs = '';
		$("tbody > tr", Edit.table).each(function(index){
			$trs += Edit.buildProductRow($(this).data('product'), index+1);
		});
		$('tbody', Edit.table).html($trs);
		Edit.RmoveProduct();
		Edit.productEditListener();
	}
	private static buildProductRow(product, index: number){
		if(!product.number){
			product.number = 1;
		}
		if(!product.price){
			product.price = 0;
		}
		if(!product.discount){
			product.discount = 0;
		}
		const finalPrice = ((product.price*product.number)-product.discount);
		let code = `
			<tr data-product='${JSON.stringify(product)}'>
				<td>${index}</td>
				<td>${product.title}</td>
				<td>${product.description}</td>
				<td>${product.number} عدد</td>
				<td>${product.price} ${product.currency_title}</td>
				<td>${product.discount} ${product.currency_title}</td>
				<td>${finalPrice} ${product.currency_title}</td>
				<td class="center">
		`;
		let className:string = '';
		let link = "";
		if(product.id){
			link = Router.url("userpanel/transactions/product/delete/" + product.id);
			code += Edit.productEdit + ' ';
		}else{
			link = '#';
			className = 'delete';
		}
		code += '<a href="' + link + '" class="btn btn-xs btn-bricky product-delete ' + className + '" title="حذف"><i class="fa fa-times fa fa-white"></i></a></td></tr>';

		return code;
	}
	private static ModalSubmitListener(){
		$("#editproductform").on('submit', function(e){
			e.preventDefault();
			if ($("input[name=product_title]", this).val() === "" || $("input[name=product_price]", this).val() === "") {
				$.growl.error({
					title:"خطا",
					message:"نام محصول و یا مبلغ محصول مشخص نشده است"
				});
				return;
			}
			$(this).parents(".modal").modal("hide");
			const newdata = {
				id: $("input[name=product]", this).val(),
				title: $("input[name=product_title]", this).val(),
				description: $("textarea[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=product_price]", this).val(),
				discount: $("input[name=discount]", this).val(),
				currency: $("select[name=product_currency] option:selected", this).val(),
				currency_title: $("select[name=product_currency] option:selected", this).data('title')
			}
			const $tr = $(this).data("tr");
			$tr.data('product', newdata);
			Edit.rebuildProductsTable();
		});
	}
	private static serialize(){
		let info = {
			title: $('input[name=title]', Edit.$form).val(),
			user: $('input[name=user]', Edit.$form).val(),
			create_at: $('input[name=create_at]', Edit.$form).val(),
			expire_at: $('input[name=expire_at]', Edit.$form).val(),
			products: []
		}
		$("tbody > tr", Edit.table).each(function(){
			info.products.push($(this).data('product'));
		});
		return info;
	}
	private static ADDproductListener() {
		$("#addproductform").on('submit', function(e){
			e.preventDefault();
			if ($("input[name=product_title]", this).val() === "" || $("input[name=product_price]", this).val() === "") {
				$.growl.error({
					title:"خطا",
					message:"نام محصول و یا مبلغ محصول مشخص نشده است"
				});
				return;
			}
			$(this).parents(".modal").modal("hide");
			const newdata = {
				title: $("input[name=product_title]", this).val(),
				description: $("textarea[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=product_price]", this).val(),
				discount: $("input[name=discount]", this).val(),
				currency: $("select[name=product_currency] option:selected", this).val(),
				currency_title: $("select[name=product_currency] option:selected", this).data('title')
			}
			let $tr = $('<tr></tr>').appendTo($("tbody", Edit.table));
			$tr.data('product', newdata);
			Edit.rebuildProductsTable();
		});
	}
	private static runSubmitFormListener(){
		Edit.$form.on('submit', function(e){
			e.preventDefault();
			$(this).formAjax({
				data: Edit.serialize(),
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title:"موفق",
						message:"انجام شد ."
					});
				},
				error: function(error:webuilder.AjaxError){
					if(error.error == 'data_duplicate' || error.error == 'data_validation'){
						let $input = $('[name='+error.input+']');
						let $params = {
							title: 'خطا',
							message:''
						};
						if(error.error == 'data_validation'){
							$params.message = 'داده وارد شده معتبر نیست';
						}
						if($input.length){
							$input.inputMsg($params);
						}else{
							$.growl.error($params);
						}
					}else if(error.hasOwnProperty('type') && error.type == 'fatal'){
						const ErrorHtml = `
							<div class="alert alert-block alert-danger ">
								<button data-dismiss="alert" class="close" type="button">&times;</button>
								<h4 class="alert-heading"><i class="fa fa-times-circle"></i> خطا</h4>
								<p>${error.message}</p>
							</div>
						`;
						if(!$('.errors .currencyError').length){
							$('.errors').append('<div class="currencyError"></div>');
						}
						$('.errors .currencyError').html(ErrorHtml);
					}else{
						$.growl.error({
							title:"خطا",
							message:'درخواست شما توسط سرور قبول نشد'
						});
					}
				}
			});
		});
	}
	public static init(){
		if($('input[name=user_name]', Edit.$form).length){
			Edit.runUserSearch();
		}
		Edit.productEditListener();
		Edit.ModalSubmitListener();
		Edit.runSubmitFormListener();
		Edit.ADDproductListener();
	}
	public static initIfNeeded(){
		if(Edit.$form.length){
			Edit.init();
		}
	}
}
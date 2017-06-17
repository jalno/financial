import * as $ from "jquery";
import "../jquery.userAutoComplete";
import "bootstrap";
import { Router , webuilder } from "webuilder";
import "jquery.growl";
import "bootstrap-inputmsg";
export default class Edit{
	private static $form = $('body.transaction-edit .create_form');
	private static table = $(".product-table", Edit.$form);
	private static productEdit:string = '<a class="btn btn-xs btn-teal product-edit" href="#product-edit" data-toggle="modal" data-original-title=""><i class="fa fa-edit"></i></a>';
	private static link:string;
	private static productId:number;
	private static runUserSearch(){
		$('input[name=user_name]', Edit.$form).userAutoComplete();
	}
	private static productEditListener(){
		$(".product-table .product-edit").on('click', function(){
			let tr = $(this).parents("tr");
			let product = tr.data('product');
			let ModalEditProduct = $("#product-edit");
			$("input[name=product]", ModalEditProduct).val(product.id);
			$("input[name=product_title]", ModalEditProduct).val(product.title);
			$("textarea[name=description]", ModalEditProduct).val(product.description);
			$("input[name=number]", ModalEditProduct).val(product.number);
			$("input[name=product_price]", ModalEditProduct).val(product.price);
			$("input[name=discount]", ModalEditProduct).val(product.discount);
			Edit.link = $(".product-delete").attr("href");
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
	private static rebuildProductsTable = function(){
		let $trs = '';
		Edit.productId = 0;
		$("tbody > tr", Edit.table).each(function(){
			Edit.productId++;
			$trs += Edit.buildProductRow($(this).data('product'));
		});
		$('tbody', Edit.table).html($trs);
		Edit.RmoveProduct();
		Edit.productEditListener();
	}
	private static buildProductRow(product){
		if(!product.number){
			product.number = 1;
		}
		if(!product.price){
			product.price = 0;
		}
		if(!product.discount){
			product.discount = 0;
		}
		var finalPrice = ((product.price*product.number)-product.discount);
		var code = '<tr data-product=\''+JSON.stringify(product)+'\'>';
		code += '<td>'+(Edit.productId)+'</td>';
		code += '<td>'+product.title+'</td>';
		code += '<td>'+product.description+'</td>';
		code += '<td>'+product.number+' عدد</td>';
		code += '<td>'+product.price+' ریال</td>';
		code += '<td>'+product.discount+' ریال</td>';
		code += '<td>'+finalPrice+' ریال</td> <td class="center">';
		let clas:string = '';
		if(product.id){
			Edit.link = Router.url("userpanel/transactions/product/delete/" + product.id);
			code += Edit.productEdit + ' ';
		}else{
			Edit.link = '#';
			clas = 'delete';
		}
		code += '<a href="' + Edit.link + '" class="btn btn-xs btn-bricky product-delete ' + clas + '" title="حذف"><i class="fa fa-times fa fa-white"></i></a></td></tr>';

		return code;
	}
	private static ModalSubmitListener(){
		$("#editproductform").on('submit', function(e){
			e.preventDefault();
			$(this).parents(".modal").modal("hide");
			let newdata = {
				id: $("input[name=product]", this).val(),
				title: $("input[name=product_title]", this).val(),
				description: $("textarea[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=product_price]", this).val(),
				discount: $("input[name=discount]", this).val()
			}
			console.log(this);
			$("tbody > tr", Edit.table).each(function(){
				$(this).data('product', newdata);
				return false;

			});
			Edit.rebuildProductsTable();
		});
	}
	private static serialize(){
		var info = {
			title: $('input[name=title]', Edit.$form).val(),
			user: $('input[name=user]', Edit.$form).val(),
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
			$(this).parents(".modal").modal("hide");
			let newdata = {
				title: $("input[name=product_title]", this).val(),
				description: $("input[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=product_price]", this).val(),
				discount: $("input[name=discount]", this).val()
			}
			let $tr = $('<tr></tr>').appendTo($("tbody", Edit.table));
			$tr.data('product', newdata);
			Edit.rebuildProductsTable();
		});
	}
	private static runSubmitFormListener = function(){
		Edit.$form.on('submit', function(e){
			e.preventDefault();
			$(this).formAjax({
				data: Edit.serialize(),
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title:"موفق",
						message:"انجام شد ."
					});
					window.location.href = data.redirect;
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
import * as $ from "jquery";
import "../jquery.userAutoComplete";
import { webuilder } from "webuilder";
import "jquery.growl";
import "bootstrap-inputmsg";
export default class Add{
	private static $form = $('body.transaction-add .create_form');
	private static runUserSearch(){
		$('input[name=user_name]', Add.$form).userAutoComplete();
	}
	private static getProductsCode(){
		let code = '<table class="table table-striped table-hover product-table"> <thead> <tr> <th> # </th> <th> محصول </th> <th class="hidden-480"> توضیحات </th> <th class="hidden-480"> تعداد </th> <th class="hidden-480"> قیمت واحد </th> <th>تخفیف</th> <th> قیمت نهایی </th> <th></th> </tr> </thead> <tbody> </tbody></table> ';
		let $table = $('.products > table');
		if($table.length){
			return $table;
		}else{
			return $(code).appendTo('.products');
		}
	}
	private static addProductTocode(product){
		//product.title, product.price, product.number, product.discount, product.desc
		if(!product.number.length){
			product.number = 1;
		}
		if(!product.price.length){
			product.price = 0;
		}
		if(!product.discount.length){
			product.discount = 0;
		}
		let finalPrice = (product.number*product.price)-product.discount;
		let $table = Add.getProductsCode();
		let id = $('tr', $table).length;
		let code = '<tr>';
		code += '<td>'+id+'</td>';
		code += '<td>'+product.title+'</td>';
		code += '<td>'+product.description+'</td>';
		code += '<td>'+product.number+' عدد</td>';
		code += '<td>'+product.price+' ریال</td>';
		code += '<td>'+product.discount+' ریال</td>';
		code += '<td>'+finalPrice+' ریال</td> <td><a href="#" class="btn btn-xs btn-bricky btn-remove tooltips" title="حذف"><i class="fa fa-times fa fa-white"></i></a></td> </tr>';
		let $row = $(code).appendTo($('tbody', $table));
		$row.data(product);
		$(".btn-remove", $row ).click(Add.productRemove);
		$table.css("display", "table");
	}
	private static productRemove(e){
		e.preventDefault();
		let $table = $(this).parents('table').first();
		$(this).parents('tr').remove();
		if(!$('tbody > tr', $table).length){
			$(".product-table").hide();
			$(".no-product").show();
		}else{
			Add.resetId();
		}
	}
	private static resetId(){
		$(".product-table tbody > tr").each(function(i:number){
			$("td:first-child", this).html((i + 1).toString());
		});
	}
	private static serialize(){
		let info = {
			title: $('input[name=title]', Add.$form).val(),
			user: $('input[name=user]', Add.$form).val(),
			create_at: $('input[name=create_at]', Add.$form).val(),
			expire_at: $('input[name=expire_at]', Add.$form).val(),
			notification: $('input[name=notification]', Add.$form).val(),
			notification_support: $('input[name=expire_at]', Add.$form).val(),
			products: []
		}
		$(".product-table tbody > tr").each(function(){
			info.products.push($(this).data());
		});
		return info;
	}
	private static runSubmitFormListener(){
		Add.$form.on('submit', function(e){
			e.preventDefault();
			$(this).formAjax({
				data: Add.serialize(),
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title:"موفق",
						message:"انجام شد ."
					});
					window.location.href = data.redirect
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
	private static transactionProduct() {
		$('#addproductform').on('submit', function(e){
			e.preventDefault();
			var product = {
				title: $('input[name=product_title]', this).val(),
				description: $('input[name=description]', this).val(),
				number: $('input[name=number]', this).val(),
				price: $('input[name=price]', this).val(),
				discount: $('input[name=discount]', this).val()
			}
			$(this).parents(".modal").modal("hide");
			$(".no-product").hide();
			Add.addProductTocode(product);
		});
	}
	public static init(){
		if($('input[name=user_name]', Add.$form).length){
			Add.runUserSearch();
		}
		Add.transactionProduct();
		Add.runSubmitFormListener();
	}
	public static initIfNeeded(){
		if(Add.$form.length){
			Add.init();
		}
	}
}
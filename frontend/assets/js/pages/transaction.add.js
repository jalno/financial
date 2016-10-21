var TransactionAdd = function () {
	var form = $('.create_form');
	var runUserListener = function(){
		$("input[name=user_name]", form).autocomplete({
			source: function( request, response ) {
				$.ajax({
					url: "/fa/userpanel/users",
					dataType: "json",
					data: {
						ajax:1,
						word: request.term
					},
					success: function( data ) {
						if(data.hasOwnProperty('status')){
							if(data.status){
								if(data.hasOwnProperty('items')){
									response( data.items );
								}
							}
						}

					}
				});
			},
			select: function( event, ui ) {
				$(this).val(ui.item.name+(ui.item.lastname ? ' '+ui.item.lastname : ''));
				$('input[name=user]', form).val(ui.item.id);
				return false;
			},
			focus: function( event, ui ) {
				$(this).val(ui.item.name+(ui.item.lastname ? ' '+ui.item.lastname : ''));
				$('input[name=user]', form).val(ui.item.id);
				return false;
			}
		}).autocomplete( "instance" )._renderItem = function( ul, item ) {
			return $( "<li>" )
				.append( "<strong>" + item.name+(item.lastname ? ' '+item.lastname : '')+ "</strong><small class=\"ltr\">"+item.email+"</small><small class=\"ltr\">"+item.cellphone+"</small>" )
				.appendTo( ul );
		};
	};
	var getProductscode = function(){
		var code = '<table class="table table-striped table-hover product-table"> <thead> <tr> <th> # </th> <th> محصول </th> <th class="hidden-480"> توضیحات </th> <th class="hidden-480"> تعداد </th> <th class="hidden-480"> قیمت واحد </th> <th>تخفیف</th> <th> قیمت نهایی </th> <th></th> </tr> </thead> <tbody> </tbody></table> ';
		var $table = $('.products > table');
		if($table.length){
			return $table;
		}else{
			return $(code).appendTo('.products');
		}
	}
	var addProductTocode = function(product){
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
		var finalPrice = (product.number*product.price)-product.discount;
		var $table = getProductscode();
		var id = $('tr', $table).length;
		var code = '<tr>';
		code += '<td>'+id+'</td>';
		code += '<td>'+product.title+'</td>';
		code += '<td>'+product.description+'</td>';
		code += '<td>'+product.number+' عدد</td>';
		code += '<td>'+product.price+' ریال</td>';
		code += '<td>'+product.discount+' ریال</td>';
		code += '<td>'+finalPrice+' ریال</td> <td><a href="#" class="btn btn-xs btn-bricky btn-remove tooltips" title="حذف"><i class="fa fa-times fa fa-white"></i></a></td> </tr>';
		var $row = $(code).appendTo($('tbody', $table));
		$row.data(product);
		$(".btn-remove", $row ).click(productRemove);
		$table.css("display", "table");
	}
	var transactionProduct = function(){
		$('#addproductform').submit(function(e){
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
			addProductTocode(product);
		});
	};
	var productRemove = function(e){
		e.preventDefault();
		var $table = $(this).parents('table').first();
		$(this).parents('tr').remove();
		if(!$('tbody > tr', $table).length){
			$(".product-table").hide();
			$(".no-product").show();
		}else{
			resetId();
		}
	};
	var resetId = function(){
		$(".product-table tbody > tr").each(function(i){
			$("td:first-child", this).html(i+1);
		});
	}
	var serialize = function(){

		var info = {
			title: $('input[name=title]', form).val(),
			user: $('input[name=user]', form).val(),
			create_at: $('input[name=create_at]', form).val(),
			expire_at: $('input[name=expire_at]', form).val(),
			notification: $('input[name=notification]', form).val(),
			notification_support: $('input[name=expire_at]', form).val(),
			products: []
		}
		$(".product-table tbody > tr").each(function(){
			info.products.push($(this).data());
		});

		return info;
	}
	var runSubmitFormListener = function(){
		$('.create_form').submit(function(e){
			e.preventDefault();
			$("btn-submit").prop('disabled', false);
			if(serialize().user.length){
				if(serialize().products.length){
					$.ajax({
						url: Main.getAjaxFormURL($(form).attr('action')),
						type:"post",
						dataType: "json",
						data: serialize(),
						success: function( data ) {
							if(data.hasOwnProperty('status')){
								if(data.status){
									if(data.hasOwnProperty('redirect')){
										window.location.href = data.redirect;
									}
								}else{
									if(data.hasOwnProperty('error')){
										for(var i =0;i!=data.error.length;i++){
											var error = data.error[i];
											var $input = $('[name='+error.input+']');
											var $params = {
												title: 'خطا'
											};
											if(error.error == 'data_validation'){
												$params.message = 'داده وارد شده معتبر نیست';
											}
											if($input.length){
												$input.inputMsg($params);
											}
											if(error.input == 'product'){
												$.growl.error({title: 'خطا', message: 'محصولی وارد نشده'});
											}else if(error.input == 'product_title'){
												$.growl.error({title: 'خطا', message: 'محصول مشخص نیست'});
											}else if(error.input == 'price'){
												$.growl.error({title: 'خطا', message: 'قیمتی برای محصولات مشخص نیست'});
											}else{
												$.growl.error($params);
											}
										}

									}else{
										$.growl.error({title: 'خطا', message: 'درخواست شما توسط سرور قبول نشد'});
									}
								}
							}

						}
					});
				}else{
					$.growl.error({title: 'خطا', message: 'محصولی وارد نشده'});
				}
			}else{
				$.growl.error({title: 'خطا', message: 'کاربری مشخص نشده'});
			}
		});
	};
	return {
		init: function() {
			runUserListener();
			transactionProduct();
			runSubmitFormListener();
		}
	}
}();
$(function(){
	TransactionAdd.init();
});

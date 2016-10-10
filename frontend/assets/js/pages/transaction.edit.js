var TransactionEdit = function () {
	var form = $('.create_form');
	var ModalEditProduct = $("#product-edit");
	var ModalForm = $("#editproductform");
	var productADD = ("#addproductform");
	var table = $(".product-table");

	var productEdit = '<a class="btn btn-xs btn-warning product-edit" href="#product-edit" data-toggle="modal" data-original-title=""><i class="fa fa-edit"></i></a>';
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
	var link = '';
	var productEditListener = function(){
		$(".product-table .product-edit").click(function(){
			var tr = $(this).parents("tr");
			var product = tr.data('product');
			$("input[name=product]", ModalEditProduct).val(product.id);
			$("input[name=title]", ModalEditProduct).val(product.title);
			$("input[name=description]", ModalEditProduct).val(product.description);
			$("input[name=number]", ModalEditProduct).val(product.number);
			$("input[name=price]", ModalEditProduct).val(product.price);
			$("input[name=discount]", ModalEditProduct).val(product.discount);
			link = $(".product-delete").attr("href");
		});
	};
	var productId = 0;
	var ModalSubmitListener = function(){
		ModalForm.submit(function(e){
			e.preventDefault();
			$(this).parents(".modal").modal("hide");
			var newdata = {
				id: $("input[name=product]", this).val(),
				title: $("input[name=title]", this).val(),
				description: $("input[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=price]", this).val(),
				discount: $("input[name=discount]", this).val()
			}
			$("tbody > tr", table).each(function(){
				$(this).data('product', newdata);
				return false;

			});
			rebuildProductsTable();
		});
	};
	var ADDproductListener = function(){
		$(productADD).submit(function(e){
			e.preventDefault();
			$(this).parents(".modal").modal("hide");
			var newdata = {
				title: $("input[name=title]", this).val(),
				description: $("input[name=description]", this).val(),
				number: $("input[name=number]", this).val(),
				price: $("input[name=price]", this).val(),
				discount: $("input[name=discount]", this).val()
			}
			var $tr = $('<tr></tr>').appendTo($("tbody", table));
			$tr.data('product', newdata);
			rebuildProductsTable();
		});
	};
	var rebuildProductsTable = function(){
		var $trs = '';
		productId = 0;
		$("tbody > tr", table).each(function(){
			productId++;
			$trs += buildProductRow($(this).data('product'));
		});
		$('tbody', table).html($trs);

		RmoveProduct();
		productEditListener();
	}
	var RmoveProduct = function(){
		$(".delete").click(function(e){
			e.preventDefault();
			$(this).parents('tr').remove();
			$("tbody > tr", table).each(function(i){
				$("td:first-child", this).html(i+1);
			});
		});
	};
	var buildProductRow = function(product){
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
		code += '<td>'+(productId)+'</td>';
		code += '<td>'+product.title+'</td>';
		code += '<td>'+product.description+'</td>';
		code += '<td>'+product.number+' عدد</td>';
		code += '<td>'+product.price+' ریال</td>';
		code += '<td>'+product.discount+' ریال</td>';
		code += '<td>'+finalPrice+' ریال</td> <td class="center">';
		var clas = '';
		if(product.id){
			code += productEdit+' ';
		}else{
			link = '#';
			clas = 'delete';
		}
		code += '<a href="'+link+'" class="btn btn-xs btn-bricky product-delete '+clas+'" title="حذف"><i class="fa fa-times fa fa-white"></i></a> </td> </tr>';

		return code;
	}
	var serialize = function(){

		var info = {
			title: $('input[name=title]', form).val(),
			user: $('input[name=user]', form).val(),
			products: []
		}
		$("tbody > tr", table).each(function(){
			info.products.push($(this).data('product'));
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
											}else if(error.input == 'title'){
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
			productEditListener();
			ModalSubmitListener();
			runSubmitFormListener();
			ADDproductListener();
		}
	}
}();
$(function(){
	TransactionEdit.init();
});

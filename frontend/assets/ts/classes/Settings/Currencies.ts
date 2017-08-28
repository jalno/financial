import * as $ from "jquery";
import "../jquery.userAutoComplete";
import { webuilder } from "webuilder";
import "jquery.growl";
import "bootstrap-inputmsg";
interface currency{
	title:string,
	value:number
}
export default class Currencies{
	private static $form;
	private static $panel = $('.panel.panel-white', Currencies.$form);
	private static deleteField($row:JQuery){
		const $prevRow:JQuery = $row.prev();
		$($row).remove();
		Currencies.shiftIndex($prevRow);
	}
	private static setEvents($row:JQuery){
		$('.btn-delete', $row).on('click', function(e){
			e.preventDefault();
			Currencies.deleteField($(this).parents('.rates'));
		});
		$('.rates-currency', $row).on('change', function(){
			const $that = $(this);
			$('.panel-body .rates-currency', Currencies.$panel).each(function(){
				const $parent = $(this).parents('.form-group');
				if(!$(this).is($that) && $(this).val() == $that.val()){
					$parent.addClass('has-error');
					$parent.append(`<span class="help-block">ارز وارد شده تکراری میباشد</span>`)
				}else{
					$parent.removeClass('has-error');
					$('.help-block', $parent).remove();
				}
			});
		}).trigger('change');
	}
	private static shiftIndex($row:JQuery){
		const $rates:JQuery = $(".panel-body .rates", Currencies.$panel);
		let eq:number;
		let found:boolean = false;
		for(let i = 0; i < $rates.length && !found; i++){
			if($rates.eq(i).is($row)){
				eq = i;
				found = true;
			}
		}
		if(found){
			let name:string = $row.find(".rates-currency").attr('name');
			let index:number = parseInt(name.match(/(\d+)/)[0]) + 1;
			for(let i:number = eq + 1; i < $rates.length ;i++, index++){
				$rates.eq(i).find(".rates-currency").attr('name', 'rates['+index+'][currency]');
				$rates.eq(i).find(".rates-price").attr('name', 'rates['+index+'][price]');
			}
		}
	}
	private static runChangeListener():void{
		$('input[name=change]', Currencies.$form).on('change', function(){
			const $this = $(this);
			if(!$this.data('change')){
				$this.prop({
					checked: false,
					disabled: true
				});
			}
			if($this.prop('checked')){
				Currencies.$panel.slideDown();
			}else{
				Currencies.$panel.slideUp();
			}
		}).trigger("change");
	}
	private static createCurrencySelectOptions():string{
		let options:string = '';
		const currencies:currency[] = Currencies.$panel.data('currencies');
		for(const currency of currencies){
			options += `<option value="${currency.value}">${currency.title}</option>`;
		}
		return options;
	}
	private static createChangeRatesFields():void{
		$('.panel-tools a.btn-add', Currencies.$panel).on('click', function(e){
			e.preventDefault();
			const currencies:currency[] = Currencies.$panel.data('currencies');

			const $rates:JQuery = $(".panel-body .row", Currencies.$panel);
			if($rates.length >= currencies.length){
				return;
			}
			const html = `
						<div class="row rates">
							<div class="col-sm-5">
								<div class="form-group"><label class="control-label">قیمت</label><input value="" name="rates[0][price]" class="form-control rates-price ltr" type="number" step="0.0001"></div>
							</div>
							<div class="col-sm-5">
								<div class="form-group"><label class="control-label">ارز</label>
									<select name="rates[0][currency]" class="form-control rates-currency">
										${Currencies.createCurrencySelectOptions()}
									</select>
								</div>
							</div>
							<div class="col-sm-2 col-xs-12 text-center">
								<button href="#" class="btn btn-xs btn-bricky tooltips btn-delete" title="حذف" style="margin-top: 30px;">
									<i class="fa fa-times"></i>
								</button>
							</div>
						</div>
			`;
			const $row = $(html).appendTo($('.panel-body', Currencies.$panel));
			Currencies.setEvents($row);
			Currencies.shiftIndex($row.prev());
		});
	}
	private static runSubmitFormListener(){
		Currencies.$form.on('submit', function(e){
			e.preventDefault();
			let $dataDuplicate:boolean = false;
			$('.rates-currency', Currencies.$panel).each(function(){
				const $this = $(this);
				$('.rates-currency', Currencies.$panel).each(function(){
					if(!$(this).is($this) && $(this).val() == $this.val()){
						$.growl.error({
							title:"خطا",
							message:"در تبدیل های ارز ، مقدار تکراری وجود دارد."
						});
						$dataDuplicate = true;
						return false;
					}
				});
				return false;
			});
			if($dataDuplicate){
				return;
			}
			$(this).formAjax({
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title:"موفق",
						message:"انجام شد ."
					});
					if(data.redirect){
						window.location.href = data.redirect;
					}
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
						}else if(error.error == 'data_duplicate'){
							$params.message = 'داده وارد شده تکراری میباشد';
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
		const $body = $('body');
		if($body.hasClass('currencies-add')){
			Currencies.$form = $('.currency-add-form', $body);
		}else if($body.hasClass('currencies-edit')){
			Currencies.$form = $('.currency-edit-form', $body);
		}
		Currencies.setEvents(Currencies.$form);
		Currencies.runChangeListener();
		Currencies.createChangeRatesFields();
		Currencies.runSubmitFormListener();
	}
	public static initIfNeeded(){
		const $body = $('body');
		if($body.hasClass('financial-settings')){
			Currencies.init();
		}
	}
}
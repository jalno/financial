import * as $ from "jquery";
import "bootstrap/js/tooltip";
export default class byCredit{
	private static $form = $('.pay_credit_form');
	private static runUserListener():void{
		$('input[name=user]', byCredit.$form).on('change', function(e){
			const $this = $(this);
			if($this.data('credit') == 0){
				$this.prop('checked', false);
				$this.prop('disabled', true);
				$this.parent().css({
					opacity: 0.5,
					cursor: 'not-allowed'
				});
				$this.parent().tooltip({
					title: 'موجودی صفر ریال است',
					trigger: 'hover'
				});
				$this.tooltip();
			}
			const $parent = byCredit.$form.parent();
			if($this.prop('checked')){
				const price = byCredit.$form.data('price');
				const credit = $this.data('credit');
				$('input[name=currentcredit]', byCredit.$form).attr('value', credit);
				$('input[name=credit]', byCredit.$form).attr('value', Math.min(credit, price));
				$('.alert', $parent).remove();
				if(credit < price){
					if(!$('.alertes', $parent).length){
						$parent.prepend(`<div class="alertes"></div>`);
					}
					const alert = `<div class="alert alert-block alert-info fade in">
										<button data-dismiss="alert" class="close" type="button">&times;</button>
										<h4 class="alert-heading"><i class="fa fa-info-circle"></i> توجه!</h4>
										<p>به دلیل کمبود موجودی کاربری، بعد از پرداخت همه مبلغ موجود در حساب کاربری، مبلغ ${(price - credit)} ریال به عنوان باقی مانده صورتحساب باید از طریق واریز بانکی و یا واریز آنلاین پرداخت شود.</p>
									</div>`;
					$('.alertes', $parent).html(alert);
				}
			}
		}).trigger('change');
	}
	;
	public static init(){
		if($('input[name=user]', byCredit.$form).length){
			byCredit.runUserListener();
		}
	}
	public static initIfNeeded(){
		if(byCredit.$form.length){
			byCredit.init();
		}
	}
}
<?php
use packages\base\Translator;
use packages\userpanel\Authentication;

$isLogin = Authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
$redirect = $this->getRedirect();
?>
<div class="row">
	<div class="col-xs-12 page-error">
		<div class="error-details col-sm-6 col-sm-offset-3">
			<div class="text-center"><i class="fa fa-spinner fa-spin fa-5x"></i></div>
			<h3><?php echo Translator::trans('pay.redirect.wait'); ?></h3>
			<p><?php echo Translator::trans('pay.redirect.text'); ?></p>
			<form id="onlinepay_redirect_form" action="<?php echo $redirect->getURL(); ?>" method="<?php echo $redirect->method; ?>">
				<?php $this->createFormData(); ?>
				<p><?php echo Translator::trans('pay.redirect.manual', [
					'submit' => '<button type="submit" class="btn btn-primary">'.Translator::trans('here').'</button>'
				]); ?></p>
			</form>
		</div>
	</div>
</div>
<?php
$this->the_footer(!$isLogin ? "logedout" : "");

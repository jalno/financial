<?php
use \packages\userpanel;
use \packages\base\translator;
use \themes\clipone\utility;

$this->the_header();
$redirect = $this->getRedirect();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12 page-error">
		<div class="error-details col-sm-6 col-sm-offset-3">
			<div class="text-center"><i class="fa fa-spinner fa-spin fa-5x"></i></div>
			<h3><?php echo translator::trans('pay.redirect.wait'); ?></h3>
			<p><?php echo translator::trans('pay.redirect.text'); ?></p>
			<form id="onlinepay_redirect_form" action="<?php echo $redirect->getURL(); ?>" method="<?php echo $redirect->method; ?>">
				<?php $this->createFormData(); ?>
				<p><?php echo translator::trans('pay.redirect.manual', array(
					'submit' => '<button type="submit" class="btn btn-primary">'.translator::trans('here').'</button>'
				)); ?></p>
			</form>
		</div>
	</div>
</div>
<?php
$this->the_footer();

<?php
use packages\base;
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
$gateway = $this->getGateway();
?>
<div class="row">
	<div class="col-md-12">
		<form action="<?php echo userpanel\url('settings/financial/gateways/delete/'.$gateway->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo Translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo Translator::trans("financial.gateway.delete.warning", array('gateway' => $gateway->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('settings/financial/gateways'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool)Translator::getLang()->isRTL()) ? "right" : "left"; ?>"></i> <?php echo Translator::trans('back'); ?></a>
					<button type="submit" class="btn btn-danger"><i class="fa fa-trash-o tip"></i> <?php echo Translator::trans("delete") ?></button>
				</p>
			</div>
		</form>
	</div>
</div>
<?php
$this->the_footer();

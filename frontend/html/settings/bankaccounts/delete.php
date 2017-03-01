<?php
$this->the_header();
use \packages\userpanel;
use \packages\base\translator;
$account = $this->getBankaccount();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12">
		<form action="<?php echo userpanel\url('settings/financial/bankaccounts/delete/'.$account->id); ?>" method="POST" role="form" id="delete_form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php
					echo translator::trans("bankaccount.delete.warning", array('bankaccount_id' => $account->id));
					?>
				</p>
				<p>
					<a href="<?php echo userpanel\url("settings/financial/bankaccounts"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('back'); ?></a>
					<button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> <?php echo translator::trans('delete'); ?></button>
				</p>
			</div>
		</form>

	</div>
</div>
<!-- end: PAGE CONTENT-->
<?php
$this->the_footer();

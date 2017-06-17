<?php
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \themes\clipone\utility;

$this->the_header();
$transaction = $this->getTransactionData();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12">
		<form action="<?php echo userpanel\url('transactions/accept/'.$transaction->id); ?>" method="POST" role="form">
			<div class="alert alert-block alert-success fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo translator::trans("transaction.accept.warning", array(
						'transaction_id' => $transaction->id
					));
					?>
				</p>
				<br>
				<p>
					<a href="<?php echo userpanel\url('transactions/pay/'.$transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i><?php echo translator::trans("return"); ?></a>
					<button type="submit" class="btn btn-yellow"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("submit"); ?></button>
				</p>
			</div>
		</form>
	</div>
</div>
<?php
$this->the_footer();

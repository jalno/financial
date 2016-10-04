<?php
use \packages\base;
use \packages\base\frontend\theme;
use \packages\base\translator;
use \packages\base\http;

use \packages\userpanel;

use \themes\clipone\utility;

$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
		<!-- start: BASIC LOCK TICKET -->
		<form action="<?php echo userpanel\url('transactions/delete/'.$this->getTransactionData()->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('transaction.delete'); ?>!</h4>
				<p>
					<?php echo translator::trans("transaction.delete.warning", array('transaction.id' => $this->getTransactionData()->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
					<button type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo translator::trans("ticket.delete") ?></button>
				</p>
			</div>
		</form>
		<!-- end: BASIC LOCK TICKET  -->
	</div>
</div>
<?php
$this->the_footer();

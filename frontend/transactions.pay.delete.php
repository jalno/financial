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
		<form action="<?php echo userpanel\url('transactions/pay/delete/'.$this->getPayData()->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo translator::trans("transaction.pay.delete.warning", array('pay.id' => $this->getPayData()->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions/edit/'.$this->getPayData()->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
					<button type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo translator::trans("ticket.delete") ?></button>
				</p>
			</div>
		</form>
		<!-- end: BASIC LOCK TICKET  -->
	</div>
</div>
<?php
$this->the_footer();

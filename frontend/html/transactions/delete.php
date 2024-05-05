<?php
use \packages\base;
use \packages\base\Frontend\Theme;
use \packages\base\Translator;
use \packages\base\HTTP;

use \packages\userpanel;

use \themes\clipone\Utility;

$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
		<!-- start: BASIC LOCK TICKET -->
		<form action="<?php echo userpanel\url('transactions/delete/'.$this->getTransactionData()->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo Translator::trans('transaction.delete'); ?>!</h4>
				<p>
					<?php echo Translator::trans("transaction.delete.warning", array('transaction.id' => $this->getTransactionData()->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo Translator::trans('return'); ?></a>
					<button type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo Translator::trans("packages.financial.delete") ?></button>
				</p>
			</div>
		</form>
		<!-- end: BASIC LOCK TICKET  -->
	</div>
</div>
<?php
$this->the_footer();

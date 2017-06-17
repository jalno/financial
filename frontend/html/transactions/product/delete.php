<?php
use \packages\base;
use \packages\base\frontend\theme;
use \packages\base\translator;
use \packages\base\http;
use \packages\base\views\FormError;

use \packages\userpanel;

use \themes\clipone\utility;

$this->the_header();
?>
<div class="row">
	<div class="col-xs-12">
		<form action="<?php echo userpanel\url('transactions/product/delete/'.$this->product->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo translator::trans("transaction.product.delete.warning", array('product.id' => $this->product->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions/edit/'.$this->product->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
					<button <?php if($error = $this->getFormErrorsByInput('products')){ echo "disabled";} ?> type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo translator::trans("ticket.delete") ?></button>
				</p>
			</div>
		</form>
	</div>
</div>
<?php
$this->the_footer();

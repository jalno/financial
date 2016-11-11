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
	<div class="col-md-12">
		<!-- start: BASIC LOCK TICKET -->
		<form action="<?php echo userpanel\url('transactions/product/delete/'.$this->getProductData()->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<div class="col-md-12">
					<?php
					if($error = $this->getFormErrorsByInput('products')){
						echo('<div class="alert alert-block alert-info fade in center">');
						if($error->error = FormError::DATA_VALIDATION){
							echo '<div>'.translator::trans('product.error.inputValidation').'</div>';
						}
						echo('</div>');
					}
					 ?>
				</div>
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo translator::trans("transaction.product.delete.warning", array('product.id' => $this->getProductData()->id)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions/edit/'.$this->getProductData()->transaction); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
					<button <?php if($error = $this->getFormErrorsByInput('products')){ echo "disabled";} ?> type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo translator::trans("ticket.delete") ?></button>
				</p>
			</div>
		</form>
		<!-- end: BASIC LOCK TICKET  -->
	</div>
</div>
<?php
$this->the_footer();

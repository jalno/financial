<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
?>
<div class="row">
	<div class="col-xs-12">
		<form action="<?php echo userpanel\url('transactions/product/delete/'.$this->product->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo t('attention'); ?>!</h4>
				<p>
					<?php echo t('transaction.product.delete.warning', ['product.id' => $this->product->id]); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions/edit/'.$this->product->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo t('return'); ?></a>
					<button <?php if ($error = $this->getFormErrorsByInput('products')) {
					    echo 'disabled';
					} ?> type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo t('ticket.delete'); ?></button>
				</p>
			</div>
		</form>
	</div>
</div>
<?php
$this->the_footer();

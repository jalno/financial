<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
?>
<div class="row">
	<div class="col-xs-12">
		<form action="<?php echo userpanel\url('transactions/pay/delete/'.$this->pay->id); ?>" method="POST" role="form" class="form-horizontal">
			<div class="alert alert-block alert-warning fade in">
				<div class="row">
					<div class="col-xs-12">
						<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo Translator::trans('attention'); ?>!</h4>
						<p>
							<?php echo Translator::trans('transaction.pay.delete.warning', ['pay.id' => $this->pay->id]); ?>
						</p>
					</div>
				</div>
				<?php
                if (1 == count($this->pay->transaction->pays)) {
                    ?>
					<div class="col-xs-12">
					<?php
                        $this->createfield([
                            'type' => 'checkbox',
                            'name' => 'untriggered',
                            'options' => [
                                [
                                    'label' => Translator::trans('financial.transaction.pay.delete.triggered'),
                                    'value' => 1,
                                ],
                            ],
                        ]);
                    ?>
					</div>
				<?php } ?>
				<div class="row">
					<div class="col-xs-12">
						<a href="<?php echo userpanel\url('transactions/edit/'.$this->pay->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo Translator::trans('return'); ?></a>
						<button type="submit" class="btn btn-yellow"><i class="fa fa-trash-o tip"></i> <?php echo Translator::trans('ticket.delete'); ?></button>
					</div>
				</div>
			</div>
		</form>
		<!-- end: BASIC LOCK TICKET  -->
	</div>
</div>
<?php
$this->the_footer();

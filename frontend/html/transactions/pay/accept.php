<?php
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \themes\clipone\utility;

$this->the_header();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12">
		<form action="<?php echo userpanel\url('transactions/pay/'.$this->action.'/'.$this->pay->id); ?>" method="POST" role="form">
			<input type="hidden" name="confrim" value="1">
			<div class="alert alert-block alert-warning fade in">
				<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo translator::trans('attention'); ?>!</h4>
				<p>
					<?php echo translator::trans("pay.{$this->action}.warning", array(
						'pay_id' => $this->pay->id,
						'pay_date' => date::format('Y/m/d H:i:s', $this->pay->date),
						'pay_price' => translator::trans('currency.rial', array('number' => $this->pay->price))
					)); ?>
				</p>
				<p>
					<a href="<?php echo userpanel\url('transactions/view/'.$this->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo t("return"); ?></a>
					<button type="submit" class="btn btn-yellow"><i class="fa <?php if($this->action == 'accept')echo('fa-check');elseif($this->action == 'reject')echo('fa-times'); ?>"></i> <?php echo translator::trans('pay.'.$this->action); ?></button>
				</p>
			</div>
		</form>
	</div>
</div>
<?php
$this->the_footer();

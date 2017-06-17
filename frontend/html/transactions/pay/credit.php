<?php
use \packages\userpanel;
use \packages\base\translator;
use \themes\clipone\utility;

$this->the_header();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-phone-3"></i> <?php echo translator::trans('pay.byCredit'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<?php
				if($this->getCredit() < $this->transaction->payablePrice()){
				?>
				<div class="alert alert-block alert-info fade in">
					<button data-dismiss="alert" class="close" type="button">&times;</button>
					<h4 class="alert-heading"><i class="fa fa-info-circle"></i> <?php echo translator::trans('attention'); ?>!</h4>
					<p><?php echo translator::trans('pay.credit.attention.notpaidcomplatly', array('remain' => translator::trans("currency.rial", array('number' =>  $this->transaction->payablePrice() - $this->getCredit())))); ?></p>
				</div>
				<?php
				}
				 ?>
				<form action="<?php echo userpanel\url('transactions/pay/credit/'.$this->transaction->id); ?>" method="POST" role="form" class="pay_credit_form">
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'name' => 'currentcredit',
								'label' => translator::trans("currentcredit"),
								'value' => translator::trans("currency.rial", array('number' => $this->getCredit())),
								'disabled' => true
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'type' => 'number',
								'name' => 'credit',
								'label' => translator::trans("pay.price")
							));
							?>
						</div>
					</div>
					<div class="row" style="margin-top: 20px;margin-bottom: 20px;">
						<div class="col-md-offset-4 col-md-4">
							<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo translator::trans('pay'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

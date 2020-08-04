<?php
use \packages\userpanel;
use \packages\base\translator;
use \themes\clipone\utility;
use \packages\financial\authorization;
use \packages\financial\authentication;
$this->the_header();
?>
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
				<div class="row">
					<div class="col-sm-12">
						<?php if($this->getCredit() < $this->transaction->payablePrice()){ ?>
							<div class="alert alert-block alert-info fade in">
								<button data-dismiss="alert" class="close" type="button">&times;</button>
								<h4 class="alert-heading"><i class="fa fa-info-circle"></i> <?php echo translator::trans('attention'); ?>!</h4>
								<p><?php echo translator::trans('pay.credit.attention.notpaidcomplatly', array('remain' => translator::trans("currency.rial", array('number' =>  $this->transaction->payablePrice() - $this->getCredit())))); ?></p>
							</div>
						<?php } ?>
						<?php $types = authorization::childrenTypes(); ?>
						<form action="<?php echo userpanel\url('transactions/pay/credit/'.$this->transaction->id); ?>" method="POST" role="form" class="pay_credit_form" data-price="<?php echo $this->transaction->payablePrice(); ?>">
							<?php 
							$self = authentication::getUser();
							$user = $this->transaction->user;
							if($types and $self->id != $user->id){ 
								?>
								<div class="row">
									<div class="col-sm-12">
									<?php $this->createField([
										'name' => 'user',
										'type' => 'radio',
										'label' => translator::trans('financial.transaction.pay.byCredit.user'),
										'inline' => true,
										'options' => [
											[
												'label' => translator::trans('financial.transaction.pay.byCredit.user.my'),
												'value' => $self->id,
												'data' => [
													'credit' => $self->credit
												]
											],
											[
												'label' => translator::trans('financial.transaction.pay.byCredit.user.owner'),
												'value' => $user->id,
												'data' => [
													'credit' => $user->credit
												]
											]
										]
									]); ?>
									</div>
								</div>
							<?php } ?>
							<div class="row">
								<div class="col-xs-12">
									<?php
									$this->createField(array(
										'name' => 'currentcredit',
										'label' => translator::trans("currentcredit"),
										'value' => number_format($this->getCredit()),
										'disabled' => true,
										'input-group' => array(
											'right' => $this->getCurrency()->title,
										),
									));
									?>
								</div>
							</div>
							<div class="row">
								<div class="col-xs-12">
									<?php
									$this->createField(array(
										'type' => 'number',
										'name' => 'credit',
										'label' => translator::trans("pay.price"),
										'input-group' => array(
											'right' => $this->getCurrency()->title,
										),
									));
									?>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-offset-4 col-sm-4">
									<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo translator::trans('pay'); ?></button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

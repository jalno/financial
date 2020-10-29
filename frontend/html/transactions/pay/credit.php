<?php
use packages\base\Translator;
use packages\userpanel;
use themes\clipone\Utility;
use packages\financial\{Authentication, Authorization};

$user = $this->transaction->user;
$self = Authentication::getUser();
$types = Authorization::childrenTypes();

$this->the_header();
?>
<div class="row">
	<div class="col-sm-8 col-sm-offset-2">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-phone-3"></i> <?php echo t('pay.byCredit'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body panel-pay-by-credit-body">
				<?php if ($this->getCredit() < $this->transaction->payablePrice()) { ?>
					<div class="alert alert-block alert-info fade in">
						<button data-dismiss="alert" class="close" type="button">&times;</button>
						<h4 class="alert-heading"><i class="fa fa-info-circle"></i> <?php echo t('attention'); ?>!</h4>
						<p><?php echo t('pay.credit.attention.notpaidcomplatly', array('remain' => t("currency.rial", array('number' =>  $this->transaction->payablePrice() - $this->getCredit())))); ?></p>
					</div>
				<?php } ?>
				<form class="pay_credit_form" action="<?php echo userpanel\url("transactions/pay/credit/{$this->transaction->id}"); ?>" method="POST" role="form" data-price="<?php echo $this->transaction->payablePrice(); ?>">
					<?php
                    if ($types and $self->id != $user->id) {
                        $this->createField([
                            'name' => 'user',
                            'type' => 'radio',
                            'label' => t('financial.transaction.pay.byCredit.user'),
                            'inline' => true,
                            'options' => [
                                [
                                    'label' => t('financial.transaction.pay.byCredit.user.my'),
                                    'value' => $self->id,
                                    'data' => [
                                        'credit' => $self->credit
                                    ]
                                ],
                                [
                                    'label' => t('financial.transaction.pay.byCredit.user.owner'),
                                    'value' => $user->id,
                                    'data' => [
                                        'credit' => $user->credit
                                    ]
                                ]
                            ]
                        ]);
					}
					$fields = array(
						array(
							'name' => 'currentcredit',
							'label' => t('currentcredit'),
							'value' => number_format($this->getCredit()),
							'disabled' => true,
							'ltr' => true,
							'input-group' => array(
								'right' => $this->getCurrency()->title,
							),
						),
						array(
							'name' => 'credit',
							'label' => t("pay.price"),
							'ltr' => true,
							'input-group' => array(
								'right' => $this->getCurrency()->title,
							),
						),
					);
					foreach ($fields as $field) {
						$this->createField($field);
					}
					?>
					<div class="row">
						<div class="col-sm-offset-4 col-sm-4">
							<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo t('pay'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

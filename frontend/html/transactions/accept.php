<?php
use packages\base\translator;
use packages\userpanel;
use packages\userpanel\{Date};
use themes\clipone\utility;

$pendingPaysCount = $this->getPendingPaysCount();
$remainPrice = $this->transaction->remainPriceForAddPay();

$this->the_header();
?>
<form class="transaction-accept-form" action="<?php echo userpanel\url("transactions/accept/{$this->transaction->id}"); ?>" method="POST" role="form">
	<div class="alert alert-block alert-success fade in">
		<h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo t('attention'); ?>!</h4>
		<?php if ($pendingPaysCount > 0) { ?>
		<p class="text-warning">
			<i class="fa fa-exclamation" aria-hidden="true"></i>
			<?php echo t("transaction.accept.warning.all_pays_will_accept", array("pending_pays_count" => $pendingPaysCount)); ?>
		</p>
		<?php } ?>
		<?php if ($remainPrice > 0) { ?>
		<p class="text-warning">
			<i class="fa fa-exclamation" aria-hidden="true"></i>
			<?php echo t("transaction.accept.warning.add_new_pay_for_remain_price", array(
				"price" => $remainPrice,
				"currency_title" => $this->transaction->currency->title
			)); ?>
		</p>
		<?php } ?>
		<p><i class="fa fa-question" aria-hidden="true"></i> <?php echo t("transaction.accept.warning", ['transaction_id' => $this->transaction->id]); ?></p>
		<br>
		<a href="<?php echo userpanel\url("transactions/pay/{$this->transaction->id}"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo t("return"); ?></a>
		<button type="submit" class="btn btn-yellow"><i class="fa fa-check-square-o"></i> <?php echo t("submit"); ?></button>
	</div>
</form>

<?php
$this->the_footer();

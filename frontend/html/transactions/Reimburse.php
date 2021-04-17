<?php
use packages\base\{Translator, http, Json};
use packages\financial\{Transaction_Pay};
use packages\userpanel\{Date};
use function packages\userpanel\url;

$isRTL = Translator::getLang()->isRTL();

$this->the_header();
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<i class="fa fa-money"></i> <?php echo t("packages.financial.reimburse.title_with_id", array("transaction_id" => $this->transaction->id)); ?>
		<div class="panel-tools">
			<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
		</div>
	</div>
	<div class="panel-body">
		<form id="reimburse-transaction-form" action="<?php echo url("transactions/{$this->transaction->id}/reimburse"); ?>" method="POST">
			<div class="row">
				<div class="col-sm-5 help-text-container">
					<p class="text-justify">
					<?php
					echo t("packages.financial.reimburse.help_text", array(
						"pay_count" => count($this->pays),
						"amount" => number_format($this->getPaysTotalAmountByCurrency()),
						"currency_name" => $this->userDefaultCurrency->title,
						"user_name" => $this->transaction->user->getFullName(),
					));
					?>
					</p>
				<?php if (!empty($this->notRefundablePays)) { ?>
					<div class="alert alert-danger text-justify">
					<h4 class="alert-heading"><i class="fa fa-times"></i> <?php echo t("error.fatal.title"); ?></h4>
					<?php echo t("packages.financial.reimburse.error_text"); ?>
					</div>
				<?php } ?>
				</div>
				<div class="col-sm-7">
					<p class="h4"><?php echo t("pays"); ?></p>
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th> <?php echo t('date&time'); ?> </th>
								<th> <?php echo t('pay.method'); ?> </th>
								<th> <?php echo t('pay.price'); ?> </th>
								<th> </th>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						foreach ($this->pays as $pay) {
							$canRefund = !in_array($pay->id, $this->notRefundablePays);
						?>
							<tr data-pay='<?php echo json\encode($pay->toArray()); ?>'>
								<td><?php echo $x++; ?></td>
								<td class="text-center" dir="ltr">
									<?php echo Date::format("Y/m/d H:i:s", $pay->date); ?>
								</td>
								<td><?php
								switch ($pay->method) {
									case Transaction_Pay::ONLINEPAY:
										echo t("pay.byPayOnline");
									break;
									case Transaction_Pay::CREDIT:
										echo t("pay.method.credit");
									break;
									case Transaction_Pay::BANKTRANSFER:
										echo t("pay.byBankTransfer");
									break;
								}
								?></td>
								<td><?php echo number_format($pay->price) . " " . $pay->currency->title; ?></td>
								<td class="center"><i class="fa fa-<?php echo $canRefund ? 'check text-success' : 'times text-danger'; ?> tooltips" title="<?php echo $canRefund ? t('packages.financial.reimburse.pay_refundable') : t('packages.financial.reimburse.pay_not_refundable'); ?>"></i></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</form>
	</div>
	<div class="panel-footer">
		<div class="row">
			<div class="col-lg-4 col-md-5 col-sm-6 col-xs-12 pull-<?php echo $isRTL ? "left" : "right"; ?>">
				<div class="btn-group btn-group-justified">
					<div class="btn-group">
						<a href="<?php echo url("transactions/view/{$this->transaction->id}"); ?>" class="btn btn-default">
							<i class="fa fa-chevron-circle-<?php echo $isRTL ? "right" : "left"; ?>"></i> <?php echo t("return"); ?>
						</a>
					</div>
					<div class="btn-group">
						<button type="submit" class="btn btn-warning" form="reimburse-transaction-form"<?php echo count($this->pays) == count($this->notRefundablePays) ? " disabled" : ""; ?>>
							<i class="fa fa-undo"></i> <?php echo t("packages.financial.reimburse.btn_title"); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$this->the_footer();

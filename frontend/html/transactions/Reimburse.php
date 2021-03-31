<?php
use packages\base\{Translator, http, Json};
use packages\financial\{Currency, Transaction_Pay};
use packages\userpanel\{Date};
use function packages\userpanel\url;

$isRTL = boolval(Translator::getLang()->isRTL());

$userDefaultCurrency = Currency::getDefault($this->transaction->user);

$this->the_header();
?>

<div class="row">
	<div class="<?php echo "col-sm-6 col-sm-offset-3 col-xs-12"; ?>">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-money"></i> <?php echo t("packages.financial.reimburse.title_with_id", array("transaction_id" => $this->transaction->id)); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="javascript::void(0)"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="reimburse-transaction-form" action="<?php echo url("transactions/{$this->transaction->id}/reimburse"); ?>" method="POST">
					<div>
					<?php 
					echo t("packages.financial.reimburse.help_text", array(
						"pay_count" => count($this->getPays()),
						"amount" => $this->getPaysTotalAmountByCurrency($userDefaultCurrency),
						"currency_name" => $userDefaultCurrency->title,
						"user_name" => $this->transaction->user->getFullName(),
					));
					?>
					</div>
					<div>
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th> <?php echo t('date&time'); ?> </th>
								<th> <?php echo t('pay.method'); ?> </th>
								<th> <?php echo t('pay.price'); ?> </th>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						foreach ($this->getPays() as $pay) {
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
								<td><?php echo $pay->price . " " . $pay->currency->title; ?></td>
								</tr>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					</div>
				</form>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-6 pull-<?php echo $isRTL ? "left" : "right"; ?>">
						<div class="btn-group btn-group-justified">
							<div class="btn-group">
								<a href="<?php echo url("transactions/view/{$this->transaction->id}"); ?>" class="btn btn-default">
									<i class="fa fa-chevron-circle-<?php echo $isRTL ? "right" : "left"; ?>"></i> <?php echo t("return"); ?>
								</a>
							</div>
							<div class="btn-group">
								<button type="submit" class="btn btn-warning" form="reimburse-transaction-form">
									<i class="fa fa-undo"></i> <?php echo t("packages.financial.reimburse.btn_title"); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$this->the_footer();

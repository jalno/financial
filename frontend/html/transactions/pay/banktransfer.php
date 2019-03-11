<?php
use packages\userpanel;
use packages\base\http;
use themes\clipone\utility;
use packages\financial\authentication;

$parameter = array();
if ($token = http::getURIData("token")) {
	$parameter["token"] = $token;
}
$isLogin = authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-md-7">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-university"></i> <?php echo t("packages.financial.banks.accounts"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover">
						<thead>
							<tr>
								<th><?php echo t("packages.financial.banks.account.title"); ?></th>
								<th><?php echo t("packages.financial.banks.account.account"); ?></th>
								<th><?php echo t("packages.financial.banks.account.cart"); ?></th>
								<th><?php echo t("packages.financial.banks.account.owner"); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach($this->getBankAccounts() as $account){
						?>
						<tr>
							<td><?php echo $account->bank->title; ?></td>
							<td><?php echo $account->account ? $account->account : "-"; ?></td>
							<td><?php echo $account->cart ? $account->cart : "-"; ?></td>
							<td><?php echo $account->owner; ?></td>
						</tr>
						<?php
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-5">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-banknote"></i> <?php echo t('pay.byBankTransfer'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form action="<?php echo userpanel\url('transactions/pay/banktransfer/'.$this->transaction->id, $parameter); ?>" method="POST" role="form" class="pay_banktransfer_form">
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'type' => 'number',
								'name' => 'price',
								'label' => t("pay.banktransfer.price"),
								"ltr" => true,
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'type' => 'select',
								'name' => 'bankaccount',
								'label' => t("pay.banktransfer.bankaccount"),
								'options' => $this->getBankAccountsForSelect(),
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'name' => 'date',
								'label' => t("pay.banktransfer.date"),
								"ltr" => true,
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'type' => 'number',
								'name' => 'followup',
								'label' => t("pay.banktransfer.followup"),
								"ltr" => true,
							));
							?>
						</div>
					</div>
					<div class="row" style="margin-top: 20px;margin-bottom: 20px;">
						<div class="col-md-offset-4 col-md-4">
							<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo t('submit'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

<?php
use packages\base\{Date, http};
use packages\userpanel;
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
		<?php if ($this->getBanktransferPays()) { ?>
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa  fa-credit-card-alt"></i><?php echo t("packages.financial.pays.banktransfer"); ?>
					<div class="panel-tools">
						<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
					</div>
				</div>
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-hover">
							<thead>
								<tr>
									<th><?php echo t("date&time"); ?></th>
									<th><?php echo t("transaction.banktransfer.price"); ?></th>
									<th><?php echo t("packages.financial.pays.banktransfer_to"); ?></th>
									<th><?php echo t("pay.banktransfer.followup"); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php
							foreach($this->getBanktransferPays() as $pay){
							?>
							<tr>
								<td class="center ltr"><?php echo Date\jDate::format("Y/m/d H:i", $pay->date); ?></td>
								<td><?php echo number_format($pay->price) . " " . $pay->currency->title ?></td>
								<td><?php echo $pay->getBanktransferBankAccount()->cart; ?></td>
								<td><?php echo $pay->param("followup"); ?></td>
							</tr>
							<?php
							}
							?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php } ?>
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
							<div class="well label-info">
							<?php echo t("packages.financial.remain_price"); ?>:
								<span class="pull-left"><?php echo number_format($this->transaction->payablePrice()) . " " . $this->transaction->currency->title; ?></span>
							</div>
						</div>
						<div class="col-xs-12">
							<?php
							echo $this->createField(array(
								'name' => 'price',
								'label' => t("pay.banktransfer.price"),
								'type' => 'number',
								'min' => 0,
								'ltr' => true,
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
							$this->createField(array(
								'name' => 'followup',
								'label' => t("pay.banktransfer.followup"),
								'type' => 'number',
								'min' => 0,
								"ltr" => true,
							));
							$this->createField(array(
								'name' => 'description',
								'label' => t("description"),
								'type' => 'textarea',
								'class' => 'form-control banktransfer-description',
								'rows' => 2
							));
							?>
						</div>
					</div>
					<div class="row" style="margin-top: 20px;margin-bottom: 20px;">
						<div class="col-md-4">
							<a href="<?php echo userpanel\url('transactions/view/' . $this->transaction->id); ?>" class="btn btn-block btn-default"><i class="fa fa-arrow-circle-right"></i> <?php echo t("return"); ?></a>
						</div>
						<div class="col-md-8">
							<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo t('submit'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

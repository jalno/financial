<?php
use packages\base;
use packages\base\{Translator, http, Json};
use packages\financial\{Authentication, Currency, Transaction, Transaction_pay};
use packages\userpanel;
use packages\userpanel\{Date, User};
use themes\clipone\Utility;
use themes\clipone\views\TransactionUtilities;

$isLogin = Authentication::check();
$remainPriceForAddPay = $this->transaction->remainPriceForAddPay();
$refundTransaction = $this->transaction->totalPrice() < 0;
if ($refundTransaction) {
	$refundInfo = nl2br($this->transaction->param("refund_pay_info"));
} else {
	$refundInfo = null;
}

$this->the_header(!$isLogin ? "logedout" : "");
?>
<div class="row">
	<div class="col-xs-12">
		<div class="invoice">
			<div class="row invoice-logo">
				<?php $logoPath = $this->getTransActionLogo(); ?>
					<?php if($logoPath){ ?>
					<div class="col-sm-6">
						<a href="<?php echo base\url(); ?>" target="_blank" >
							<img src="<?php echo($logoPath); ?>"/>
						</a>
					</div>
					<?php } ?>
				<div class="col-sm-6 <?php echo(!$logoPath ? "col-sm-offset-6" : ""); ?>">
					<p>
						#<?php echo $this->transaction->id; ?> / <?php echo date::format("l j F Y", $this->transaction->create_at); ?><span><?php echo $this->transaction->title; ?></span>
					</p>
				</div>
			</div>
			<hr>
		<?php if ($this->hasdesc or ($this->transaction->status == Transaction::paid and $refundInfo)) { ?>
			<div class="row">
				<div class="col-md-12">
					<div class="box-note">
					<?php
					if ($this->hasdesc) {
						foreach ($this->transaction->products as $product) {
							if ($product->param('description')) {
					?>
						<p><b><?php echo $product->title ?></b>: <br><?php echo $product->param('description') ?></p>
					<?php
							}
						}
					}
					if ($refundInfo) {
					?>
						<p><b><?php echo t("packages.financial.refund.pay.info"); ?></b>: <br> <?php echo nl2br($refundInfo); ?></p>
					<?php } ?>
					</div>
				</div>
			</div>
			<?php
			}
			if (!$this->transaction->user) {
				$firstname = $this->transaction->param("firstname");
				$lastname = $this->transaction->param("lastname");
				$email = $this->transaction->param("email");
				$cellphone = $this->transaction->param("cellphone");
				if ($firstname or $lastname or $email or $cellphone) {
					$user = new user();
					if ($firstname) {
						$user->name = $firstname;
					}
					if ($lastname) {
						$user->lastname = $lastname;
					}
					if ($email) {
						$user->email = $email;
					}
					if ($cellphone) {
						$user->cellphone = $cellphone;
					}
					$this->transaction->user = $user;
				}
			}
			?>
			<div class="row">
			<?php if ($this->transaction->user) { ?>
				<div class="col-sm-4">
					<h4><?php echo t("packages.financial.purchaser"); ?>:</h4>
					<div class="well">
						<address>
							<?php if ($this->transaction->user->name or $this->transaction->user->lastname) { ?>
							<strong><?php echo $this->transaction->user->getFullName(); ?></strong>
							<br>
							<?php
							}
							if($this->transaction->user->address){
								echo $this->transaction->user->address;
							?>
							<br>
							<?php
							}
							if($this->transaction->user->country) {
								if ($this->transaction->user->country->id == 105 and Translator::getShortCodeLang() == "fa") {
									echo("ایران");
								} else {
									echo($this->transaction->user->country->name);
								}
							}
							?> - <?php echo $this->transaction->user->city; ?>
							<?php if($this->transaction->user->phone){ ?>
							<br>
							<strong><?php echo t("packages.financial.phone"); ?>:</strong><?php echo $this->transaction->user->phone; ?>
							<?php } ?>
						</address>
						<address>
							<?php if ($this->transaction->user->email) { ?>
							<strong><?php echo t("packages.financial.email"); ?>: </strong>
							<br>
							<?php
							}
							if ($this->transaction->user->email) { ?>
							<a href="<?php echo "mailto:".$this->transaction->user->email; ?>"><?php echo $this->transaction->user->email; ?></a>
							<?php } ?>
						</address>
					</div>
				</div>
			<?php } ?>
				<div class="col-sm-4 pull-left">
					<h4><?php echo t("packages.financial.transaction.details"); ?>:</h4>
					<ul class="list-unstyled invoice-details">
						<li>
							<strong><?php echo t("packages.financial.transaction.id"); ?> :</strong> <?php echo $this->transaction->id; ?>
						</li>
						<li>
							<strong><?php echo t("packages.financial.transaction.title"); ?>:</strong> <?php echo $this->transaction->title; ?>
						</li>
						<li>
							<strong><?php echo t("transaction.createdate"); ?>:</strong> <span dir="ltr"> <?php echo date::format("Y/m/d H:i:s", $this->transaction->create_at); ?><span>
						</li>
						<li>
							<strong><?php echo t("transaction.add.expire_at"); ?>:</strong>
							<span dir="ltr">
								<?php echo $this->transaction->expire_at ? Date::format("Y/m/d H:i:s", $this->transaction->expire_at) : '-'; ?>
							</span>
						</li>
						<li>
							<strong><?php echo t("transaction.status"); ?>:</strong>
							<?php echo TransactionUtilities::getStatusLabelSpan($this->transaction) ?? '-'; ?>
						</li>
					</ul>
				</div>
			</div>

			<?php
			if ($refundTransaction) {
				if ($this->transaction->status == Transaction::expired) {	
			?>
				<div class="alert alert-info text-center"><?php echo t("packages.financial.refunded-expired-refund-transaction"); ?></div>
			<?php
				} elseif ($this->transaction->status == Transaction::rejected) {
			?>
			<div class="alert alert-warning"><?php echo t($refundInfo ? "packages.financial.rejected-refund-transaction-reasoned" : "packages.financial.rejected-refund-transaction", array(
				'reason' => $refundInfo
			)); ?></div>
			<?php
				}
			}
			?>
			<h3><?php echo t("transaction.products"); ?></h3>
			<div class="row">
				<div class="col-sm-12">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th><?php echo t("transaction.add.product"); ?></th>
								<th class="hidden-480"><?php echo t("transaction.add.description"); ?></th>
								<th class="hidden-480"><?php echo t("transaction.add.number"); ?></th>
								<th class="hidden-480"><?php echo t("financial.transaction.product.price_unit"); ?></th>
								<th class="hidden-480"><?php echo t("financial.transaction.product.discount"); ?></th>
								<th class="hidden-480"><?php echo t("transaction.tax"); ?></th>
								<th><?php echo t("financial.transaction.product.price.final"); ?></th>‍
								<?php if($this->transaction->status == transaction::paid and !$this->transaction->isConfigured()){ ?><th></th>‍<?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						$currency = $this->transaction->currency;
						foreach($this->transaction->products as $product){
							$productPrice = $product->currency->changeTo(($product->price * $product->number), $currency);
							$productDiscount = $product->currency->changeTo($product->discount, $currency);

							$this->discounts += $productDiscount;

							$finalPrice = $product->currency->changeTo($product->totalPrice(), $currency);

							$vat = ($product->vat * $productPrice) / 100;

							$this->vats += $vat;
						?>
							<tr>
								<td><?php echo $x++; ?></td>
								<td><?php echo $product->title; ?></td>
								<td class="hidden-480"><?php echo $product->description; ?></td>
								<td class="hidden-480"><?php echo t("product.xnumber", array("number" => $product->number)); ?></td>
								<td class="hidden-480"> <?php echo $this->numberFormat($productPrice) . " " . $currency->title; ?></td>
								<td class="hidden-480"> <?php echo $this->numberFormat($productDiscount) . " " . $currency->title; ?></td>
								<td class="hidden-480"> <?php echo $this->numberFormat($vat) . " " . $currency->title; ?></td>
								<td><?php echo $this->numberFormat($finalPrice) . " " . $currency->title; ?></td>
								<?php if($this->transaction->status == transaction::paid and !$product->configure){ ?>
								<td><a href="<?php echo userpanel\url("transactions/config/".$product->id); ?>" class="btn btn-sm btn-teal"><i class="fa fa-cog"></i> <?php echo translator::trans("financial.configure"); ?></a></td>
								<?php } ?>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php
			if($this->pays){
				$hasdesc = $this->paysHasDiscription();
				$hastatus = $this->paysHasStatus();
				$hasButtons = $this->hasButtons();
			?>
			<h3><?php echo translator::trans('pays'); ?></h3>
			<?php if (!$refundTransaction and $this->transaction->status == Transaction::expired) { ?>
			<div class="alert alert-info text-center"><?php echo t("packages.financial.refunded-expired-buy-transaction"); ?></div>
			<?php } ?>
			<div class="row">
				<div class="col-xs-12">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th> <?php echo translator::trans('date&time'); ?> </th>
								<th> <?php echo translator::trans('pay.method'); ?> </th>
								<?php if($hasdesc){ ?><th> <?php echo translator::trans('description'); ?> </th><?php } ?>
								<th> <?php echo translator::trans('pay.price'); ?> </th>
								<?php if($hastatus){ ?><th> <?php echo translator::trans('pay.status'); ?> </th><?php } ?>
								<?php if($hasButtons){ ?><th><?php echo t("financial.actions"); ?></th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						foreach($this->pays as $pay){
							if($hasButtons){
								$this->setButtonParam('pay_accept', 'link', userpanel\url("transactions/pay/accept/".$pay->id));
								$this->setButtonParam('pay_reject', 'link', userpanel\url("transactions/pay/reject/".$pay->id));
								$this->setButtonActive('pay_accept', $this->canPayAccept and $pay->status == Transaction_pay::pending);
								$this->setButtonActive('pay_reject', $this->canPayReject and $pay->status == Transaction_pay::pending);
							}
							if($hastatus){
								$statusClass = utility::switchcase($pay->status, array(
									'label label-danger' => transaction_pay::rejected,
									'label label-success' => transaction_pay::accepted,
									'label label-warning' => transaction_pay::pending,
									'label label-info' => transaction_pay::REIMBURSE,
								));
								$statusTxt = utility::switchcase($pay->status, array(
									'pay.rejected' => transaction_pay::rejected,
									'pay.accepted' => transaction_pay::accepted,
									'pay.pending' => transaction_pay::pending,
									'pay.reimburse' => transaction_pay::REIMBURSE
								));
							}
						?>
							<tr data-pay='<?php echo json\encode($pay->toArray()); ?>'>
								<td><?php echo $x++; ?></td>
								<td class="ltr-text-center"><?php echo $pay->date; ?></td>
								<td><?php echo $pay->method; ?></td>
								<?php if($hasdesc){ ?><td><?php echo $pay->description; ?></td><?php } ?>
								<td><?php echo $pay->price; ?></td>
								<?php if($hastatus){ ?><td><span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></td><?php } ?>
								<?php
								if($hasButtons){
									echo("<td class=\"center\">".$this->genButtons()."</td>");
								}
								?>
								</tr>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

			<?php
			}
			?>
			<div class="row">
				<div class="col-sm-12 invoice-block">
					<ul class="list-unstyled amounts">
						<li><strong><?php echo t("packages.financial.total_price"); ?>:</strong> <?php echo($this->numberFormat(abs($this->transaction->price)). " " . $currency->title); ?></li>
						<li><strong><?php echo t("transaction.add.discount"); ?>:</strong> <?php echo($this->numberFormat($this->discounts) . " " . $currency->title); ?></li>
						<li><strong><?php echo t("packages.financial.tax"); ?>:</strong> <?php echo $this->numberFormat($this->vats) . " " .$currency->title; ?></li>
						<li>
							<strong><?php echo t("packages.financial.payable_price"); ?>:</strong>
						<?php
						echo $this->numberFormat(abs($remainPriceForAddPay)) . " " .$currency->title;
						?>
						</li>
					</ul>
					<br>
					<a onclick="javascript:window.print();" class="btn btn-lg btn-teal hidden-print"> <?php echo t("print"); ?> <i class="fa fa-print"></i></a>
					<?php
					if ($this->transaction->canAddPay() and !$refundTransaction) {
						$parameter = array();
						if ($token = http::getURIData("token")) {
							$parameter["token"] = $token;
						}
					?>
					<a class="btn btn-lg btn-green hidden-print btn-pay" href="<?php echo userpanel\url('transactions/pay/'.$this->transaction->id, $parameter);?>"><?php echo t("packages.financial.transaction.pay"); ?><i class="fa fa-check"></i></a>
					<?php
					} elseif (!$refundTransaction and in_array($this->transaction->status, [Transaction::PENDING, Transaction::UNPAID])) {
					?>
					<a class="btn btn-lg btn-info hidden-print btn-accept" href="<?php echo userpanel\url("transactions/accept/{$this->transaction->id}");?>"><?php echo t("packages.financial.transaction.accept"); ?><i class="fa fa-check"></i></a>
					<?php
					} elseif ($remainPriceForAddPay < 0 and $this->canAcceptRefund) {
						$refundTransaction = true;
					?>
					<a class="btn btn-lg btn-success hidden-print" href="#refund-accept-modal" data-toggle="modal">
						<div class="btn-icons"> <i class="fa fa-check-square-o"></i> </div>
						<?php echo t("packages.financial.refund.accept"); ?>
					</a>
					<a class="btn btn-lg btn-danger hidden-print" href="#refund-reject-modal" data-toggle="modal">
						<div class="btn-icons"> <i class="fa fa-times-circle"></i> </div>
						<?php echo t("packages.financial.refund.reject"); ?>
					</a>
					<?php
					}
					if ($this->canReimburse) {
					?>
					<a class="btn btn-lg btn-warning hidden-print" href="<?php echo userpanel\url("transactions/{$this->transaction->id}/reimburse");?>">
						<?php echo t("packages.financial.reimburse.btn_title"); ?> <i class="fa fa-undo"></i>
					</a>
					<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if ($refundTransaction) { ?>
<div class="modal fade" id="refund-accept-modal" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title text-success"><?php echo t("packages.financial.refund.accept"); ?></h4>
	</div>
	<div class="modal-body">
		<form id="refund-accept-form" action="<?php echo userpanel\url("transactions/{$this->transaction->id}/refund/accept"); ?>" method="POST" autocomplete="off">
		<?php $this->createField(array(
			"type" => "textarea",
			"name" => "refund_pay_info",
			"label" => t("packages.financial.refund.pay.info"),
			"rows" => 4,
		)); ?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="refund-accept-form" class="btn btn-success"><?php echo t("packages.financial.submit"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("packages.financial.cancel"); ?></button>
	</div>
</div>
<div class="modal fade" id="refund-reject-modal" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title text-danger"><?php echo t("packages.financial.refund.reject"); ?></h4>
	</div>
	<div class="modal-body">
		<form id="refund-reject-form" action="<?php echo userpanel\url("transactions/{$this->transaction->id}/refund/reject"); ?>" method="POST" autocomplete="off">
			<p><?php echo t("transaction.reject.warning"); ?></p>
			<?php
			$this->createField(array(
				"type" => "textarea",
				"name" => "refund_pay_info",
				"label" => t("packages.financial.refund.pay.reject_reason"),
				"rows" => 4,
			));
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="refund-reject-form" class="btn btn-danger"><?php echo t("packages.financial.submit"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("packages.financial.cancel"); ?></button>
	</div>
</div>
<?php
}
$this->the_footer(!$isLogin ? "logedout" : "");

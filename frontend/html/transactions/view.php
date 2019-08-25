<?php
use \packages\base;
use \packages\base\{translator, http};
use \themes\clipone\utility;
use \packages\financial\{currency, transaction, transaction_pay, authentication};
use \packages\userpanel;
use \packages\userpanel\{date, user};
$isLogin = authentication::check();
$payablePrice = $this->transaction->payablePrice();
$refundTransaction = $payablePrice < 0;
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
		<?php if ($this->hasdesc or $refundInfo = $this->transaction->param("refund_pay_info")) { ?>
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
					<h4>خریدار:</h4>
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
							if($this->transaction->user->country){
								switch($this->transaction->user->country->id){
									case'105':
										echo("ایران");
										break;
									default:
										echo($this->transaction->user->country->name);
										break;
								}
							}
							?> - <?php echo $this->transaction->user->city; ?>
							<?php if($this->transaction->user->phone){ ?>
							<br>
							<strong>تلفن:</strong><?php echo $this->transaction->user->phone; ?>
							<?php } ?>
						</address>
						<address>
							<?php if ($this->transaction->user->email) { ?>
							<strong>ایمیل: </strong>
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
					<h4>اطلاعات فاکتور:</h4>
					<ul class="list-unstyled invoice-details">
						<li>
							<strong>کد فاکتور :</strong> <?php echo $this->transaction->id; ?>
						</li>
						<li>
							<strong>عنوان فاکتور:</strong> <?php echo $this->transaction->title; ?>
						</li>
						<li>
							<strong>تاریخ صدور:</strong> <?php echo date::format("Y/m/d H:i:s", $this->transaction->create_at); ?>
						</li>
						<li>
							<strong>تاریخ انقضا:</strong> <?php echo date::format("Y/m/d H:i:s", $this->transaction->expire_at); ?>
						</li>
						<li>
						<?php
						$statusClass = utility::switchcase($this->transaction->status, array(
							'label label-danger' => transaction::unpaid,
							'label label-success' => transaction::paid,
							'label label-warning' => transaction::refund,
							"label label-inverse" => transaction::expired,
							"label label-danger label-rejected" => transaction::rejected,
						));
						$statusTxt = utility::switchcase($this->transaction->status, array(
							'transaction.unpaid' => transaction::unpaid,
							'transaction.paid' => transaction::paid,
							'transaction.refund' => transaction::refund,
							"transaction.status.expired" => transaction::expired,
							"packages.financial.transaction.status.rejected" => transaction::rejected,
						));
						?>
							<strong>وضعیت :</strong> <span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></span>
						</li>
					</ul>
				</div>
			</div>

			<?php if ($refundTransaction and $this->transaction->status == Transaction::expired) { ?>
			<div class="alert alert-info text-center"><?php echo t("packages.financial.refunded-expired-refund-transaction"); ?></div>
			<?php } ?>
			<h3>محصولات</h3>
			<div class="row">
				<div class="col-sm-12">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th> محصول </th>
								<th class="hidden-480"> توضیحات </th>
								<th class="hidden-480"> تعداد </th>
								<th class="hidden-480"> قیمت واحد </th>
								<th class="hidden-480"> تخفیف </th>
								<th> قیمت نهایی </th>‍
								<?php if($this->transaction->status == transaction::paid and !$this->transaction->isConfigured()){ ?><th></th>‍<?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						$currency = $this->transaction->currency;
						foreach($this->transaction->products as $product){
							$pcurrency = $product->currency;
							$rate = false;
							if($pcurrency->id != $currency->id){
								$rate = new currency\rate();
								$rate->where('currency', $pcurrency->id);
								$rate->where('changeTo', $currency->id);
								$rate = $rate->getOne();
							}
							$product->price = abs($product->price);
							$finalPrice = ($product->price * $product->number) - $product->discount;
						?>
							<tr>
								<td><?php echo $x++; ?></td>
								<td><?php echo $product->title; ?></td>
								<td class="hidden-480"><?php echo $product->description; ?></td>
								<td class="hidden-480"><?php echo $product->number; ?> عدد</td>
								<td class="hidden-480"> <?php echo ($rate ? $product->price * $rate->price : $product->price).$currency->title; ?></td>
								<td class="hidden-480"> <?php echo ($rate ? $product->discount * $rate->price : $product->discount).$currency->title; ?></td>
								<td><?php echo ($rate ? $finalPrice * $rate->price : $finalPrice).$currency->title; ?></td>
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
								<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						foreach($this->pays as $pay){
							if($hasButtons){
								$this->setButtonParam('pay_accept', 'link', userpanel\url("transactions/pay/accept/".$pay->id));
								$this->setButtonParam('pay_reject', 'link', userpanel\url("transactions/pay/reject/".$pay->id));

							}
							if($hastatus){
								$statusClass = utility::switchcase($pay->status, array(
									'label label-danger' => transaction_pay::rejected,
									'label label-success' => transaction_pay::accepted,
									'label label-warning' => transaction_pay::pending
								));
								$statusTxt = utility::switchcase($pay->status, array(
									'pay.rejected' => transaction_pay::rejected,
									'pay.accepted' => transaction_pay::accepted,
									'pay.pending' => transaction_pay::pending
								));
							}
						?>
							<tr>
								<td><?php echo $x++; ?></td>
								<td><?php echo $pay->date; ?></td>
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
						<li><strong>جمع کل:</strong> <?php echo(number_format(abs($this->transaction->price)).$currency->title); ?></li>
						<li><strong>تخفیف:</strong><?php echo(number_format($this->Discounts()).$currency->title); ?></li>
						<li><strong>مالیات:</strong> 0 <?php echo $currency->title; ?></li>
						<li>
							<strong>مبلغ قابل پرداخت:</strong>
						<?php
						echo abs($payablePrice). " " .$currency->title;
						?>
						</li>
					</ul>
					<br>
					<a onclick="javascript:window.print();" class="btn btn-lg btn-teal hidden-print">چاپ<i class="fa fa-print"></i></a>
					<?php
					if ($this->transaction->status == transaction::unpaid) {
						if ($payablePrice > 0) {
							$parameter = array();
							if ($token = http::getURIData("token")) {
								$parameter["token"] = $token;
							}
					?>
						<a class="btn btn-lg btn-green hidden-print btn-pay" href="<?php echo userpanel\url('transactions/pay/'.$this->transaction->id, $parameter);?>">پرداخت صورتحساب<i class="fa fa-check"></i></a>
					<?php
					} else if ($payablePrice < 0 and $this->canAcceptRefund) {
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
		<form id="refund-reject-form" class="form-horizontal" action="<?php echo userpanel\url("transactions/{$this->transaction->id}/refund/reject"); ?>" method="POST" autocomplete="off">
			<p>آیا از عدم تایید این صورتحساب اطمینان دارید؟</p>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="refund-reject-form" class="btn btn-danger"><?php echo t("packages.financial.submit"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("packages.financial.cancel"); ?></button>
	</div>
</div>
<?php } ?>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

<?php
use \packages\userpanel;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\base\translator;
use \themes\clipone\utility;
use \packages\userpanel\date;
$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
		<!-- start: BASIC TABLE PANEL -->
		<div class="invoice">
			<div class="row invoice-logo">
				<div class="col-sm-6">
				</div>
				<div class="col-sm-6">
					<p>
						#<?php echo $this->transaction->id; ?> / <?php echo date::format("l j F Y", $this->transaction->create_at); ?><span><?php echo $this->transaction->title; ?></span>
					</p>
				</div>
			</div>
			<hr>
			<?php
			if($this->hasdesc){
			?>
			<div class="row">
				<div class="col-md-12">
					<div class="box-note">
						<?php
						foreach($this->transaction->products as $product){
							if($product->param('description')){
						?>
						<p><b><?php echo $product->title ?></b>: <br><?php echo $product->param('description') ?></p>
					<?php
							}
						}?>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="row">
				<div class="col-sm-4">
					<h4>خریدار:</h4>
					<div class="well">
						<address>
							<strong><?php echo $this->transaction->user->getFullName(); ?></strong>
							<br>
							<?php
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
										echo($this->transaction->country->name);
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
							<strong>ایمیل: </strong>
							<br>
							<a href="<?php echo "mailto:".$this->transaction->user->email; ?>"><?php echo $this->transaction->user->email; ?></a>
						</address>
					</div>
				</div>
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
								'label label-warning' => transaction::refund
							));
							$statusTxt = utility::switchcase($this->transaction->status, array(
								'transaction.unpaid' => transaction::unpaid,
								'transaction.paid' => transaction::paid,
								'transaction.refund' => transaction::refund
							));
							 ?>
							<strong>وضعیت :</strong> <span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></span>
						</li>
					</ul>
				</div>
			</div>
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
								<th> قیمت نهایی </th>
							</tr>
						</thead>
						<tbody>
						<?php
						$x = 1;
						foreach($this->transaction->products as $product){
								?>
							<tr>
								<td><?php echo $x++; ?></td>
								<td><?php echo $product->title; ?></td>
								<td class="hidden-480"><?php echo $product->description; ?></td>
								<td class="hidden-480"><?php echo $product->number; ?> عدد</td>
								<td class="hidden-480"> <?php echo $product->price; ?> ریال</td>
								<td class="hidden-480"> <?php echo $product->discount; ?> ریال</td>
								<td><?php echo(($product->price*$product->number)-$product->discount); ?> ریال</td>
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
			<div class="row">
				<div class="col-xs-12">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th> # </th>
								<th> <?php echo translator::trans('date&time'); ?> </th>
								<th> <?php echo translator::trans('pay.method'); ?> </th>
								<?php if($hasdesc){ ?><th> <?php echo translator::trans('description'); ?> </th><?php } ?>
								<th> <?php echo translator::trans('transaction.price'); ?> </th>
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
								<td class="hidden-480"><?php echo $pay->method; ?></td>
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
						<li><strong>جمع کل:</strong> <?php echo(number_format($this->transaction->price)); ?> ریال</li>
						<li><strong>تخفیف:</strong><?php echo(number_format($this->Discounts())); ?> ریال</li>
						<li><strong>مالیات:</strong> 0 ریال</li>
						<li><strong>مبلغ قابل پرداخت:</strong><?php echo(number_format($this->transaction->payablePrice())); ?> ریال</li>
					</ul>
					<br>
					<a onclick="javascript:window.print();" class="btn btn-lg btn-teal hidden-print">چاپ<i class="fa fa-print"></i></a>
					<?php if($this->transaction->status == transaction::unpaid){ ?><a class="btn btn-lg btn-green hidden-print btn-pay" href="<?php echo userpanel\url('transactions/pay/'.$this->transaction->id);?>">پرداخت صورتحساب<i class="fa fa-check"></i></a><?php } ?>
				</div>
			</div>
		</div>
		<!-- end: BASIC TABLE PANEL -->
	</div>
</div>
<?php
$this->the_footer();

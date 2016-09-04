<?php
use \packages\userpanel;
use \packages\financial\transaction;
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
					<a href="http://jeyserver.com/" target="_blank"><img alt="" src="http://static.jeyserver.com/theme/panel/assets/images/jslogo3.png"></a>
				</div>
				<div class="col-sm-6">
					<p>
						#<?php echo $this->transaction->id; ?> / <?php echo date::format("l j F Y", $this->transaction->create_at); ?><span><?php echo $this->transaction->title; ?></span>
					</p>
				</div>
			</div>
			<hr>
			<div class="row">
				<div class="col-sm-4">
					<h4>خریدار:</h4>
					<div class="well">
						<address>
							<strong><?php echo $this->transaction->user->name; echo $this->transaction->user->lastname; ?></strong>
							<br>
							<?php echo $this->transaction->user->address; ?>
							<br>
							<?php
							switch($this->transaction->user->country->id){
								case'105':
									echo("ایران");
									break;
								default:
									echo($this->transaction->country->name);
									break;
							}
							?> - <?php echo $this->transaction->user->city; ?>
							<br>
							<strong>تلفن:</strong><?php echo $this->transaction->user->phone; ?>
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
						<?php foreach($this->transaction->products as $product){ ?>
							<tr>
								<td>1</td>
								<td><?php echo $product->title; ?></td>
								<td class="hidden-480"><?php echo $product->description; ?></td>
								<td class="hidden-480"><?php echo $product->number; ?> عدد</td>
								<td class="hidden-480"> <?php echo $product->price/$product->number; ?> ریال</td>
								<td class="hidden-480"> <?php echo $product->discount; ?> ریال</td>
								<td><?php echo $product->price; ?> ریال</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12 invoice-block">
					<ul class="list-unstyled amounts">
						<li><strong>جمع کل:</strong> <?php echo(number_format($this->transaction->price)); ?> ریال</li>
						<li><strong>تخفیف:</strong><?php echo(number_format($this->transaction->products->discount)); ?> ریال</li>
						<li><strong>مالیات:</strong> 0 ریال</li>
						<li><strong>مبلغ قابل پرداخت:</strong><?php echo(number_format($this->transaction->price)); ?> ریال</li>
					</ul>
					<br>
					<a onclick="javascript:window.print();" class="btn btn-lg btn-teal hidden-print">چاپ<i class="fa fa-print"></i></a>
					<a class="btn btn-lg btn-green hidden-print btn-pay" data-toggle="modal" href="#typepay">پرداخت صورتحساب<i class="fa fa-check"></i></a>
				</div>
			</div>
		</div>
		<!-- end: BASIC TABLE PANEL -->
	</div>
</div>
<?php
$this->the_footer();

<?php
use packages\userpanel;
use packages\userpanel\User;
use \themes\clipone\utility;
use \packages\userpanel\date;
use \packages\base\{json, translator};
use \packages\financial\{transaction, transaction_pay};
$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-edit"></i>
                <span><?php echo translator::trans("tranaction").' #'.$this->transaction->id; ?></span>
				<div class="panel-tools"></div>
            </div>
            <div class="panel-body">
				<form class="create_form" action="<?php echo userpanel\url('transactions/edit/'.$this->transaction->id) ?>" method="post">
					<div class="row">
						<div class="col-sm-6 col-xs-12">
							<?php $this->createField([
								'name' => 'title',
								'label' => translator::trans("transaction.title")
							]);
							$this->createField([
								'name' => 'create_at',
								'label' => translator::trans("transaction.add.create_at"),
								"placeholder" => date::format("Y/m/d H:i:s", $this->transaction->create_at),
								"ltr" => true,
							]);
							?>
						</div>
						<div class="col-sm-6 col-xs-12">
							<?php
							$this->createField([
								'name' => 'user',
								'type' => 'hidden'
							]);
							?>
							<?php
							$this->createField([
								'name' => 'user_name',
								'label' => translator::trans("transaction.user")
							]);
							if ($this->transaction->status != transaction::paid) {
								$this->createField([
									'name' => 'expire_at',
									'label' => translator::trans("transaction.expire_at"),
									"placeholder" => date::format("Y/m/d H:i:s", $this->transaction->expire_at),
									"ltr" => true,
								]);
							}
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<h3 class="text-muted"><?php echo translator::trans("financial.transaction.products"); ?></h3>
							<div class="table-responsive">
								<table class="table table-striped table-hover product-table">
									<?php
									$hasButtons = $this->hasButtons();
									?>
									<thead>
										<tr>
											<th> # </th>
											<th><?php echo translator::trans('financial.transaction.product'); ?></th>
											<th class="hidden-xs"><?php echo translator::trans('financial.transaction.product.decription'); ?></th>
											<th><?php echo translator::trans('financial.transaction.product.number'); ?></th>
											<th><?php echo translator::trans('financial.transaction.product.price_unit'); ?></th>
											<th><?php echo translator::trans('financial.transaction.product.discount'); ?></th>
											<th><?php echo translator::trans('transaction.tax'); ?></th>
											<th><?php echo translator::trans('financial.transaction.product.price.final'); ?></th>
											<?php if($hasButtons){ ?>
											<th>
												<a class="btn btn-xs btn-link tooltips pull-left" title="<?php echo translator::trans('add'); ?>" href="#product-add" data-toggle="modal">
													<i class="fa fa-plus"></i>
												</a>
											</th>
											<?php } ?>
										</tr>
									</thead>
									<tbody>
										<?php
										$x = 1;
										foreach($this->transaction->products as $product){
											$data = [
												'id' => $product->id,
												'title' => $product->title,
												'description' => $product->description,
												'number' => $product->number,
												'price' => $product->price,
												'currency' => $product->currency->id,
												'discount' => $product->discount,
												"vat" => $product->vat,
												"currency_title" => $product->currency->title,
											];
											$this->setButtonParam('productEdit', 'link', '#product-edit');
											$this->setButtonParam('productDelete', 'link', userpanel\url('transactions/product/delete/'.$product->id));
										?>
											<tr data-product='<?php echo json\encode($data); ?>'>
												<td><?php echo $x++; ?></td>
												<td><?php echo $product->title; ?></td>
												<td class="hidden-xs"><?php echo $product->description; ?></td>
												<td><?php echo translator::trans('financial.number', ['number' => $product->number]); ?></td>
												<td><?php echo $this->numberFormat($product->price) . " " . $product->currency->title; ?></td>
												<td><?php echo $this->numberFormat($product->discount ? $product->discount : 0) . " " . $product->currency->title; ?></td>
												<td><?php echo $product->vat; ?> %</td>
												<td><?php echo $this->numberFormat($product->totalPrice($product->currency)) . " " . $product->currency->title; ?></td>
												<?php
													if($hasButtons){
														echo("<td class=\"center\">".$this->genButtons(['productEdit', 'productDelete'])."</td>");
													}
												?>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<?php
					if($this->pays){
						$hasButtons = $this->hasButtons();
					?>
					<div class="row">
						<div class="col-xs-12">
							<h3 class="text-muted"><?php echo translator::trans('pays'); ?></h3>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-pays">
									<thead>
										<tr>
											<th> # </th>
											<th> <?php echo translator::trans('date&time'); ?> </th>
											<th> <?php echo translator::trans('pay.method'); ?> </th>
											<th> <?php echo translator::trans('description'); ?> </th>
											<th> <?php echo translator::trans('transaction.price'); ?> </th>
											<th> <?php echo translator::trans('pay.status'); ?> </th>
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
												$this->setButtonParam('pay_delete', 'link', userpanel\url("transactions/pay/delete/".$pay->id));
											}
											$statusClass = utility::switchcase($pay->status, [
												'label label-danger' => transaction_pay::rejected,
												'label label-success' => transaction_pay::accepted,
												'label label-warning' => transaction_pay::pending
											]);
											$statusTxt = utility::switchcase($pay->status, [
												'pay.rejected' => transaction_pay::rejected,
												'pay.accepted' => transaction_pay::accepted,
												'pay.pending' => transaction_pay::pending
											]);
											$description = $pay->param("description");
										?>
										<tr data-pay='<?php echo json\encode(array(
											"id" => $pay->id,
											"date" => $pay->date,
											"price" => $pay->price,
											"currency" => $pay->currency->toArray(),
											"description" => $description,
											"status" => $pay->status,
										)); ?>'>
											<td><?php echo $x++; ?></td>
											<td class="ltr"><?php echo date::format("Y/m/d H:i:s", $pay->date); ?></td>
											<td><?php
											if (transaction_pay::PAYACCEPTED == $pay->method) {
												$user = User::byId($pay->param('acceptor'));
												echo t('pay.method.payaccepted', ['acceptor' => $user ? $user->getFullName() : '-']);
											} else {
												echo t('pay.method.'.$pay->method);
											}
											?></td>
											<td>
											<?php echo $pay->description ? $pay->description : ""; ?>
												<div class="pay-description btn-block"><?php echo $description ? nl2br($description) : ""; ?></div>
											</td>
											<td><?php echo $this->numberFormat(abs($pay->price)) . " " . $pay->currency->title;; ?></td>
											<td><span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></td>
											<?php
											if($hasButtons){
												echo("<td class=\"center\">".$this->genButtons(['pay_accept', 'pay_reject', 'pay_edit', 'pay_delete'])."</td>");
											}
											?>
										</tr>
									<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<?php
					}
					?>
					<div class="row">
						<div class="col-sm-12">
							<p>
								<a href="<?php echo userpanel\url('transactions'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
								<button type="submit" class="btn btn-teal"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("update") ?></button>
							</p>
						</div>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php if($this->canEditProduct){ ?>
<div class="modal fade" id="product-edit" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	</div>
	<div class="modal-body">
		<form id="editproductform" action="#" method="post" class="form-horizontal">
			<input type="hidden" name="product" value="">
			<?php
			$this->setHorizontalForm('sm-3','sm-9');
			$feilds = [
				[
					'name' => 'product_title',
					'label' => translator::trans("transaction.add.product")
				],
				[
					'name' => 'description',
					'type' => 'textarea',
					'label' => translator::trans("transaction.add.description")
				],
				[
					'name' => 'number',
					'type' => 'number',
					'label' => translator::trans("transaction.add.number"),
					'ltr' => true
				],
				[
					'name' => 'product_price',
					'label' => translator::trans("transaction.add.price"),
					'ltr' => true,
					'step' => 0.001
				],
				[
					'name' => 'discount',
					'label' => translator::trans("transaction.add.discount"),
					'ltr' => true,
					'step' => 0.001
				],
				[
					'name' => 'vat',
					'type' => 'number',
					'label' => t("transaction.tax"),
					'ltr' => true,
					'input-group' => array(
						'first' => array(
							array(
								'type' => 'addon',
								'text' => '%',
							),
						),
					),
					'step' => 0.001,
					'min' => 0,
					'max' => 100,
				],
				[
					'name' => 'product_currency',
					'type' => 'select',
					'label' => translator::trans("financial.settings.currency"),
					'options' => $this->getCurrenciesForSelect()
				]
			];
			foreach($feilds as $input){
				$this->createField($input);
			}
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="editproductform" data-backdrop="static" aria-hidden="true" class="btn btn-success"><?php echo translator::trans('update'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo translator::trans('cancel'); ?></button>
	</div>
</div>
<?php } ?>
<div class="modal fade" id="product-add" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo translator::trans('users.search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="addproductform" action="" method="post" class="form-horizontal">
			<?php
			$this->setHorizontalForm('sm-3','sm-9');
			$feilds = [
				[
					'name' => 'product_title',
					'label' => translator::trans("transaction.add.product")
				],
				[
					'name' => 'description',
					'type' => 'textarea',
					'label' => translator::trans("transaction.add.description")
				],
				[
					'name' => 'number',
					'type' => 'number',
					'label' => translator::trans("transaction.add.number"),
					'ltr' => true
				],
				[
					'name' => 'product_price',
					'label' => translator::trans("transaction.add.price"),
					'ltr' => true,
					'step' => 0.001
				],
				[
					'name' => 'discount',
					'label' => translator::trans("transaction.add.discount"),
					'ltr' => true,
					'step' => 0.001
				],
				[
					'name' => 'vat',
					'type' => 'number',
					'label' => t("transaction.tax"),
					'ltr' => true,
					'input-group' => array(
						'first' => array(
							array(
								'type' => 'addon',
								'text' => '%',
							),
						),
					),
					'step' => 0.001,
					'min' => 0,
					'max' => 100,
				],
				[
					'name' => 'product_currency',
					'type' => 'select',
					'label' => translator::trans("financial.settings.currency"),
					'options' => $this->getCurrenciesForSelect()
				]
			];
			foreach($feilds as $input){
				$this->createField($input);
			}
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="addproductform" data-backdrop="static" aria-hidden="true" class="btn btn-success"><?php echo translator::trans('add'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo translator::trans('cancel'); ?></button>
	</div>
</div>
<?php
	$this->the_footer();

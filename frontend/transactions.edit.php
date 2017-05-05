<?php
use \packages\base;
use \packages\base\json;
use \packages\base\translator;
use \packages\userpanel;
use \themes\clipone\utility;
use \packages\userpanel\date;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-edit"></i>
                <span><?php echo translator::trans("tranaction").' #'.$this->getTransactionData()->id; ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('add'); ?>" href="#product-add" data-toggle="modal" data-original-title=""><i class="fa fa-plus"></i></a>
				</div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <form class="create_form" action="<?php echo userpanel\url('transactions/edit/'.$this->getTransactionData()->id) ?>" method="post">
                        <div class="col-sm-6">
	                        <?php $this->createField(array(
								'name' => 'title',
								'label' => translator::trans("transaction.title")
							));
							?>
                        </div>
						<div class="col-sm-6">
							<input type="hidden" name="user" value="<?php echo $this->getTransactionData()->user->id; ?>">
							<?php $this->createField(array(
								'name' => 'user_name',
								'label' => translator::trans("transaction.user")
							)); ?>
						</div>
						<div class="col-sm-12">
							<table class="table table-striped table-hover product-table">
								<?php
								$hasButtons = $this->hasButtons();
								?>
							    <thead>
							        <tr>
							            <th> # </th>
							            <th><?php echo translator::trans('financial.transaction.product'); ?></th>
							            <th class="hidden-480"><?php echo translator::trans('financial.transaction.product.decription'); ?></th>
							            <th class="hidden-480"><?php echo translator::trans('financial.transaction.product.number'); ?></th>
							            <th class="hidden-480"><?php echo translator::trans('financial.transaction.product.price.base'); ?></th>
							            <th><?php echo translator::trans('financial.transaction.product.discount'); ?></th>
							            <th><?php echo translator::trans('financial.transaction.product.price.final'); ?></th>
							            <?php if($hasButtons){ ?><th></th><?php } ?>
							        </tr>
							    </thead>
							    <tbody>
									<?php
									$x = 1;
									foreach($this->getTransactionData()->products as $product){
										$data = array(
											'id' => $product->id,
											'title' => $product->title,
											'description' => $product->description,
											'number' => $product->number,
											'price' => $product->price,
											'discount' => $product->discount
										);
										$this->setButtonParam('productEdit', 'link', '#product-edit');
										$this->setButtonParam('productDelete', 'link', userpanel\url('transactions/product/delete/'.$product->id));
									?>
										<tr data-product='<?php echo json\encode($data); ?>'>
											<td><?php echo $x++; ?></td>
											<td><?php echo $product->title; ?></td>
											<td class="hidden-480"><?php echo $product->description; ?></td>
											<td class="hidden-480"><?php echo translator::trans('financial.number', ['number'=>$product->number]); ?></td>
											<td class="hidden-480"><?php echo translator::trans('financial.price.rial', ['price'=>$product->price]); ?></td>
											<td class="hidden-480"><?php echo translator::trans('financial.price.rial', ['price'=>$product->disscount ? $product->disscount : 0]); ?></td>
											<td><?php echo translator::trans('financial.price.rial', ['price'=>(($product->price*$product->number)-$product->discount)]); ?></td>
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
						<?php
						if($this->pays){
							$hasdesc = $this->paysHasDiscription();
							$hastatus = $this->paysHasStatus();
							$hasButtons = $this->hasButtons();
						?>
						<h3><?php echo translator::trans('pays'); ?></h3>
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
											$this->setButtonParam('pay_delete', 'link', userpanel\url("transactions/pay/delete/".$pay->id));
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
											echo("<td class=\"center\">".$this->genButtons(['pay_accept', 'pay_reject', 'pay_delete'])."</td>");
										}
										?>
									</tr>
								<?php } ?>
								</tbody>
							</table>
						</div>
						<?php
						}
						?>
						<div class="col-sm-12">
			                <hr>
			                <p>
			                    <a href="<?php echo userpanel\url('transactions'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
			                    <button type="submit" class="btn btn-yellow"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("update") ?></button>
			                </p>
						</div>
	                </form>
                </div>
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
			$feilds = array(
				array(
					'name' => 'title',
					'label' => translator::trans("transaction.add.product")
				),
				array(
					'name' => 'description',
					'type' => 'textarea',
					'label' => translator::trans("transaction.add.description")
				),
				array(
					'name' => 'number',
					'type' => 'number',
					'label' => translator::trans("transaction.add.number")
				),
				array(
					'name' => 'price',
					'type' => 'number',
					'label' => translator::trans("transaction.add.price")
				),
				array(
					'name' => 'discount',
					'type' => 'number',
					'label' => translator::trans("transaction.add.discount")
				)
			);
			foreach($feilds as $input){
				echo $this->createField($input);
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
			$feilds = array(
				array(
					'name' => 'title',
					'label' => translator::trans("transaction.add.product")
				),
				array(
					'name' => 'description',
					'label' => translator::trans("transaction.add.description")
				),
				array(
					'name' => 'number',
					'type' => 'number',
					'label' => translator::trans("transaction.add.number")
				),
				array(
					'name' => 'price',
					'type' => 'number',
					'label' => translator::trans("transaction.add.price")
				),
				array(
					'name' => 'discount',
					'type' => 'number',
					'label' => translator::trans("transaction.add.discount")
				)
			);
			foreach($feilds as $input){
				echo $this->createField($input);
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

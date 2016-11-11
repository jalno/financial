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
    <div class="col-md-12">
        <!-- start: BASIC PRODUCT EDIT -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-plus"></i>
                <span><?php echo translator::trans("transaction.adding_credit"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('add'); ?>" href="#product-add" data-toggle="modal" data-original-title=""><i class="fa fa-plus"></i></a>
				</div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <form class="addingcredit_form" action="<?php echo userpanel\url('transactions/addingcredit'); ?>" method="post">
                        <div class="col-md-6">
							<?php
							if($this->getData('selectclient')){
							?>
								<input type="hidden" name="client" value="">
							<?php
								$this->createField(array(
									'name' => 'user_name',
									'label' => translator::trans("newticket.client"),
									'error' => array(
										'data_validation' => 'transactions.client.data_validation'
									)
								));
							}
							?>
                        </div>
						<div class="col-md-6">
							<?php $this->createField(array(
								'name' => 'price',
								'label' => translator::trans("transaction.price")."(".translator::trans("currency.rial").")",
								'value' => 10000
							)); ?>
						</div>
						<div class="col-md-12">
			                <hr>
			                <p>
			                    <a href="<?php echo userpanel\url('transactions'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
			                    <button type="submit" class="btn btn-yellow"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("submit"); ?></button>
			                </p>
						</div>
	                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- end: BASIC PRODUCT EDIT -->
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

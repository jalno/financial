<?php
use \packages\base;
use \packages\base\translator;

use \packages\userpanel;
use \packages\userpanel\date;

$this->the_header();
?>
<!-- start: BASIC TABLE NEW TRANSACTION -->
<div class="row">
    <form class="create_form" action="<?php echo userpanel\url('transactions/new') ?>" method="post">
        <div class="col-md-7">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo translator::trans("transaction.add"); ?></span>
                </div>
                <div class="panel-body">
                    <input type="hidden" name="user" value="">
                    <?php
					$this->setHorizontalForm('sm-4','sm-8');
					$fields = array(
						array(
							'name' => 'title',
							'label' => translator::trans("transaction.add.title"),
							'class' => 'form-control space'
						),
						array(
							'name' => 'user_name',
							'label' => translator::trans("transaction.user"),
							'class' => 'form-control space'
						),
						array(
							'name' => 'create_at',
							'label' => translator::trans("transaction.add.create_at"),
							'value' => date::format('Y/m/d H:i:s'),
							'class' => 'form-control space'
						),
						array(
							'name' => 'expire_at',
							'label' => translator::trans("transaction.add.expire_at"),
							'value' => date::format('Y/m/d H:i:s', time()+86400),
							'class' => 'form-control space'
						)
					);
					foreach($fields as $field){
						$this->createField($field);
					}
					?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo translator::trans("transaction.add.setting"); ?></span>
                </div>
                <div class="panel-body form-horizontal">
					<?php
					$this->setHorizontalForm('sm-9','sm-3');
					$feilds = array(
						array(
							'name' => 'notification',
							'type' => 'checkbox',
							'label' => translator::trans("transaction.add.notification"),
							'options' => array(
								array(
									'value' => 1
								)
							),
							'value' => 1
						),
						array(
							'name' => 'notification_support',
							'type' => 'checkbox',
							'label' => translator::trans("transaction.add.notification.support"),
							'options' => array(
								array(
									'value' => 1
								)
							),
							'value' => 1
						)
					);
					foreach($feilds as $input){
						echo $this->createField($input);
					}
					?>

                </div>
            </div>
        </div>
		<div class="col-md-12">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo translator::trans("transaction.add.products"); ?></span>
					<div class="panel-tools">
						<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('add'); ?>" href="#product-add" data-toggle="modal" data-original-title=""><i class="fa fa-plus"></i></a>
						<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
					</div>
                </div>
                <div class="panel-body products">
					<div class="alert alert-block alert-info fade in no-product">
					    <h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> یک محصول جدید اضافه کنید</h4>
					    <p>هنوز هیچ محصولی وارد نشده!</p>
					    <p>
							<a class="btn btn-success btn-addproduct" href="#product-add" data-toggle="modal" data-original-title="">محصول جدید</a>
						</p>
					</div>
                </div>
            </div>
			<hr>
        </div>
		<div class="col-md-8">
		</div>
		<div class="col-md-4">
			<button class="btn btn-teal btn-block btn-submit" type="submit"><i class="fa fa-arrow-circle-left"></i>بساز</button>
		</div>
    </form>
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
					'name' => 'product_title',
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
		<button type="submit" form="addproductform" data-backdrop="static" aria-hidden="true" class="btn btn-success product"><?php echo translator::trans('add'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo translator::trans('cancel'); ?></button>
	</div>
</div>
<!-- end: BASIC TABLE NEW TRANSACTION -->
<?php
$this->the_footer();

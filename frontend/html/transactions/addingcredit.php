<?php
use \packages\base;
use \packages\base\json;
use \packages\base\translator;
use \packages\userpanel;
use \themes\clipone\utility;
use \packages\userpanel\date;
use \packages\financial\transaction;
use \packages\financial\transaction_pay;
use \packages\financial\authorization;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-plus"></i>
                <span><?php echo translator::trans("transaction.adding_credit"); ?></span>
				<div class="panel-tools">
				</div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <form class="addingcredit_form" action="<?php echo userpanel\url('transactions/addingcredit'); ?>" method="post">
					<?php if($multyUser = authorization::childrenTypes()){ ?> 
                        <div class="col-sm-6">
							<?php
							$this->createField([
								'name' => 'client',
								'type' => 'hidden'
							]);
							$this->createField([
								'name' => 'client_name',
								'label' => translator::trans("newticket.client"),
								'error' => [
									'data_validation' => 'transactions.client.data_validation'
								]
							]);
							?>
                        </div>
					<?php } ?>
						<div class="<?php echo ($multyUser ? 'col-sm-6': 'col-sm-6 col-sm-offset-3'); ?>">
							<?php $this->createField([
								'name' => 'price',
								'type' => 'number',
								'label' => translator::trans("transaction.addingcredit.price"),
								'ltr' => true,
								'placeholder' => 10000
							]); ?>
						</div>
						<div class="col-sm-12">
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
<?php
	$this->the_footer();

<?php
$this->the_header();
use \packages\userpanel;
use \packages\base\translator;
$account = $this->getBankaccount();
?>
<div class="row">
	<div class="col-md-12">
		<!-- start: BASIC PRODUCT EDIT -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-edit"></i> <?php echo translator::trans("bankaccount_edit")." #{$account->id}"; ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form action="<?php echo(userpanel\url("settings/financial/bankaccounts/edit/".$account->id)); ?>" method="post">
					<div class="row">
						<div class="col-md-6">
							<?php
							$this->createField(array(
								'name' => 'title',
								'label' => translator::trans('bankaccount.title')
							));
							$this->createField(array(
								'name' => 'account',
								'label' => translator::trans('bankaccount.account')
							));
							$this->createField(array(
								'name' => 'cart',
								'label' => translator::trans('bankaccount.cart')
							));
							?>
						</div>
						<div class="col-md-6">
							<?php
							$this->createField(array(
								'name' => 'owner',
								'label' => translator::trans('bankaccount.owner')
							));
							$this->createField(array(
								'name' => 'status',
								'type' => 'select',
								'label' => translator::trans('bankaccount.status'),
								'options' => $this->setStatusForSelect()
							));
							?>
						</div>
					</div>
					<!-- end: CONDENSED TABLE PANEL -->
					<div class="col-md-12">
		                <p>
		                    <a href="<?php echo userpanel\url("settings/financial/bankaccounts"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans("return"); ?></a>
		                    <button type="submit" class="btn btn-yellow"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("update") ?></button>
		                </p>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

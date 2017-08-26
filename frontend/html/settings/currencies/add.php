<?php
use \packages\base\json;
use \packages\base\translator;
use \packages\userpanel;
$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-plus"></i>
                <span><?php echo translator::trans("settings.financial.currency.add"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="currency-add-form" action="<?php echo userpanel\url('settings/financial/currencies/add'); ?>" method="post">
					<div class="row">
						<div class="col-sm-6">
							<?php
							$this->createField(array(
								'name' => 'title',
								'label' => translator::trans("financial.settings.currency.title")
							));
							?>
							<?php
							$this->createField(array(
								'name' => 'change',
								'type' => 'checkbox',
								'options' => [
									[
										'label' => translator::trans("financial.settings.currency.change"),
										'value' => 1,
										'data' => [
											'change' => !empty($this->getCurrencies())
										]
									]
								]
							));
							?>
						</div>
						<div class="col-sm-6">
							<div class="panel panel-white" data-currencies='<?php echo json\encode($this->geCurrenciesForSelect()); ?>'>
								<div class="panel-heading">
									<i class="fa fa-handshake-o"></i>
									<span><?php echo translator::trans("financial.settings.currency.change"); ?></span>
									<div class="panel-tools">
										<a class="btn btn-xs btn-link btn-add tooltips" href="#" title="<?php echo translator::trans('add'); ?>"><i class="fa fa-plus"></i></a>
										<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
									</div>
								</div>
								<div class="panel-body">
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-12">
						<p>
							<a href="<?php echo userpanel\url('settings/financial/currencies'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
							<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("add"); ?></button>
						</p>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

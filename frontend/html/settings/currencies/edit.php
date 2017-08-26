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
                <i class="fa fa-edit"></i>
                <span><?php echo translator::trans("settings.financial.currency.edit"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="currency-edit-form" action="<?php echo userpanel\url("settings/financial/currencies/edit/{$this->currency->id}"); ?>" method="post">
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
								<?php
								$i = 0;
								foreach($this->currency->rates as $rate){
								?>
									<div class="row rates">
										<div class="col-sm-5">
											<?php $this->createField([
												'name' => "rates[{$i}][price]",
												'label' => translator::trans('financial.settings.currency.price'),
												'ltr' => true,
												'type' => 'number',
												'class' => 'form-control rates-price',
												'step' => 0.0001
											]); ?>
										</div>
										<div class="col-sm-5">
											<?php $this->createField([
												'name' => "rates[{$i}][currency]",
												'type' => 'select',
												'label' => translator::trans('financial.settings.currency'),
												'options' => $this->geCurrenciesForSelect(),
												'class' => 'form-control rates-currency'
											]); ?>
										</div>
										<?php $i++; ?>
										<div class="col-sm-2 col-xs-12 text-center">
											<button href="#" class="btn btn-xs btn-bricky tooltips btn-delete" title="<?php echo translator::trans('delete'); ?>" style="margin-top: 30px;">
												<i class="fa fa-times"></i>
											</button>
										</div>
									</div>
								<?php } ?>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-12">
						<p>
							<a href="<?php echo userpanel\url('settings/financial/currencies'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
							<button type="submit" class="btn btn-teal"><i class="fa fa-edit"></i> <?php echo translator::trans("edit"); ?></button>
						</p>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

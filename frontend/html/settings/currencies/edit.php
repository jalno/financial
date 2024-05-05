<?php
use packages\base\Json;
use packages\base\Translator;
use packages\userpanel;
$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-edit"></i>
                <span><?php echo Translator::trans("settings.financial.currency.edit"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="currency-edit-form" action="<?php echo userpanel\url("settings/financial/currencies/edit/{$this->currency->id}"); ?>" method="post">
					<div class="row">
						<div class="col-sm-6">
						<?php $this->createField(array(
							"name" => "title",
							"label" => t("financial.settings.currency.title")
						)); ?>
							<div class="row">
								<div class="col-xs-7">
								<?php $this->createField(array(
									"name" => "prefix",
									"label" => t("financial.settings.currency.prefix"),
								)); ?>
								</div>
								<div class="col-xs-5">
								<?php $this->createField(array(
									"name" => "postfix",
									"label" => t("financial.settings.currency.postfix"),
								)); ?>
								</div>
							</div>
							<div class="row">
								<div class="col-xs-7">
								<?php $this->createField(array(
									"name" => "update_at",
									"label" => t("financial.settings.currency.update_at"),
									"ltr" => true
								)); ?>
								</div>
								<div class="col-xs-5 default-currency-container">
								<?php $this->createField(array(
									"name" => "default",
									"type" => "checkbox",
									"inline" => true,
									"options" => array(
										array(
											"label" => t("financial.default_currency"),
											"value" => 1,
										),
									),
								)); ?>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-3 col-checkbox">
								<?php
								$this->createField(array(
									'name' => 'change',
									'type' => 'hidden',
								));
								$this->createField(array(
									'name' => 'change-checkbox',
									'type' => 'checkbox',
									'options' => [
										[
											'label' => Translator::trans("financial.settings.currency.change"),
											'value' => 1,
											'data' => [
												'change' => !empty($this->getCurrencies())
											]
										]
									]
								));
								?>
								</div>
								<div class="col-sm-9 rounding-container<?php echo !$this->hasRate ? ' rate-inputs' : ''; ?>">
									<div class="row">
										<div class="col-sm-8 col-rounding-behaviour">
											<?php
											$this->createField(array(
												'name' => 'rounding-behaviour',
												'type' => 'select',
												'label' => t('financial.setting.currency.rounding_behaviour'),
												'options' => $this->getRoundingBehavioursForSelect(),
											));
											?>
										</div>
										<div class="col-sm-4 col-rounding-precision">
											<?php
											$this->createField(array(
												'name' => 'rounding-precision',
												'type' => 'number',
												'ltr' => true,
												'label' => t('financial.setting.currency.rounding_precision'),
											));
											?>
										</div>
									</div>
								</div>
							</div>
							<div class="alert alert-info rounding-behaviour-guidance<?php echo !$this->hasRate ? ' rate-inputs' : ''; ?> text-center"><i class="fa fa-spinner fa-plus fa-spin fa-3x fa-align-center"></i></div>
						</div>
						<div class="col-sm-6">
							<div class="panel panel-white<?php echo !$this->hasRate ? ' rate-inputs' : ''; ?>" data-currencies='<?php echo json\encode($this->geCurrenciesForSelect()); ?>'>
								<div class="panel-heading">
									<i class="fa fa-handshake-o"></i>
									<span><?php echo Translator::trans("financial.settings.currency.change"); ?></span>
									<div class="panel-tools">
										<a class="btn btn-xs btn-link btn-add tooltips" href="#" title="<?php echo Translator::trans('add'); ?>"><i class="fa fa-plus"></i></a>
										<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
									</div>
								</div>
								<div class="panel-body">
								<?php
								$i = 0;
								foreach ($this->currency->rates as $rate) {
								?>
									<div class="row rates">
										<div class="col-sm-5 col-xs-12">
										<?php $this->createField([
											'name' => "rates[{$i}][currency]",
											'type' => 'select',
											'label' => Translator::trans('financial.settings.currency'),
											'options' => $this->geCurrenciesForSelect(),
											'class' => 'form-control rates-currency'
										]); ?>
										</div>
										<div class="col-sm-5 col-xs-9">
										<?php
										$this->createField([
											'name' => "rates[{$i}][price]",
											'label' => Translator::trans('financial.settings.currency.price'),
											'ltr' => true,
											'type' => 'number',
											'class' => 'form-control rates-price',
											'step' => 'any'
										]);
										$i++
										?>
										</div>
										<div class="col-sm-2 col-xs-3 text-center">
											<button href="#" class="btn btn-xs btn-bricky tooltips btn-delete" title="<?php echo Translator::trans('delete'); ?>" style="margin-top: 30px;">
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
							<a href="<?php echo userpanel\url('settings/financial/currencies'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool)Translator::getLang()->isRTL()) ? "right" : "left"; ?>"></i> <?php echo Translator::trans('return'); ?></a>
							<button type="submit" class="btn btn-teal"><i class="fa fa-edit"></i> <?php echo Translator::trans("edit"); ?></button>
						</p>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

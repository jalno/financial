<?php
use packages\base\json;
use packages\base\translator;
use packages\userpanel;
use packages\financial\Currency;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-plus"></i>
                <span><?php echo t("settings.financial.currency.add"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="currency-add-form" action="<?php echo userpanel\url("settings/financial/currencies/add"); ?>" method="post">
					<div class="row">
						<div class="col-sm-6">
						<?php $this->createField(array(
							"name" => "title",
							"label" => t("financial.settings.currency.title")
						)); ?>
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
									"label" => t("financial.settings.currency.update_at"),
									"ltr" => true
								)); ?>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-3 col-checkbox">
								<?php
								$this->createField(array(
									"name" => "change",
									"type" => "hidden",
								));
								$this->createField(array(
									"name" => "change-checkbox",
									"type" => "checkbox",
									"options" => [
										[
											"label" => t("financial.settings.currency.change"),
											"value" => 1,
											"data" => [
												"change" => !empty($this->getCurrencies())
											]
										]
									]
								));
								?>
								</div>
								<div class="col-sm-9 rounding-container rate-inputs">
									<div class="row">
										<div class="col-sm-8 col-rounding-behaviour">
											<?php
											$this->createField(array(
												"name" => "rounding-behaviour",
												"type" => "select",
												"label" => t("financial.setting.currency.rounding_behaviour"),
												"options" => $this->getRoundingBehavioursForSelect(),
											));
											?>
										</div>
										<div class="col-sm-4 col-rounding-precision">
											<?php
											$this->createField(array(
												"name" => "rounding-precision",
												"type" => "number",
												"ltr" => true,
												"value" => 0,
												"label" => t("financial.setting.currency.rounding_precision"),
											));
											?>
										</div>
									</div>
								</div>
							</div>
							<div class="alert alert-info rounding-behaviour-guidance rate-inputs"></div>
						</div>
						<div class="col-sm-6">
							<div class="panel panel-white rate-inputs" data-currencies="<?php echo json\encode($this->geCurrenciesForSelect()); ?>">
								<div class="panel-heading">
									<i class="fa fa-handshake-o"></i>
									<span><?php echo t("financial.settings.currency.change"); ?></span>
									<div class="panel-tools">
										<a class="btn btn-xs btn-link btn-add tooltips" href="#" title="<?php echo t("add"); ?>"><i class="fa fa-plus"></i></a>
										<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
									</div>
								</div>
								<div class="panel-body">
								</div>
							</div>
						</div>
					</div>
					<div>
						<a href="<?php echo userpanel\url("settings/financial/currencies"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool)translator::getLang()->isRTL()) ? "right" : "left"; ?>"></i> <?php echo t("return"); ?></a>
						<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo t("add"); ?></button>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

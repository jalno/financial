<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus"></i>
				<span><?php echo Translator::trans('settings.financial.gateways.add'); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form class="create_form" action="<?php echo userpanel\url('settings/financial/gateways/add'); ?>" method="post">
					<div class="numbersfields"></div>
					<div class="row">
						<div class="col-sm-6">
							<?php
                            $this->createField([
                                'name' => 'title',
                                'label' => Translator::trans('financial.gateway.title'),
                            ]);
$this->createField([
    'name' => 'gateway',
    'type' => 'select',
    'label' => Translator::trans('financial.gateway.type'),
    'options' => $this->getGatewaysForSelect(),
]);
$this->createField([
    'name' => 'account',
    'type' => 'select',
    'label' => Translator::trans('financial.gateway.account'),
    'options' => $this->getAccountsForSelect(),
]);
$this->createField([
    'name' => 'status',
    'type' => 'select',
    'label' => Translator::trans('financial.gateway.status'),
    'options' => $this->getGatewayStatusForSelect(),
]);
?>
						</div>
						<div class="col-sm-6">
							<?php
foreach ($this->getGateways()->get() as $gateway) {
    $name = $gateway->getName();
    echo "<div class=\"gatewayfields gateway-{$name}\">";
    foreach ($gateway->getFields() as $field) {
        $this->createField($field);
    }
    echo '</div>';
}
?>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6">
							<div class="panel panel-white">
								<div class="panel-heading">
									<i class="fa fa-usd"></i>
									<span><?php echo Translator::trans('settings.financial.currencies'); ?></span>
									<div class="panel-tools">
										<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
									</div>
								</div>
								<div class="panel-body panel-scroll" style="height: 200px;">
									<?php $this->createField([
									    'name' => 'currency[]',
									    'type' => 'checkbox',
									    'options' => $this->getCurrenciesForSelect(),
									]); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<p>
								<a href="<?php echo userpanel\url('settings/financial/gateways'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool) Translator::getLang()->isRTL()) ? 'right' : 'left'; ?>"></i> <?php echo Translator::trans('return'); ?></a>
								<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo Translator::trans('submit'); ?></button>
							</p>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

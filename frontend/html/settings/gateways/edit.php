<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-edit"></i>
                <span><?php echo t('settings.financial.gateways.edit'); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="create_form" action="<?php echo userpanel\url('settings/financial/gateways/edit/'.$this->getGateway()->id); ?>" method="post">
					<div class="numbersfields"></div>
					<div class="row">
						<div class="col-md-6">
							<?php
                            $this->createField([
                                'name' => 'title',
                                'label' => t('financial.gateway.title'),
                            ]);
$this->createField([
    'name' => 'gateway',
    'type' => 'select',
    'label' => t('financial.gateway.type'),
    'options' => $this->getGatewaysForSelect(),
]);
$this->createField([
    'name' => 'account',
    'type' => 'select',
    'label' => t('financial.gateway.account'),
    'options' => $this->getAccountsForSelect(),
]);
$this->createField([
    'name' => 'status',
    'type' => 'select',
    'label' => t('financial.gateway.status'),
    'options' => $this->getGatewayStatusForSelect(),
]);
if ($options = $this->getCurrenciesForSelect()) {
    ?>
							<div class="panel panel-white">
								<div class="panel-heading">
									<i class="fa fa-usd"></i>
									<span><?php echo t('settings.financial.currencies'); ?></span>
									<div class="panel-tools">
										<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
									</div>
								</div>
								<div class="panel-body panel-scroll" style="height: 200px;">
									<?php $this->createField([
									    'name' => 'currency[]',
									    'type' => 'checkbox',
									    'options' => $options,
									]); ?>
								</div>
							</div>
							<?php } ?>
						</div>
						<div class="col-md-6">
							<?php
    foreach ($this->getGateways() as $gateway) {
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
						<div class="col-md-12">
							<p>
								<a href="<?php echo userpanel\url('settings/financial/gateways'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo Translator::isRTL() ? 'right' : 'left'; ?>"></i> <?php echo t('return'); ?></a>
								<button type="submit" class="btn btn-teal"><i class="fa fa-edit"></i> <?php echo t('edit'); ?></button>
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

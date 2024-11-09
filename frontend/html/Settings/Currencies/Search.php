<?php

use packages\financial\Currency;
use packages\userpanel;
use packages\userpanel\Date;

$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
	<?php
    if (!empty($this->getCurrencies())) {
        $defaultCurrency = Currency::getDefault();
        ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-rss"></i> <?php echo t('settings.financial.currencies'); ?>
				<div class="panel-tools">
				<?php if ($this->canAdd) { ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t('add'); ?>" href="<?php echo userpanel\url('settings/financial/currencies/add'); ?>"><i class="fa fa-plus"></i></a>
				<?php } ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t('search'); ?>" href="#search" data-toggle="modal"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover table-currencies">
					<?php $hasButtons = $this->hasButtons(); ?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo t('financial.settings.currency.title'); ?></th>
								<th><?php echo t('financial.settings.currency.changes'); ?></th>
								<th><?php echo t('financial.settings.currency.update_at'); ?></th>
							<?php if ($hasButtons) { ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
					<?php foreach ($this->getCurrencies() as $currency) { ?>
						<tr>
							<td class="center"><?php echo $currency->id; ?></td>
							<td>
							<?php
                                echo $currency->title;
					    if ($defaultCurrency and $defaultCurrency->id == $currency->id) {
					        ?>
								<span class="label label-gold">
									<div class="label-icon"><i class="fa fa-star"></i></div>
								<?php echo t('financial.default_currency'); ?>
								</span>
							<?php } ?>
							</td>
							<td><span class="badge badge-info"><?php echo $currency->getCountRates(); ?></span></td>
							<td class="ltr"><?php echo Date::format('Q QT', $currency->update_at); ?></td>
						<?php
                        if ($hasButtons) {
                            $this->setButtonParam('edit', 'link', userpanel\url('settings/financial/currencies/edit/'.$currency->id));
                            $this->setButtonParam('delete', 'link', userpanel\url('settings/financial/currencies/delete/'.$currency->id));
                            echo '<td class="center">'.$this->genButtons().'</td>';
                        }
					    ?>
						</tr>
					<?php } ?>
						</tbody>
					</table>
				</div>
				<?php $this->paginator(); ?>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
<div class="modal fade" id="search" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t('search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="currencies_search_form" class="form-horizontal" action="<?php echo userpanel\url('settings/financial/currencies'); ?>" method="GET">
			<?php
            $this->setHorizontalForm('sm-3', 'sm-9');
$feilds = [
    [
        'name' => 'id',
        'type' => 'number',
        'label' => t('ticket.id'),
    ],
    [
        'name' => 'title',
        'label' => t('department.title'),
    ],
    [
        'type' => 'select',
        'label' => t('search.comparison'),
        'name' => 'comparison',
        'options' => $this->getComparisonsForSelect(),
    ],
];
foreach ($feilds as $input) {
    $this->createField($input);
}
?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="currencies_search_form" class="btn btn-success"><?php echo t('search'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

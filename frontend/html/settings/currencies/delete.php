<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
$rates = $this->currency->rates;
?>
<div class="row">
	<div class="col-sm-6">
		<div class="alert alert-block alert-warning fade in">
			<h4 class="alert-heading">
				<i class="fa fa-exclamation-triangle"></i>
				<?php echo t('attention'); ?>!
			</h4>
			<p>
				<?php echo t('financial.setting.currency.delete.warning'); ?>
			</p>
		</div>
	</div>
	<div class="col-sm-6">
		<div class="panel panel-white">
			<div class="panel-heading">
				<i class="fa fa-trash-o"></i> <?php echo t('settings.financial.currency.delete'); ?>
			</div>
			<div class="panel-body">
				<form action="<?php echo userpanel\url("settings/financial/currencies/delete/{$this->currency->id}"); ?>" method="POST" role="form" class="delete_form">
					<div class="row">
						<div class="col-sm-12 form-horizontal">
							<div class="form-group">
								<label class="col-xs-3 control-label"><?php echo t('financial.settings.currency.title'); ?>: </label>
								<div class="col-xs-9"><?php echo $this->currency->title; ?></div>
							</div>
						</div>
					</div>
				<?php if ($rates) { ?>
					<table class="table table-striped">
						<thead>
							<tr>
								<th>#</th>
								<th class="ltr"><?php echo t('financial.settings.currency.price'); ?></th>
								<th><?php echo t('financial.settings.currency'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
                        $i = 0;
				    foreach ($rates as $rate) {
				        ?>
							<tr>
								<td><?php echo ++$i; ?></td>
								<td class="ltr"><?php echo $rate->price; ?></td>
								<td><?php echo $rate->changeTo->title; ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php } ?>
					<div class="row">
						<div class="col-sm-12 text-left">
							<a href="<?php echo userpanel\url('settings/financial/currencies'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool) Translator::getLang()->isRTL()) ? 'right' : 'left'; ?>"></i> <?php echo t('return'); ?></a>
							<button type="submit" class="btn btn-danger"><i class="fa fa-trash-o"></i> <?php echo t('delete'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

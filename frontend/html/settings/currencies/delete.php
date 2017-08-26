<?php
use \packages\base\translator;
use \packages\userpanel;
$this->the_header();
$rates = $this->currency->rates;
?>
<div class="alert alert-block alert-warning fade in">
	<h4 class="alert-heading">
		<i class="fa fa-exclamation-triangle"></i>
		<?php echo translator::trans('attention'); ?>!
	</h4>
	<p>
		<?php echo translator::trans("financial.setting.currency.delete.warning"); ?>
	</p>
</div>
<div class="row">
	<div class="col-sm-<?php echo $rates ? 6 : 12; ?>">
		<div class="panel panel-white">
			<div class="panel-heading">
				<i class="fa fa-trash-o"></i> <?php echo translator::trans("settings.financial.currency.delete"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form action="<?php echo userpanel\url("settings/financial/currencies/delete/{$this->currency->id}"); ?>" method="POST" role="form" class="delete_form">
					<div class="row">
						<div class="col-sm-12 form-horizontal">
							<div class="form-group">
								<label class="col-xs-3 control-label"><?php echo translator::trans("financial.settings.currency.title"); ?>: </label>
								<div class="col-xs-9"><?php echo $this->currency->title; ?></div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12 text-left">
							<a href="<?php echo userpanel\url('settings/financial/currencies'); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('return'); ?></a>
							<button type="submit" class="btn btn-danger"><i class="fa fa-trash-o"></i> <?php echo translator::trans("delete"); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php if($rates){ ?>
	<div class="col-sm-6">
		<div class="panel panel-white">
			<div class="panel-heading">
				<i class="fa fa-handshake-o"></i> <?php echo translator::trans("financial.settings.currency.change"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-sm-12">
						<?php $i = 0; ?>
						<table class="table table-striped">
							<thead>
								<tr>
									<th>#</th>
									<th class="ltr"><?php echo translator::trans('financial.settings.currency.price'); ?></th>
									<th><?php echo translator::trans('financial.settings.currency'); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($this->currency->rates as $rate){ ?>
								<tr>
									<td><?php echo ++$i; ?></td>
									<td class="ltr"><?php echo $rate->price; ?></td>
									<td><?php echo $rate->changeTo->title; ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
</div>
<?php
$this->the_footer();

<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
$account = $this->getBankaccount();
?>
<div class="row">
	<div class="col-xs-12">
		<div class="panel panel-danger">
			<div class="panel-heading">
				<i class="fa fa-trash"></i> <?php echo t('packages.financial.banks.account.delete'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6 col-sm-7 col-xs-12 form-horizontal">
						<div class="form-group">
							<label class="col-xs-5"><?php echo t('packages.financial.banks.account.id'); ?>:</label>
							<div class="col-xs-7">#<?php echo $this->account->id; ?></div>
						</div>
						<div class="form-group">
							<label class="col-sm-5 col-xs-12"><?php echo t('packages.financial.banks.account.user'); ?>:</label>
							<div class="col-sm-7 col-xs-12"><?php echo $this->account->user->getFullName(); ?></div>
						</div>
						<div class="form-group">
							<label class="col-xs-5"><?php echo t('packages.financial.banks.account.bank'); ?>:</label>
							<div class="col-xs-7"><?php echo $this->account->bank->title; ?></div>
						</div>
						<div class="form-group">
							<label class="col-xs-5"><?php echo t('packages.financial.banks.account.cart'); ?>:</label>
							<div class="col-xs-7"><?php echo $this->account->cart ? $this->account->cart : '-'; ?></div>
						</div>
						<div class="form-group">
							<label class="col-xs-5"><?php echo t('packages.financial.banks.account.account'); ?>:</label>
							<div class="col-xs-7"><?php echo $this->account->account ? $this->account->account : '-'; ?></div>
						</div>
						<div class="form-group">
							<label class="col-xs-5"><?php echo t('packages.financial.banks.account.shaba'); ?>:</label>
							<div class="col-xs-7"><?php echo $this->account->shaba ? $this->account->shaba : '-'; ?></div>
						</div>
					</div>
					<div class="col-md-6 col-sm-5 col-xs-12">
						<div class="row">
							<div class="col-xs-12">
								<div class="alert alert-danger">
									<h4 class="alert-heading"> <i class="fa fa-times-circle"></i> <?php echo t('error.fatal.title'); ?> </h4>
								<?php echo t('packages.financial.banks.account.delete.warning'); ?>
								</div>
							</div>
						</div>
						<div class="row">
							<form action="<?php echo userpanel\url("settings/financial/banks/accounts/{$this->account->id}/delete"); ?>" method="POST">
								<div class="col-sm-6 col-xs-12">
									<a href="<?php echo userpanel\url('settings/financial/banks/accounts'); ?>" class="btn btn-default btn-block"><i class="fa fa-chevron-circle-<?php echo Translator::isRTL() ? 'right' : 'left'; ?>"></i> <?php echo t('packages.financial.return'); ?></a>
								</div>
								<div class="col-sm-6 col-xs-12">
									<button type="submit" class="btn btn-danger btn-block"><i class="fa fa-times-circle"></i> <?php echo t('packages.financial.delete'); ?></button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

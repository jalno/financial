<?php
use packages\base\Translator;
use packages\userpanel;
use packages\financial\Bank\Account;
$this->the_header();
?>
<div class="row">
<?php if ($this->canAccept) { ?>
	<div class="col-lg-4 col-md-5 col-sm-5 col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench"></i> <?php echo t("packages.financial.actions"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="tabbable">
					<ul class="nav nav-tabs tab-bricky">
					<?php if ($this->account->status != Account::Active) { ?>
						<li class="active">
							<a href="#accept-banks-account" data-toggle="tab"> <i class="green fa fa-check-square-o text-success"></i> <?php echo t("packages.financial.banks.account.accept"); ?> </a>
						</li>
					<?php
					}
					if ($this->account->status != Account::Rejected) {
					?>
						<li <?php echo $this->account->status == Account::Active ? 'class="active"' : ""; ?>>
							<a href="#reject-banks-account" data-toggle="tab"> <i class="fa fa-ban text-danger"></i> <?php echo t("packages.financial.banks.account.reject"); ?> </a>
						</li>
					<?php } ?>
					</ul>
					<div class="tab-content">
					<?php if ($this->account->status != Account::Active) { ?>
						<div class="tab-pane active" id="accept-banks-account">
							<form action="<?php echo userpanel\url("settings/financial/banks/accounts/{$this->account->id}/accept"); ?>" method="post">
								<div class="text-success form-group">
									<h4 class="alert-heading"><?php echo t("packages.financial.banks.account.accept"); ?></h4>
								<?php echo t("packages.financial.accounts.accept"); ?>
								</div>
								<div class="row">
									<div class="col-sm-12 col-sm-offset-0 col-xs-8 col-xs-offset-2">
										<button type="submit" class="btn btn-success btn-block btn-accept">
											<div class="btn-icons"> <i class="fa fa-check-square"></i> </div>
										<?php echo t("packages.financial.banks.account.accept"); ?>
										</button>
									</div>
								</div>
							</form>
						</div>
					<?php
					}
					if ($this->account->status != Account::Rejected) {
					?>
						<div class="tab-pane<?php echo $this->account->status == Account::Active ? " active" : ""; ?>" id="reject-banks-account">
							<form action="<?php echo userpanel\url("settings/financial/banks/accounts/{$this->account->id}/reject"); ?>" method="post">
								<div class="row">
									<div class="col-xs-12">
									<?php $this->createField(array(
										"type" => "textarea",
										"name" => "reason",
										"label" => t("packages.financial.account.reject.reason"),
										"required" => true,
										"rows" => 8,
									)); ?>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12 col-sm-offset-0 col-xs-8 col-xs-offset-2">
										<button type="submit" class="btn btn-danger btn-block btn-accept">
											<div class="btn-icons"> <i class="fa fa-check-square"></i> </div>
										<?php echo t("packages.financial.banks.account.reject"); ?>
										</button>
									</div>
								</div>
							</form>
						</div>
					<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-8 col-md-7 col-sm-7 col-xs-12">
<?php } else { ?>
	<div class="col-xs-12">
	<?php
	}
	if ($this->account->status == Account::Rejected) {
	?>
		<div class="alert alert-danger">
		<h4 class="alert-heading"> <i class="fa fa-times-circle"></i> خطا </h4>
		<?php echo nl2br($this->account->reject_reason); ?>
		</div>
	<?php } ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus"></i> <?php echo t("packages.financial.banks.account.add"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="edit-banks-account" action="<?php echo(userpanel\url("settings/financial/banks/accounts/{$this->account->id}/edit")); ?>" method="post">
					<div class="row">
						<div class="col-md-6">
						<?php if ($this->multiUser) { ?>
							<div class="row">
								<div class="col-lg-7 col-md-12 col-sm-12 col-xs-12">
								<?php
								$this->createField(array(
										"name" => "user",
										"type" => "hidden",
								));
								$this->createField(array(
										"name" => "user_name",
										"label" => t("packages.financial.banks.account.user"),
								));
								?>
								</div>
								<div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
								<?php $this->createField(array(
										"type" => "select",
										"name" => "bank",
										"label" => t("packages.financial.banks.account.bank"),
										"options" => $this->getBanksForSelect(),
										"required" => true,
								)); ?>
								</div>
							</div>
						<?php
						} else {
							$this->createField(array(
									"type" => "select",
									"name" => "bank",
									"label" => t("packages.financial.banks.account.bank"),
									"options" => $this->getBanksForSelect(),
									"required" => true,
							));
						}
						$this->createField(array(
							"name" => "account",
							"label" => t("packages.financial.banks.account.account"),
							"ltr" => true,
						));
						$this->createField(array(
							"name" => "cart",
							"label" => t("packages.financial.banks.account.cart"),
							"ltr" => true,
							"required" => true,
						));
						?>
						</div>
						<div class="col-md-6">
							<div class="alert alert-warning"><p><?php echo t("packages.financial.accounts.enter.fullname"); ?>.</p></div>
							<?php
							$this->createField(array(
								"name" => "owner",
								"label" => t("packages.financial.banks.account.owner"),
								"required" => true,
							));
							$this->createField(array(
								"name" => "shaba",
								"label" => t("packages.financial.banks.account.shaba"),
								"ltr" => true,
								"placeholder" => "IR123456789101112131415161",
								"required" => true,
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-7 col-md-12 col-sm-6 col-xs-12">
							<p><?php echo t("packages.financial.require.items.marker"); ?></p>
						</div>
						<div class="col-lg-5 col-md-12 col-sm-6 col-xs-12">
							<div class="text-left">
								<a href="<?php echo userpanel\url("settings/financial/banks/accounts"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool)translator::getLang()->isRTL()) ? "right" : "left"; ?>"></i> <?php echo t("packages.financial.return"); ?></a>
								<button type="submit" class="btn btn-teal"><i class="fa fa-check-square-o"></i> <?php echo t("packages.financial.edit") ?></button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
$this->the_footer();

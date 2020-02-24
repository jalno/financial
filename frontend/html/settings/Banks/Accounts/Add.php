<?php
use packages\base\translator;
use packages\userpanel;
$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus"></i> <?php echo t("packages.financial.banks.account.add"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="add-banks-account" action="<?php echo(userpanel\url("settings/financial/banks/accounts/add")); ?>" method="post">
					<div class="row">
						<div class="col-md-6">
						<?php if ($this->multiUser) { ?>
							<div class="row">
								<div class="col-lg-7 col-md-6 col-sm-6 col-xs-12">
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
								<div class="col-lg-5 col-md-6 col-sm-6col-xs-12">
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
						<div class="col-md-7 col-sm-6 col-xs-12">
							<p><?php echo t("packages.financial.require.items.marker"); ?></p>
						</div>
						<div class="col-md-5 col-sm-6 col-xs-12">
							<div class="text-<?php echo ((bool)translator::getLang()->isRTL()) ? "left" : "right"; ?>">
								<a href="<?php echo userpanel\url("settings/financial/banks/accounts"); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-<?php echo ((bool)translator::getLang()->isRTL()) ? "right" : "left"; ?>"></i> <?php echo t("packages.financial.return"); ?></a>
								<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo t("packages.financial.add") ?></button>
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

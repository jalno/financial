<?php
use packages\userpanel;
use packages\base\json;
use themes\clipone\utility;
use packages\financial\Bank;
$this->the_header();
?>
<div class="row">
<?php if ($this->canAdd) { ?>
	<div class="col-lg-4 col-md-5 col-sm-5 col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus"></i> <?php echo t("packages.financial.add"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form id="add-bank-form" action="<?php echo userpanel\url("settings/financial/banks/add"); ?>" method="POST">
					<div class="row">
						<div class="col-xs-12">
						<?php $this->createField(array(
							"name" => "title",
							"label" => t("packages.financial.bank.title"),
							"input-group" => array(
								"left" => array(
									array(
										"type" => "addon",
										"text" => '<i class="fa fa-university"></i>',
									),
								),
							),
						)); ?>
						</div>
					</div>
				</form>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-6 col-md-offset-6 col-sm-8 col-sm-offset-2 col-xs-12">
						<button type="submit" class="btn btn-success btn-block btn-submit" form="add-bank-form">
							<div class="btn-icons"> <i class="fa fa-check-square-o"></i> </div>
						<?php echo t("packages.financial.add"); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-8 col-md-7 col-sm-7 col-xs-12">
<?php } else { ?>
	<div class="col-xs-12">
<?php } ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-university"></i> <?php echo t("packages.financial.banks"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link tooltips" href="#searchBanks" data-toggle="modal" title="<?php echo t("packages.financial.search"); ?>"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
			<?php $banks = $this->getBanks(); ?>
				<div class="table-responsive">
				<?php $hasButtons = $this->hasButtons(); ?>
					<table class="table table-hover table-stripped table-banks"<?php echo !$banks ? ' style="display: none;"' : ""; ?> data-canedit="<?php echo $this->canEdit ? "true" : "false"; ?>" data-candelete="<?php echo $this->canDelete ? "true" : "false"; ?>">
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo t("packages.financial.bank.title"); ?></th>
								<th><?php echo t("packages.financial.bank.status"); ?></th>
							<?php if ($hasButtons) { ?>
								<th class="center"><?php echo t("packages.financial.actions"); ?></th>
							<?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($banks as $bank) { ?>
							<tr data-bank='<?php echo json\encode($bank->toArray()); ?>'>
								<td class="center"><?php echo $bank->id; ?></td>
								<td><?php echo $bank->title; ?></td>
								<?php
								$statusClass = utility::switchcase($bank->status, array(
									"label label-success" => Bank::Active,
									"label label-danger" => Bank::Deactive
								));
								$statusTxt = utility::switchcase($bank->status, array(
									"packages.financial.bank.status.Active" => Bank::Active,
									"packages.financial.bank.status.Deactive" => Bank::Deactive
								));
								?>
								<td><span class="<?php echo $statusClass; ?>"><?php echo t($statusTxt); ?></span></td>
							<?php if ($hasButtons) { ?>
								<td class="center"><?php echo $this->genButtons(array("bank_edit", "bank_delete")); ?></td>
							<?php } ?>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<div class="alert alert-info alert-notfound"<?php echo $banks ? ' style="display: none;"' : ""; ?>>
					<h4 class="alert-heading"> <i class="fa fa-info-circle"></i> <?php echo t("attention"); ?> </h4>
				<?php echo t("packages.financial.banks.notfound"); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="searchBanks" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t("packages.financial.search"); ?></h4>
	</div>
	<div class="modal-body">
		<form id="search-form" class="form-horizontal" action="<?php echo userpanel\url("settings/financial/banks"); ?>" method="GET">
		<?php
		$this->setHorizontalForm("sm-3","sm-9");
		$feilds = array(
			array(
				"name" => "id",
				"type" => "number",
				"label" => t("packages.financial.bank.id")
			),
			array(
				"name" => "title",
				"label" => t("packages.financial.bank.title")
			),
			array(
				"type" => "select",
				"name" => "status",
				"label" => t("packages.financial.bank.status"),
				"options" => $this->getStatusForSelect(),
			),
			array(
				"type" => "select",
				"label" => t("packages.financial.search.comparison"),
				"name" => "comparison",
				"options" => $this->getComparisonsForSelect()
			),
		);
		foreach ($feilds as $input) {
			$this->createField($input);
		}
		?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="search-form" class="btn btn-success"><?php echo t("packages.financial.search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t("packages.financial.cancel"); ?></button>
	</div>
</div>
<?php
$this->the_footer();
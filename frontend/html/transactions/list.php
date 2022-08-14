<?php
use packages\base\Date;
use packages\base\Utility\Safe;
use packages\userpanel;
use themes\clipone\utility;
use packages\financial\transaction;
use themes\clipone\views\TransactionUtilities;

$this->the_header();
$transactions = $this->getTransactions();
$hasTransaction = !empty($transactions);
if ($hasTransaction or $this->canRefund) {
?>
<div class="row refund-request">
	<div class="col-md-6 col-sm-12 col-xs-12">
	<?php if ($this->canRefund) { ?>
		<div class="core-box">
			<h4><?php echo t("currentcredit"); ?>:</h4>
			<div class="row">
				<div class="col-xs-8"><h2 class="user-credit text-center"><?php echo $this->numberFormat($this->user->credit); ?></h2></div>
				<div class="col-xs-4"> <h3 class="user-currency text-center"><?php echo $this->user->currency->title; ?></h3></div>
			</div>
		</div>
	<?php
	}
	if ($hasTransaction) {
	?>
		<div class="core-box">
			<h4><?php echo t("packages.financial.export"); ?>:</h4>
			<?php $this->createField(array(
				"name" => "download",
				"type" => "select",
				"options" => $this->getExportOptionsForSelect(),
			)); ?>
			<div class="row">
				<div class="col-sm-6 col-sm-offset-6">
					<a href="#" class="btn btn-block btn-default" id="download-export-file">
						<div class="btn-icons"> <i class="fa fa-download"></i> </div>
					<?php echo t("packages.financial.download"); ?>
					</a>
				</div>
			</div>
		</div>
	<?php } ?>
	</div>
<?php if ($this->canRefund) { ?>
	<div class="col-md-6 col-sm-12 col-xs-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-money"></i> <?php echo t("packages.financial.refund.request"); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
			<?php if ($accounts = $this->getBanksAccountForSelect()) { ?>
				<form id="refund-form" action="<?php echo userpanel\url("transactions/refund/add"); ?>" method="POST">
				<?php
				if ($this->multiuser) {
					$this->createField(array(
					"name" => "refund_user",
					"type" => "hidden",
				));
				$this->createField(array(
					"name" => "refund_user_name",
					"label" => t("packages.financial.refund.user"),
					"input-group" => array(
						"right" => array(
							array(
								"type" => "addon",
								"text" => '<i class="fa fa-user-o"></i>',
							),
						),
					),
				));
				?>
				<p class="text-muted"><small class="user-currenct-credit">موجودی فعلی:‌ <span class="ltr user-credit"></span> <span class="user-currency"></span></small></p>
				<?php
				}
				$this->createField(array(
					"name" => "refund_price",
					"label" => t("packages.financial.refund.price"),
					"ltr" => true,
					"input-group" => array(
						"right" => array(
							array(
								"type" => "addon",
								"text" => $this->selectedUserForRefund->currency->title,
							),
						),
					),
				));
				$this->createField(array(
					"type" => "select",
					"name" => "refund_account",
					"label" => t("packages.financial.refund.bank.account"),
					"options" => $accounts,
				));
				$errorMessage = '';
				if ($this->selectedUserForRefund) {
					if ($this->selectedUserForRefund->credit > 0) {
						$limits = $this->getCheckoutLimits($this->selectedUserForRefund);
						if (isset($limits['price'], $limits['currency']) and Safe::floats_cmp($limits['price'], $this->selectedUserForRefund->credit) > 0) {
							$errorMessage = t('error.checkout_limit.price.with_price', ['price' => $limits['price'].' '.$this->selectedUserForRefund->currency->title]);
						} elseif (isset($limits['last_time']) and Date::time() - $limits['last_time'] < $limits['period']) {
							$errorMessage = t('error.checkout_limit.period', ['time' => Date::format('Q QT', $limits['last_time'] + $limits['period'])]);
						}
					}
				}
				?>
					<div class="alert alert-danger form-group text-center<?php echo !$errorMessage ? ' hide' : ''; ?>">
						<h5 class="alert-heading"><?php echo $errorMessage; ?></h5>
					</div>
					<div class="row">
						<div class="col-md-6 col-md-offset-6 col-sm-6 col-sm-offset-6 col-xs-12">
							<button type="submit" class="btn btn-success btn-sm btn-block btn-refund"<?php echo !empty($errorMessage) ? " disabled": ""; ?>>
								<div class="btn-icons"> <i class="fa fa-credit-card"></i> </div>
							<?php echo t("packages.financial.create"); ?>
							</button>
						</div>
					</div>
				</form>
			<?php } else { ?>
				<div class="alert alert-info">
					<h4 class="alert-heading"> <i class="fa fa-info-circle"></i> <?php echo t("attention"); ?> </h4>
				<?php echo t("packages.financial.refund.banks.account.notfound"); ?>
				</div>
			<?php } ?>
			</div>
		</div>
	</div>
<?php } ?>
</div>
<?php } ?>
<div class="row">
	<div class="col-sm-12">
	<?php if($hasTransaction) { ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-data"></i> <?php echo t('transactions'); ?>
				<div class="panel-tools">
					<?php if($this->canAddingCredit){ ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t('transaction.adding_credit'); ?>" href="<?php echo userpanel\url('transactions/addingcredit'); ?>"><i class="fa fa-money"></i></a>
					<?php }
					if($this->canAdd){ ?>
						<a class="btn btn-xs btn-link tooltips" title="<?php echo t('transaction.add'); ?>" href="<?php echo userpanel\url('transactions/new'); ?>"><i class="fa fa-plus"></i></a>
				<?php } ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t('search'); ?>" href="#search" data-toggle="modal" data-original-title=""><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover">
						<?php
						$hasButtons = $this->hasButtons();
						?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo t('transaction.title'); ?></th>
								<th><?php echo t('transaction.price'); ?></th>
								<?php if($this->multiuser){ ?><th><?php echo t('transaction.user'); ?></th><?php } ?>
								<th><?php echo t('transaction.createdate'); ?></th>
								<th><?php echo t("transaction.status"); ?></th>
								<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($this->getTransactions() as $transaction) {
								$this->setButtonParam('transactions_view', 'link', userpanel\url("transactions/view/".$transaction->id));
								$this->setButtonParam('transactions_edit', 'link', userpanel\url("transactions/edit/".$transaction->id));
								$this->setButtonParam('transactions_delete', 'link', userpanel\url("transactions/delete/".$transaction->id));
							?>
							<tr>
								<td class="center"><?php echo $transaction->id; ?></td>
								<td><?php echo $transaction->title; ?></td>
								<td><?php echo $this->numberFormat(abs($transaction->price)) . " " . $transaction->currency->title; ?></td>
								<?php if($this->multiuser){ ?>
								<td>
									<?php if ($transaction->user) { ?>
									<a href="<?php echo userpanel\url('users/view/'.$transaction->user->id); ?>"><?php echo $transaction->user->name.' '.$transaction->user->lastname; ?></a>
									<?php
									} else {
										echo "-";
									}
									?>
								</td>
								<?php } ?>
								<td class="ltr"><?php echo $transaction->create_at; ?></td>
								<td><?php echo TransactionUtilities::getStatusLabelSpan($transaction) ?? '-'; ?></td>
								<?php
								if($hasButtons){
									echo("<td class=\"center\">".$this->genButtons()."</td>");
								}
								?>
								</tr>
							<?php
							}
							?>
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
		<form id="transactionsearch" class="form-horizontal" action="<?php echo userpanel\url("transactions"); ?>" method="GET" autocomplete="off">
		<?php
		$this->setHorizontalForm('sm-3','sm-9');
		$this->createField(array(
			'name' => 'id',
			'type' => 'number',
			'label' => t("transaction.id")
		));
		$this->createField(array(
			'name' => 'user',
			'type' => 'hidden',
		));
		$this->createField(array(
			'name' => 'user_name',
			'label' => t("transaction.user"),
		));
		$this->createField(array(
			'name' => 'title',
			'label' => t("transaction.title")
		));
		$this->removeHorizontalForm();
		?>
			<div class="row">
				<label class="col-sm-3 control-label create-at-label"><?php echo t("transaction.createdate"); ?></label>
				<div class="col-sm-9">
					<div class="">
						<div class="col-sm-6">
						<?php $this->createField(array(
							"name" => "create_from",
							"label" => t("packages.financial.search.from"),
							"ltr" => true,
						)); ?>
						</div>
						<div class="col-sm-6">
						<?php $this->createField(array(
							"name" => "create_to",
							"label" => t("packages.financial.search.to"),
							"ltr" => true,
						)); ?>
						</div>
					</div>
				</div>
			</div>
		<?php if ($this->canAccept) { ?>
			<div class="row">
				<div class="col-sm-9 col-sm-offset-3">
				<?php $this->createField(array(
					"name" => "refund",
					"type" => "checkbox",
					"inline" => true,
					"options" => array(
						array(
							"label" => t("packages.financial.search.refund"),
							"value" => true,
						),
					),
				)); ?>
				</div>
			</div>
		<?php
		}
		$this->setHorizontalForm('sm-3','sm-9');
		$this->createField(array(
			"name" => "status",
			"label" => t('transaction.status'),
			'type' => 'select',
			'options' => $this->getStatusForSelect(),
		));
		$this->createField(array(
			"name" => "word",
			"label" => t('packages.financial.search.word'),
		));
		$this->createField(array(
			"name" => "comparison",
			"label" => t('packages.financial.search.comparison'),
			'type' => 'select',
			'options' => $this->getComparisonsForSelect(),
		));
		?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="transactionsearch" class="btn btn-success"><?php echo t("search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

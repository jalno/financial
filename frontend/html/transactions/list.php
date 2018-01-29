<?php
use \packages\userpanel;
use \packages\financial\transaction;
use \packages\base\translator;
use \themes\clipone\utility;
$this->the_header();
?>
<div class="row">
	<div class="col-sm-12">
		<?php if(!empty($this->getTransactions())){ ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-data"></i> <?php echo translator::trans('transactions'); ?>
				<div class="panel-tools">
					<?php if($this->canAddingCredit){ ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('transaction.adding_credit'); ?>" href="<?php echo userpanel\url('transactions/addingcredit'); ?>"><i class="fa fa-money"></i></a>
					<?php }
					if($this->canAdd){ ?>
						<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('transaction.add'); ?>" href="<?php echo userpanel\url('transactions/new'); ?>"><i class="fa fa-plus"></i></a>
				<?php } ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('search'); ?>" href="#search" data-toggle="modal" data-original-title=""><i class="fa fa-search"></i></a>
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
								<th><?php echo translator::trans('transaction.title'); ?></th>
								<th><?php echo translator::trans('transaction.price'); ?></th>
								<?php if($this->multiuser){ ?><th><?php echo translator::trans('user.name'); ?></th><?php } ?>
								<th><?php echo translator::trans('transaction.createdate'); ?></th>
								<th><?php echo translator::trans('service.status'); ?></th>
								<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($this->getTransactions() as $transaction){
								$this->setButtonParam('transactions_view', 'link', userpanel\url("transactions/view/".$transaction->id));
								$this->setButtonParam('transactions_edit', 'link', userpanel\url("transactions/edit/".$transaction->id));
								$this->setButtonParam('transactions_delete', 'link', userpanel\url("transactions/delete/".$transaction->id));
								$statusClass = utility::switchcase($transaction->status, [
									'label label-danger' => transaction::unpaid,
									'label label-success' => transaction::paid,
									'label label-warning' => transaction::refund,
									'label label-inverse' => transaction::expired
								]);
								$statusTxt = utility::switchcase($transaction->status, [
									'transaction.unpaid' => transaction::unpaid,
									'transaction.paid' => transaction::paid,
									'transaction.refund' => transaction::refund,
									'transaction.status.expired' => transaction::expired
								]);
							?>
							<tr>
								<td class="center"><?php echo $transaction->id; ?></td>
								<td><?php echo $transaction->title; ?></td>
								<td><?php echo $transaction->price . " " . $transaction->currency->title; ?></td>
								<?php if($this->multiuser){ ?><td><a href="<?php echo userpanel\url('users/view/'.$transaction->user->id); ?>"><?php echo $transaction->user->name.' '.$transaction->user->lastname; ?></a></td><?php } ?>
								<td><?php echo $transaction->create_at; ?></td>
								<td class="hidden-xs"><span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></span></td>
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
		<h4 class="modal-title"><?php echo translator::trans('search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="transactionsearch" class="form-horizontal" action="<?php echo userpanel\url("transactions"); ?>" method="GET" autocomplete="off">
			<?php
			$this->setHorizontalForm('sm-3','sm-9');
			$feilds = [
				[
					'name' => 'id',
					'type' => 'number',
					'label' => translator::trans("transaction.id")
				],
				[
					'name' => 'title',
					'label' => translator::trans("transaction.title")
				],
				[
					'type' => 'select',
					'label' => translator::trans('transaction.status'),
					'name' => 'status',
					'options' => $this->getStatusForSelect()
				],
				[
					'type' => 'select',
					'label' => translator::trans('search.comparison'),
					'name' => 'comparison',
					'options' => $this->getComparisonsForSelect()
				]
			];
			if($this->multiuser){
				$userSearch = [
					[
						'name' => 'user',
						'type' => 'hidden'
					],
					[
						'name' => 'user_name',
						'label' => translator::trans("transaction.user")
					],
				];
				array_splice($feilds, 2, 0, $userSearch);
			}
			foreach($feilds as $input){
				$this->createField($input);
			}
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="transactionsearch" class="btn btn-success"><?php echo translator::trans("search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo translator::trans('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

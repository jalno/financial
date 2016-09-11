<?php
use \packages\userpanel;
use \packages\financial\transaction;
use \packages\base\translator;
use \themes\clipone\utility;
$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
		<!-- start: BASIC TABLE PANEL -->
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-data"></i> <?php echo translator::trans('transactions'); ?>
				<div class="panel-tools">
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
							foreach($this->dataList as $row){
								$this->setButtonParam('transactions_view', 'link', userpanel\url("transactions/view/".$row->id));
								$this->setButtonParam('transactions_edit', 'link', userpanel\url("transaction/edit/".$row->id));
								$this->setButtonParam('transactions_del', 'link', userpanel\url("transaction/delete/".$row->id));
								$statusClass = utility::switchcase($row->status, array(
									'label label-danger' => transaction::unpaid,
									'label label-success' => transaction::paid,
									'label label-warning' => transaction::refund
								));
								$statusTxt = utility::switchcase($row->status, array(
									'transaction.unpaid' => transaction::unpaid,
									'transaction.paid' => transaction::paid,
									'transaction.refund' => transaction::refund
								));
							?>
							<tr>
								<td class="center"><?php echo $row->id; ?></td>
								<td><?php echo $row->title; ?></td>
								<td><?php echo $row->price." ریال"; ?></td>
								<?php if($this->multiuser){ ?><td><a href="<?php echo userpanel\url('users/view/').$row->user->id; ?>"><?php echo $row->user->name.' '.$row->user->lastname; ?></a></td><?php } ?>
								<td><?php echo $row->create_at; ?></td>
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
			</div>
		</div>
		<!-- end: BASIC TABLE PANEL -->
	</div>
</div>
<?php
$this->the_footer();

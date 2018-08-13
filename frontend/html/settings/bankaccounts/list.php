<?php
$this->the_header();
use \packages\userpanel;
use \themes\clipone\utility;
use \packages\base\translator;
use \packages\financial\bankaccount;
?>
<div class="row">
	<div class="col-md-12">
	<?php if(!empty($this->getBankaccounts())){ ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-external-link-square"></i>لیست حساب ها
				<div class="panel-tools">
					<?php if($this->canAdd){ ?>
						<a class="btn btn-xs btn-link" href="<?php echo userpanel\url("settings/financial/bankaccounts/add"); ?>"><i class="fa fa-plus tip tooltips" title="<?php echo translator::trans("add") ?>"></i></a>
						<?php } ?>
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
								<th><?php echo translator::trans("bankaccount.title"); ?></th>
								<th><?php echo translator::trans("bankaccount.account"); ?></th>
								<th><?php echo translator::trans("bankaccount.cart"); ?></th>
								<th><?php echo translator::trans("financial.bankaccount.shaba"); ?></th>
								<th><?php echo translator::trans("bankaccount.status"); ?></th>
								<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach($this->getBankaccounts() as $account){
								$this->setButtonParam('edit', 'link', userpanel\url("settings/financial/bankaccounts/edit/".$account->id));
								$this->setButtonParam('delete', 'link', userpanel\url("settings/financial/bankaccounts/delete/".$account->id));
								$statusClass = utility::switchcase($account->status, array(
									'label label-success' => bankaccount::active,
									'label label-warning' => bankaccount::deactive
								));
								$statusTxt = utility::switchcase($account->status, array(
									'bankaccount.active' => bankaccount::active,
									'bankaccount.deactive' => bankaccount::deactive
								));
							?>
							<tr>
								<td class="center"><?php echo $account->id; ?></td>
								<td><?php echo $account->title; ?></td>
								<td class="ltr"><?php echo $account->account; ?></td>
								<td class="ltr"><?php echo $account->cart; ?></td>
								<td class="ltr"><?php echo $account->shaba; ?></td>
								<td><span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></span></td>
								<?php
								if($hasButtons){
									echo("<td class=\"center\">".$this->genButtons()."</td>");
								}
								?>
								</tr>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
<?php
$this->the_footer();

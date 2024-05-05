<?php
$this->the_header();
use packages\financial\Bank\Account;
use packages\userpanel;
use themes\clipone\Utility;

?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-external-link-square"></i> <?php echo t('packages.financial.accounts'); ?>
				<div class="panel-tools">
				<?php if ($this->canAdd) { ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo t('packages.financial.add'); ?>" href="<?php echo userpanel\url('settings/financial/banks/accounts/add'); ?>"><i class="fa fa-plus"></i></a>
				<?php } ?>
					<a href="#search" class="btn btn-xs btn-link tooltips" title="<?php echo t('packages.financial.search'); ?>" data-toggle="modal"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
			<?php if ($accounts = $this->getBankaccounts()) { ?>
				<div class="table-responsive">
					<table class="table table-hover">
					<?php $hasButtons = $this->hasButtons(); ?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo t('packages.financial.banks.account.bank'); ?></th>
							<?php if ($this->multiUser) { ?>
								<th><?php echo t('packages.financial.banks.account.user'); ?></th>
							<?php } ?>
								<th><?php echo t('packages.financial.banks.account.account'); ?></th>
								<th><?php echo t('packages.financial.banks.account.cart'); ?></th>
								<th><?php echo t('packages.financial.banks.account.shaba'); ?></th>
								<th><?php echo t('packages.financial.banks.account.status'); ?></th>
								<?php if ($hasButtons) { ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->getBankaccounts() as $account) { ?>
							<tr>
								<td class="center"><?php echo $account->id; ?></td>
								<td><?php echo $account->bank->title; ?></td>
							<?php if ($this->multiUser) { ?>
								<td><a target="_blank" href="<?php echo userpanel\url('users', ['id' => $account->user->id]); ?>"><?php echo $account->user->getFullName(); ?></a></td>
							<?php } ?>
								<td class="ltr"><?php echo $account->account ? $account->account : '-'; ?></td>
								<td class="ltr"><?php echo $account->cart ? $account->cart : '-'; ?></td>
								<td class="ltr"><?php echo $account->shaba ? $account->shaba : '-'; ?></td>
							<?php
                            $statusClass = Utility::switchcase($account->status, [
                                'label label-success' => Account::Active,
                                'label label-warning' => Account::WaitForAccept,
                                'label label-danger' => Account::Rejected,
                                'label label-inverse' => Account::Deactive,
                            ]);
							    $statusTxt = Utility::switchcase($account->status, [
							        'packages.financial.banks.account.status.Active' => Account::Active,
							        'packages.financial.banks.account.status.WaitForAccept' => Account::WaitForAccept,
							        'packages.financial.banks.account.status.Rejected' => Account::Rejected,
							        'packages.financial.banks.account.status.Deactive' => Account::Deactive,
							    ]);
							    ?>
								<td><span class="<?php echo $statusClass; ?>"><?php echo t($statusTxt); ?></span></td>
								<?php
							        if ($hasButtons) {
							            $this->setButtonParam('edit', 'link', userpanel\url('settings/financial/banks/accounts/edit/'.$account->id));
							            $this->setButtonActive('edit', $this->canEdit and (Account::Rejected == $account->status or $this->canAccept));
							            $this->setButtonParam('delete', 'link', userpanel\url('settings/financial/banks/accounts/delete/'.$account->id));
							            echo '<td class="center">'.$this->genButtons().'</td>';
							        }
							    ?>
								</tr>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			<?php
                $this->paginator();
			} else {
			    ?>
				<div class="alert alert-block alert-info ">
					<h4 class="alert-heading"> <i class="fa fa-info-circle"></i> <?php echo t('attention'); ?> </h4>
					<p><?php echo t('financial.settings.bankaccount.notfound'); ?></p>
				</div>
			<?php } ?>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="search" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t('search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="search-form" class="form-horizontal" action="<?php echo userpanel\url('settings/financial/banks/accounts'); ?>" method="GET" autocomplete="off">
			<?php
			    $this->setHorizontalForm('sm-3', 'sm-9');
$feilds = [
    [
        'name' => 'id',
        'type' => 'number',
        'label' => t('packages.financial.banks.account.id'),
        'ltr' => true,
    ],
    [
        'name' => 'bank',
        'label' => t('packages.financial.banks.account.bank'),
    ],
    [
        'name' => 'owner',
        'label' => t('packages.financial.banks.account.owner'),
    ],
    [
        'name' => 'cart',
        'label' => t('packages.financial.banks.account.cart'),
        'ltr' => true,
    ],
    [
        'name' => 'account',
        'label' => t('packages.financial.banks.account.account'),
        'ltr' => true,
    ],
    [
        'name' => 'shaba',
        'label' => t('packages.financial.banks.account.shaba'),
        'ltr' => true,
    ],
    [
        'type' => 'select',
        'label' => t('packages.financial.banks.account.status'),
        'name' => 'status',
        'options' => $this->getStatusForSelect(),
    ],
    [
        'type' => 'select',
        'label' => t('packages.financial.search.comparison'),
        'name' => 'comparison',
        'options' => $this->getComparisonsForSelect(),
    ],
];
if ($this->multiUser) {
    $userSearch = [
        [
            'name' => 'user',
            'type' => 'hidden',
        ],
        [
            'name' => 'user_name',
            'label' => t('packages.financial.banks.account.user'),
        ],
    ];
    array_splice($feilds, 2, 0, $userSearch);
}
foreach ($feilds as $input) {
    $this->createField($input);
}
?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="search-form" class="btn btn-success"><?php echo t('search'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

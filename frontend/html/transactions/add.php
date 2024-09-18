<?php
use packages\base\Translator;
use packages\userpanel;

$this->the_header();
?>
<div class="row">
    <form class="create_form form-horizontal" action="<?php echo userpanel\url('transactions/new'); ?>" method="post">
        <div class="col-md-7">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo t('transaction.add'); ?></span>
                </div>
                <div class="panel-body">
                    <?php
                    $this->setHorizontalForm('sm-4', 'sm-8');
$fields = [
    [
        'name' => 'title',
        'label' => t('transaction.add.title'),
    ],
    [
        'name' => 'user',
        'type' => 'hidden',
    ],
    [
        'name' => 'user_name',
        'label' => t('transaction.user'),
    ],
    [
        'name' => 'create_at',
        'label' => t('transaction.add.create_at'),
        'ltr' => true,
    ],
    [
        'name' => 'expire_at',
        'label' => t('transaction.add.expire_at'),
        'ltr' => true,
    ],
];
foreach ($fields as $field) {
    $this->createField($field);
}
?>
                </div>
            </div>
        </div>
        <div class="col-md-5">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo t('transaction.add.setting'); ?></span>
                </div>
                <div class="panel-body form-horizontal">
					<?php
$this->removeHorizontalForm();
$this->setHorizontalForm('sm-9', 'sm-3');
$feilds = [
    [
        'name' => 'notification',
        'type' => 'checkbox',
        'label' => t('transaction.add.notification'),
        'options' => [
            [
                'label' => '',
                'value' => 1,
            ],
        ],
        'value' => 1,
    ],
    [
        'name' => 'notification_support',
        'type' => 'checkbox',
        'label' => t('transaction.add.notification.support'),
        'options' => [
            [
                'label' => '',
                'value' => 1,
            ],
        ],
        'value' => 1,
    ],
];
foreach ($feilds as $input) {
    $this->createField($input);
}
?>

                </div>
            </div>
        </div>
		<div class="col-md-12">
			<div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-external-link-square"></i>
                    <span><?php echo t('transaction.add.products'); ?></span>
					<div class="panel-tools">
						<a class="btn btn-xs btn-link tooltips" title="<?php echo t('add'); ?>" href="#product-add" data-toggle="modal" data-original-title=""><i class="fa fa-plus"></i></a>
						<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
					</div>
                </div>
                <div class="panel-body products">
					<div class="alert alert-block alert-info fade in no-product">
					    <h4 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> <?php echo t('packages.financial.addnew_product'); ?></h4>
					    <p><?php echo t('packages.financial.not.entered.products.yet'); ?>!</p>
					    <p>
							<a class="btn btn-success btn-addproduct" href="#product-add" data-toggle="modal" title=""><?php echo t('packages.financial.new_product'); ?></a>
						</p>
					</div>
                </div>
            </div>
			<hr>
        </div>
		<div class="col-md-8">
		</div>
		<div class="col-md-4">
			<button class="btn btn-teal btn-block btn-submit" type="submit"><?php echo t('packages.financial.create'); ?> <i class="fa fa-arrow-circle-<?php echo Translator::isRTL() ? 'left' : 'right'; ?>"></i></button>
		</div>
    </form>
</div>
<div class="modal fade" id="product-add" tabindex="-1" data-show="true" role="dialog" data-backdrop="static" data-keyboard="false">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo t('users.search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="addproductform" action="" method="post" class="form-horizontal">
			<?php
            $this->removeHorizontalForm();
$this->setHorizontalForm('sm-3', 'sm-9');
$feilds = [
    [
        'name' => 'product_title',
        'label' => t('transaction.add.product'),
    ],
    [
        'name' => 'description',
        'label' => t('transaction.add.description'),
    ],
    [
        'name' => 'number',
        'type' => 'number',
        'label' => t('transaction.add.number'),
        'ltr' => true,
    ],
    [
        'name' => 'price',
        'label' => t('transaction.add.price'),
        'ltr' => true,
    ],
    [
        'name' => 'discount',
        'label' => t('transaction.add.discount'),
        'ltr' => true,
    ],
    [
        'name' => 'vat',
        'type' => 'number',
        'label' => t('transaction.tax'),
        'ltr' => true,
        'input-group' => [
            'first' => [
                [
                    'type' => 'addon',
                    'text' => '%',
                ],
            ],
        ],
        'step' => 0.001,
        'min' => 0,
        'max' => 100,
    ],
    [
        'name' => 'currency',
        'type' => 'select',
        'label' => t('financial.settings.currency'),
        'options' => $this->getCurrenciesForSelect(),
    ],
];
foreach ($feilds as $input) {
    $this->createField($input);
}
?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="addproductform" data-backdrop="static" aria-hidden="true" class="btn btn-success product"><?php echo t('add'); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo t('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

<?php
use function packages\userpanel\url;

$user = $this->transaction->user;

$this->the_header();
?>
<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="clip-phone-3"></i> <?php echo t('pay.byCredit'); ?>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
                </div>
            </div>
            <div class="panel-body panel-pay-by-credit-body">
                <form class="pay_credit_form" action="<?php echo url("transactions/pay/credit/{$this->transaction->id}"); ?>" method="POST" role="form" data-price="<?php echo $this->transaction->payablePrice(); ?>">
                    <?php
                    $fields = [
                        [
                            'name' => 'currentcredit',
                            'label' => t('currentcredit'),
                            'value' => number_format($user->credit),
                            'disabled' => true,
                            'ltr' => true,
                            'input-group' => [
                                'right' => $this->transaction->currency->title,
                            ],
                        ],
                        [
                            'name' => 'price',
                            'label' => t("pay.price"),
                            'ltr' => true,
                            'input-group' => [
                                'right' => $this->transaction->currency->title,
                            ],
                        ],
                    ];
                    foreach ($fields as $field) {
                        $this->createField($field);
                    }
                    ?>
                    <div class="row">
                        <div class="col-sm-offset-4 col-sm-4">
                            <button class="btn btn-teal btn-block" type="submit">
                                <i class="fa fa-arrow-circle-left"></i>
                            <?php echo t('pay'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

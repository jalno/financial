<?php
use packages\base\Http;
use packages\userpanel\Authentication;
use function packages\userpanel\url;

$parameter = array();
$token = Http::getURIData("token");
if ($token) {
    $parameter["token"] = $token;
}
$isLogin = Authentication::check();

$this->the_header(!$isLogin ? "logedout" : "");
?>
<div class="row">
    <div class="<?php echo !$isLogin ? "col-sm-6 col-sm-offset-3 col-xs-12" : "col-sm-8 col-sm-offset-2"; ?>">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="clip-phone-3"></i> <?php echo t('pay.online.select'); ?>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
                </div>
            </div>
            <div class="panel-body panel-online-pay-body">
                <form class="online-pay-form" action="<?php echo url('transactions/pay/onlinepay/'.$this->transaction->id, $parameter); ?>" method="POST" role="form">
                <?php
                $fields = array(
                    array(
                        'name' => 'currency',
                        'type' => 'hidden',
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'payport',
                        'label' => t('pay.online.payport'),
                        'options' => $this->getPayportsForSelect(),
                    ),
                    array(
                        'name' => 'price',
                        'ltr' => true,
                        'label' => t('pay.price'),
                        'input-group' => array(
                            'right' => array(
                                array(
                                    'type' => 'addon',
                                    'text' => $this->transaction->currency->title,
                                ),
                            ),
                        ),
                    ),
                );
                foreach ($fields as $field) {
                    $this->createField($field);
                }
                ?>
                    <div class="row" style="margin-top: 20px;margin-bottom: 20px;">
                        <div class="col-md-offset-4 col-md-4">
                            <button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo t('pay'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

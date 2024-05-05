<?php
use packages\base\HTTP;
use packages\financial\Authentication;
use packages\userpanel;

$parameter = [];
$token = HTTP::getURIData('token');
if ($token) {
    $parameter['token'] = $token;
}
$isLogin = Authentication::check();

$this->the_header(!$isLogin ? 'logedout' : '');
?>
<div class="row">
	<div class="<?php echo !$isLogin ? 'col-sm-6 col-sm-offset-3 col-xs-12' : 'col-sm-8 col-sm-offset-2'; ?>">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-phone-3"></i> <?php echo t('pay.online.select'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body panel-online-pay-body">
				<form class="online-pay-form" action="<?php echo userpanel\url('transactions/pay/onlinepay/'.$this->transaction->id, $parameter); ?>" method="POST" role="form">
					<?php
                    $fields = [
                        [
                            'name' => 'currency',
                            'type' => 'hidden',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'payport',
                            'label' => t('pay.online.payport'),
                            'options' => $this->getPayportsForSelect(),
                        ],
                        [
                            'name' => 'price',
                            'ltr' => true,
                            'label' => t('pay.price'),
                            'input-group' => [
                                'right' => [
                                    [
                                        'type' => 'addon',
                                        'text' => $this->transaction->currency->title,
                                    ],
                                ],
                            ],
                        ],
                    ];
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
<?php $this->the_footer(!$isLogin ? 'logedout' : '');

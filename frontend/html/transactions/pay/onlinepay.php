<?php
use \packages\userpanel;
use \packages\base\{translator, http};
use \themes\clipone\utility;
use packages\financial\authentication;
$parameter = array();
if ($token = http::getURIData("token")) {
	$parameter["token"] = $token;
}
$isLogin = authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="<?php echo !$isLogin ? "col-sm-6 col-sm-offset-3 col-xs-12" : "col-xs-12"; ?>">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="clip-phone-3"></i> <?php echo translator::trans('pay.online.select'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<form action="<?php echo userpanel\url('transactions/pay/onlinepay/'.$this->transaction->id, $parameter); ?>" method="POST" role="form" class="pay_credit_form">
					<?php $this->createField([
						"name" => "currency",
						"type" => "hidden",
					]); ?>
					<div class="row">
						<div class="col-xs-12">
							<?php
							$this->createField(array(
								'type' => 'select',
								'name' => 'payport',
								'label' => translator::trans("pay.online.payport"),
								'options' => $this->getPayportsForSelect()
							));
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12">
							<?php
							$this->createField(array(
								'type' => 'number',
								'name' => 'price',
								'ltr' => true,
								'label' => translator::trans("pay.price"),
								"input-group" => [
									"right" => [
										[
											"type" => "addon",
											"text" => $this->transaction->currency->title,
										]
									]
								]
							));
							?>
						</div>
					</div>
					<div class="row" style="margin-top: 20px;margin-bottom: 20px;">
						<div class="col-md-offset-4 col-md-4">
							<button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo translator::trans('pay'); ?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

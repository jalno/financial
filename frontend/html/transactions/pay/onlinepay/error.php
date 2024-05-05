<?php
use packages\base\{Translator, HTTP};
use packages\userpanel;
use packages\userpanel\Authentication;
use themes\clipone\Utility;
$error = $this->getError();


$queries = array();
if ($token = HTTP::getURIData("token")) {
	$queries["token"] = $token;
}
$isLogin = Authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12 page-error">
		<div class="error-details col-sm-6 col-sm-offset-3">
			<?php
			if ($error) {
				if($error["error"] == "gateway"){ ?><div class="error-number bricky">500</div><?php } ?>
				<h3><?php echo Translator::trans("pay.online.error." . $error["error"]); ?></h3>
				<p><?php echo Translator::trans("pay.online.error." . $error["error"] . ".text"); ?></p>
				<p>
					<div class="btn-group">
						<a class="btn btn-primary" href="<?php echo userpanel\url("transactions/pay/" . $this->transaction->id, $queries); ?>"><i class="fa fa-repeat"></i> <?php echo Translator::trans("pay.online.backto.pay"); ?></a>
						<a class="btn btn-default" href="<?php echo userpanel\url("transactions/view/" . $this->transaction->id, $queries); ?>"><i class="fa fa-chevron-circle-left"></i> <?php echo Translator::trans("pay.online.backto.transaction"); ?></a>
					</div>
				</p>
			<?php } else { ?>
				<h2 class="text-success"><?php echo Translator::trans("pay.online.success"); ?></h2>
				<p><?php echo Translator::trans("pay.online.success.text", array("transaction_id" => $this->transaction->id)); ?></p>
				<p>
					<a class="btn btn-default btn-block" href="<?php echo userpanel\url("transactions/view/" . $this->transaction->id, $queries); ?>"><i class="fa fa-chevron-circle-left"></i> <?php echo Translator::trans("pay.online.backto.transaction"); ?></a>
				</p>
			<?php } ?>
		</div>
	</div>
</div>
<?php
$this->the_footer(!$isLogin ? "logedout" : "");

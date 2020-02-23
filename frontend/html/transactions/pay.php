<?php
use \packages\userpanel;
use \packages\base\{translator, http};
use \themes\clipone\utility;
use packages\financial\authentication;
$isLogin = authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="<?php echo !$isLogin ? "col-sm-6 col-sm-offset-3 col-xs-12" : "col-xs-12"; ?>">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-money"></i> <?php echo translator::trans('pay.methods'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">
					<?php
					$first = true;
					$parameter = array();
					if ($token = http::getURIData("token")) {
						$parameter["token"] = $token;
					}
					foreach($this->methods as $method){
						$icon = utility::switchcase($method, array(
							'fa fa-university' => 'banktransfer',
							'fa fa-money' => 'onlinepay',
							'fa fa-credit-card' => 'credit'
						));
					?>
					<div class="col-sm-<?php echo ($this->getColumnWidth());if($first)echo(' col-sm-offset-3'); ?>">
						<a href="<?php echo userpanel\url('transactions/pay/'.$method.'/'.$this->transaction->id, $parameter); ?>" class="btn btn-icon btn-block"><i class="<?php echo $icon; ?>"></i> <?php echo translator::trans('pay.method.'.$method); ?></a>
					</div>
					<?php
						if($first){
							$first = false;
						}
					}
					?>
				</div>
			</div>
			<?php if($this->canAccept){ ?>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-4 col-sm-9">
						<span><?php echo translator::trans("packages.financial.transaction.guest_pay_link"); ?></span><br>
						<div class="input-group">
							<input type="text" class="form-control ltr" value="<?php echo userpanel\url("transactions/pay/".$this->transaction->id,array("token" => $this->transaction->token),true); ?>">
							<span class="input-group-btn">
								<button class="btn btn-default" type="button">کپی</button>
							</span>
						</div>
					</div>
					<div class="col-md-2 col-md-offset-6 col-sm-3">
						<a href="<?php echo userpanel\url("transactions/accept/".$this->transaction->id); ?>" class="btn btn-success btn-block"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("paided"); ?></a>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

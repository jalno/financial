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
					<?php if($this->canViewGuestLink){ ?>
					<div class="col-md-6">
						<div class="guest_pay_link">
							<div class="icon">
								<i class="fa fa-bell fa-4x" aria-hidden="true"></i>
							</div>
							<p><?php echo t("financial.transaction.guest.pay.text"); ?></p>
							<div class="input-group">
								<input type="text" class="form-control ltr" value="<?php echo userpanel\url("transactions/pay/".$this->transaction->id,array("token" => $this->transaction->token),true); ?>">
								<span class="input-group-btn">
									<button class="btn btn-default" type="button"><?php echo t("copy"); ?><i class="fa fa-clipboard"></i></button>
								</span>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<?php }else { ?>
					<div class="col-md-12">
						<?php } ?>	
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
							<div class="col-sm-<?php echo ($this->getColumnWidth());if($first)echo(' col-sm-offset-' . ($this->canViewGuestLink ? 1 : 3)); ?>">
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
				</div>
			</div>
			<?php if($this->canAccept){ ?>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-2 col-md-offset-10 col-sm-3 col-sm-offset-9">
						<a href="<?php echo userpanel\url("transactions/accept/".$this->transaction->id); ?>" class="btn btn-success btn-block"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("paided"); ?></a>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

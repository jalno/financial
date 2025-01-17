<?php

use packages\base\Http;
use packages\financial\Authentication;
use function packages\userpanel\url;

$isLogin = Authentication::check();

$this->the_header(!$isLogin ? "logedout" : "");
?>
<div class="row">
	<div class="<?php echo !$isLogin ? "col-sm-6 col-sm-offset-3 col-xs-12" : "col-xs-12"; ?>">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-money"></i> <?php echo t('pay.methods'); ?>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="row">
				<?php if ($this->canViewGuestLink) { ?>
					<div class="col-md-6">
						<div class="guest-pay-link">
							<div class="icon">
								<i class="fa fa-bell fa-4x" aria-hidden="true"></i>
							</div>
							<p><?php echo t("financial.transaction.guest.pay.text"); ?></p>
							<div class="input-group">
								<input type="text" class="form-control ltr" id="financial-guest-pay" value="<?php echo url("transactions/pay/".$this->transaction->id,array("token" => $this->transaction->token),true); ?>">
								<span class="input-group-btn">
									<button class="btn btn-default btn-copy-link" data-clipboard-target="#financial-guest-pay" type="button"><i class="fa fa-clipboard"></i> <?php echo t("copy"); ?></button>
								</span>
							</div>
						</div>
					</div>
					<div class="col-md-6">
				<?php } else { ?>
					<div class="col-md-8 col-sm-offset-2">
				<?php
					}
					$parameter = [];
					if ($token = http::getURIData("token")) {
						$parameter["token"] = $token;
					}
					foreach (array_chunk($this->methods, 2) as $methods) {
				?>
						<div class="row">
						<?php
						$className = 'col-sm-'.(count($methods) == 2 ? 6 : 12);
						foreach ($methods as $method) {
						?>
							<div class="<?php echo $className; ?>">
								<a href="<?php echo url('transactions/pay/'.$method->getName().'/'.$this->transaction->id, $parameter); ?>" class="btn btn-icon btn-block">
									<i class="<?php echo $method->getIcon(); ?>"></i>
								<?php echo t('pay.method.'.$method->getName()); ?>
								</a>
							</div>
						<?php } ?>
						</div>
					<?php } ?>
					</div>
				</div>
			</div>
		<?php if($this->canAccept){ ?>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-2 col-md-offset-10 col-sm-3 col-sm-offset-9">
						<a href="<?php echo url("transactions/accept/".$this->transaction->id); ?>" class="btn btn-success btn-block"><i class="fa fa-check-square-o"></i> <?php echo t("paided"); ?></a>
					</div>
				</div>
			</div>
		<?php } ?>
		</div>
	</div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");

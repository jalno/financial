<?php
use \packages\userpanel;
use \packages\base\translator;
use \themes\clipone\utility;

$this->the_header();
?>
<!-- start: PAGE CONTENT -->
<div class="row">
	<div class="col-sm-12">
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
					foreach($this->methods as $method){
						$icon = utility::switchcase($method, array(
							'clip-banknote' => 'banktransfer',
							'fa fa-money' => 'onlinepay',
							'clip-phone-3' => 'credit'
						));
					?>
					<div class="col-sm-<?php echo ($this->getColumnWidth());if($first)echo(' col-sm-offset-3'); ?>">
						<a href="<?php echo userpanel\url('transactions/pay/'.$method.'/'.$this->transaction->id); ?>" class="btn btn-icon btn-block"><i class="<?php echo $icon; ?>"></i> <?php echo translator::trans('pay.method.'.$method); ?></a>
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
					<div class="col-md-2 col-md-offset-10 col-sm-3 col-sm-offset-9">
						<a href="<?php echo userpanel\url("transactions/accept/".$this->transaction->id); ?>" class="btn btn-success btn-block"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("paided"); ?></a>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
</div>
<?php
$this->the_footer();

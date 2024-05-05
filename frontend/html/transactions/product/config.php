<?php
use \packages\base;
use \packages\base\Json;
use \packages\base\Translator;
use \packages\userpanel;
use \packages\userpanel\Date;
use \themes\clipone\Utility;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-check"></i>
                <span><?php echo Translator::trans("financial.configure"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="create_form" action="<?php echo userpanel\url('transactions/config/'.$this->product->id); ?>" method="post">
					<div class="row">
						<div class="col-sm-8 col-sm-offset-2 col-xs-12">
							<?php
							foreach($this->product->getFields() as $field){
								$this->createField($field);
							}
							?>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12 text-left">
							<p>
								<a href="<?php echo userpanel\url('transactions/view/'.$this->product->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo Translator::trans('financial.return'); ?></a>
								<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo Translator::trans("financial.submit"); ?></button>
							</p>
						</div>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();


<?php
use \packages\base;
use \packages\base\json;
use \packages\base\translator;
use \packages\userpanel;
use \packages\userpanel\date;
use \themes\clipone\utility;

$this->the_header();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-check"></i>
                <span><?php echo translator::trans("financial.configure"); ?></span>
				<div class="panel-tools">
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
            </div>
            <div class="panel-body">
				<form class="create_form" action="<?php echo userpanel\url('transactions/config/'.$this->product->id); ?>" method="post">
					<div class="col-xs-8 col-xs-ofsset-2">
						<?php
						foreach($this->product->getFields() as $field){
							$this->createField($field);
						}
						?>
					</div>
					<div class="col-xs-12 text-left">
						<p>
							<a href="<?php echo userpanel\url('transactions/view/'.$this->product->transaction->id); ?>" class="btn btn-light-grey"><i class="fa fa-chevron-circle-right"></i> <?php echo translator::trans('financial.return'); ?></a>
							<button type="submit" class="btn btn-success"><i class="fa fa-check-square-o"></i> <?php echo translator::trans("financial.submit"); ?></button>
						</p>
					</div>
				</form>
            </div>
        </div>
    </div>
</div>
<?php
$this->the_footer();

<?php
use \packages\base\translator;
use \packages\userpanel;
use \packages\financial\payport as gateway;
use \themes\clipone\utility;
$this->the_header();
?>
<div class="row">
	<div class="col-md-12">
	<?php if(!empty($this->getDataList())){ ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-rss"></i> <?php echo translator::trans("settings.financial.gateways"); ?>
				<div class="panel-tools">
					<?php if($this->canAdd){ ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('add'); ?>" href="<?php echo userpanel\url('settings/financial/gateways/add'); ?>"><i class="fa fa-plus"></i></a>
					<?php } ?>
					<a class="btn btn-xs btn-link tooltips" title="<?php echo translator::trans('search'); ?>" href="#search" data-toggle="modal"><i class="fa fa-search"></i></a>
					<a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
				</div>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover">
						<?php
						$hasButtons = $this->hasButtons();
						?>
						<thead>
							<tr>
								<th class="center">#</th>
								<th><?php echo translator::trans('financial.gateway.title'); ?></th>
								<th><?php echo translator::trans('financial.gateway.type'); ?></th>
								<th><?php echo translator::trans('financial.gateway.status'); ?></th>
								<?php if($hasButtons){ ?><th></th><?php } ?>
							</tr>
						</thead>
						<tbody>
						<?php
							foreach($this->getDataList() as $item){
								$gateway = $this->getGateways()->getByHandler($item->controller);
								if($gateway){
									$gateway = $gateway->getName();
								}
								$this->setButtonParam('view', 'link', userpanel\url("settings/financial/gateways/view/".$item->id));
								$this->setButtonParam('edit', 'link', userpanel\url("settings/financial/gateways/edit/".$item->id));
								$this->setButtonParam('delete', 'link', userpanel\url("settings/financial/gateways/delete/".$item->id));
								$statusClass = utility::switchcase($item->status, array(
									'label label-success' => gateway::active,
									'label label-danger' => gateway::deactive
								));
								$statusTxt = utility::switchcase($item->status, array(
									'financial.gateway.status.active' => gateway::active,
									'financial.gateway.status.deactive' => gateway::deactive
								));
						?>
						<tr>
							<td class="center"><?php echo $item->id; ?></td>
							<td><?php echo $item->title; ?></td>
							<td><?php echo (translator::trans('financial.gateway.'.$gateway) ? translator::trans('financial.gateway.'.$gateway) : $gateway); ?></td>
							<td><span class="<?php echo $statusClass; ?>"><?php echo translator::trans($statusTxt); ?></span></td>
							<?php
							if($hasButtons){
								echo("<td class=\"center\">".$this->genButtons()."</td>");
							}
							?>
						</tr>
						<?php
							}
							?>
						</tbody>
					</table>
				</div>
				<?php $this->paginator(); ?>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
<div class="modal fade" id="search" tabindex="-1" data-show="true" role="dialog">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4 class="modal-title"><?php echo translator::trans('search'); ?></h4>
	</div>
	<div class="modal-body">
		<form id="gateways_search_form" class="form-horizontal" action="<?php echo userpanel\url("settings/financial/gateways"); ?>" method="GET">
			<?php
			$this->setHorizontalForm('sm-3','sm-9');
			$feilds = array(
				array(
					'name' => 'id',
					'type' => 'number',
					'label' => translator::trans("ticket.id")
				),
				array(
					'name' => 'title',
					'label' => translator::trans("department.title")
				),
				array(
					'type' => 'select',
					'label' => translator::trans('search.comparison'),
					'name' => 'comparison',
					'options' => $this->getComparisonsForSelect()
				)
			);
			foreach($feilds as $input){
				echo $this->createField($input);
			}
			?>
		</form>
	</div>
	<div class="modal-footer">
		<button type="submit" form="gateways_search_form" class="btn btn-success"><?php echo translator::trans("search"); ?></button>
		<button type="button" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo translator::trans('cancel'); ?></button>
	</div>
</div>
<?php
$this->the_footer();

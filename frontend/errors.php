<?php
require_once 'header.php';
?>
<div class="row">
	<div class="col-sm-12 page-error">
		<div class="error-number bricky"><?php echo $this->errorcode; ?></div>
		<div class="error-details col-sm-6 col-sm-offset-3">
			<h3><?php echo $this->title[count($this->title) - 1]; ?></h3>
			<?php
            if ($this->errortext) {
                ?>
			<p><?php echo $this->errortext; ?></p>
			<?php
            }
?>
		</div>
	</div>
</div>
<?php
require_once 'footer.php';
?>

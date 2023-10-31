<?php
use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabasePM;
use plugins\NovaPoshta\classes\DatabaseSync;
use plugins\NovaPoshta\classes\invoice\InvoiceModel;

$invoiceModel = new InvoiceModel();
?>
	<style>.statenp-loading:after{ border: 2px solid red: green; }</style>
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<link rel="stylesheet" href="<?php echo PLUGIN_URL; ?>public/css/style.css?ver=<?php echo MNP_PLUGIN_VERSION; ?>"/>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<?php

$invoiceModel->displayNav(); ?>

<div class="container">
	<?php // Hidden form for update plugin DB tables ?>
    <form id="updatedbtablesform" action="admin.php?page=morkvanp_plugin" method="post"
    	style="visibility:hidden;height:0">
        <input type="hidden" id="updatedbhiddeninput" name="updatedbtablesbtnclicked" value="1">
        <input type="submit" class="updatedbtables" name="updatedbtablesname">
    </form>
	<div class="row">
		<?php settings_errors(); ?>
		<div class="settingsgrid">
			<div class="w70">
				<div class="mrkvnp-settings-tab-content">
					<div id="tab-1" class="tab-pane active">
						<h1 style="margin-bottom:0;color:#d9602b;"><?php echo MNP_PLUGIN_NAME ?></h1>
						<form id="mrkvnpformsettings" method="post" name="settingsform" action="options.php">
							<?php
								settings_fields( 'morkvanp_options_group' );
								do_settings_sections( 'morkvanp_plugin' );
								submit_button( null, 'secondary' );
							?>
						</form>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		  <?php require 'card.php' ; ?>
		</div>
	</div>
</div>

<?php
use plugins\NovaPoshta\classes\Database;
use plugins\NovaPoshta\classes\DatabasePM;
use plugins\NovaPoshta\classes\DatabaseSync;

require("functions.php");
loadsrcs();
mnp_display_nav(); ?>
<div class="container">
	<div class="row">
		<h1><?php echo MNP_PLUGIN_NAME ?></h1>
		<?php settings_errors(); ?>
		<hr>
		<h3 style="margin:0;font-size: 1.5em;">Базові налаштування</h3>
		<div class="">
	        <?php
	        if( isset( $_POST['updatedbtables'] ) ) {
	            Database::instance()->upgrade();
	            DatabasePM::instance()->upgrade();
	            DatabaseSync::instance()->synchroniseLocations();
	        } else {

	        }
	        global $wpdb;
	        $results = $wpdb->get_results( 'select distinct updated_at from ' . $wpdb->prefix.'nova_poshta_region' );
	        $time = $results[0]->updated_at ?? 0;
	        $r2 = $wpdb->get_results( 'SELECT COUNT( `ref` ) as result  FROM `' . $wpdb->prefix . 'nova_poshta_city`' );
	        $r2w = $r2[0]->result ?? 0;
	        $r3 = $wpdb->get_results( 'SELECT COUNT( `ref` ) as result FROM `' . $wpdb->prefix . 'nova_poshta_warehouse`' );
	        $r3w = $r3[0]->result ?? 0;
	        $r4 = $wpdb->get_results( 'SELECT COUNT( `ref` ) as result FROM `' . $wpdb->prefix . 'nova_poshta_poshtomat`' );
	        $r4p = $r4[0]->result ?? 0;
	        ?>
	        <p>Останнє оновлення бази (<?php echo ' ' . $r2w . ' міст / ' . $r3w . ' відділень / ' . $r4p . ' поштоматів'; ?> ) відбулось: <?php echo date("Y-m-d H:i:s", $time); ?> (UTC)<p>
	        <form action="admin.php?page=morkvanp_plugin" method="post" style="display: inline;display: inline-flex;margin-left: 10px;">
	            <input type="submit" id="updatedbtables" name="updatedbtables" value="Оновити" class="button button-primary" style="vertical-align:unset"><span id="mrkvupdatedbloader" class=""></span>
	        </form>
        <?php
        	// Show/Hide spinner when DB tables are updating with 'Оновити' button
        	echo '<script>';
        	echo 'const buttonElement = document.getElementById("updatedbtables");';
        	echo 'buttonElement.addEventListener("click", function (event) {
				  showMrkvUpdateDbTablesSpinner();
				});';
        	echo 'const spinnerElement = document.getElementById("mrkvupdatedbloader");';
        	echo 'if(spinnerElement){
        		spinnerElement.innerHTML = "";
        	}';
        	echo '</script>';
        ?>
		<div class="settingsgrid">
			<div class="w70">
				<div class="tab-content">
					<div id="tab-1" class="tab-pane active">
						<form method="post" action="options.php">
							<?php
								settings_fields( 'morkvanp_options_group' );
								do_settings_sections( 'morkvanp_plugin' );
								submit_button();
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

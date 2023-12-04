<div style="margin-top:35px;" class="rightgrid">
	<div class="updatedpro mtb16 p0" >
		<div class="card">
			<div class="card-header">
				<h3>Підтримка</h3>
			</div>
			<div class="card-body">
				<p>
					Якщо у вас виникли проблеми із створенням накладної або щось інше, то звертайтесь до нашої підтримки.
				</p>
				<h5><a href="mailto:support@morkva.co.ua" class="wpbtn button" target="_blank"> Написати</a></h5>
				<h5><a href="https://t.me/morkva_support_bot" class="wpbtn button" target="_blank"><?php echo '<img class="imginwpbtn" src="' .
					plugins_url('img/telegram.png', __FILE__) . '" />'; ?> Написати</a></h5>


				<p>Щось не працює? (версія <?php echo MNP_PLUGIN_VERSION; ?>)<br>Можливо в оновленій версії уже вирішена ваша проблема.</p>
				<?php
					$path = NOVA_POSHTA_TTN_SHIPPING_PLUGIN_DIR . '/public/partials/morkvanp-plugin-invoices-page.php';
					if (!file_exists($path)) { ?>
						<a target="_blank" href="plugin-install.php?tab=plugin-information&amp;plugin=nova-poshta-ttn&amp;section=changelog&amp;TB_iframe=true&amp;width=772&amp;height=374" class="thickbox open-plugin-details-modal" >встановити останню версію плагіна</a>
					<?php } else { ?>
						<a target="_blank" href="plugin-install.php?tab=plugin-information&amp;plugin=nova-poshta-ttn&amp;section=changelog&amp;TB_iframe=true&amp;width=772&amp;height=374" class="thickbox open-plugin-details-modal" >Встановити останню версію плагіна</a>
					<?php } ?>
			</div>
		</div>
	</div>
	<div class="updatedpro mtb16 p0" >
		<div class="card">
			<div class="card-header">
				<h3>Про версія</h3>
			</div>
			<div class="card-body">
				<p>Отримайте більше функціоналу з Про-версією</p>
				<h5>
					<a href="https://morkva.co.ua/shop/nova-poshta-ttn-pro-lifetime/" class="button button-primary">Детальніше</a>
				</h5>
			</div>
		</div>
	</div>
	<div class="clear"></div>
</div>

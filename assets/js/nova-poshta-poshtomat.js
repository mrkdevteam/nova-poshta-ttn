jQuery(document).ready(function() {
  	if (jQuery('form[name="checkout"]').length == 0) return; // On Checkout page only
  		console.log('Поточний js-файл nova-poshta-poshtomat.js');
	    /**
		 * JS Class for 'nova_poshta_shipping_method' (на відділення) and
		 * for 'nova_poshta_shipping_method_poshtomat' (на поштомат)
		 */
	    class NPWarehouse {

	    	static config = {
		    	npwhOffFields: ['_address_1', '_address_2', '_city', '_state', '_postcode',
		    		'_mrkvnp_street', '_mrkvnp_house', '_mrkvnp_flat', '_mrkvnp_patronymics'],
		    	npwhOnFields: ['_nova_poshta_region', '_nova_poshta_city', '_nova_poshta_warehouse'],
			}

	    	constructor(shippingMethodName, fieldsLocation) {
	    		this.shippingMethodName = shippingMethodName;
		        this.fieldsLocation = fieldsLocation;

		        this.npwhOffFields = NPWarehouse.config.npwhOffFields;
	    		this.npwhOnFields = NPWarehouse.config.npwhOnFields;
	    	}

	    	addSelect2() {
				let fieldNames = NPWarehouse.makefieldNamesIds(this.npwhOnFields, this.fieldsLocation);
	    		for (let i = 0; i < fieldNames.length; i++) {
				  	jQuery(fieldNames[i]).select2({ 
				  		"language" : {
				  			"noResults": function(){
				  				var current_lang = jQuery('html').attr('lang');

								var not_found_text = 'No results found';

								if(current_lang == 'uk' || current_lang == 'uk-UA')
								{
									not_found_text = 'Нічого не знайдено';
								}
								if(current_lang == 'ru' || current_lang == 'ru-RU')
								{
									not_found_text = 'Результаты не найдены';
								}
					           return not_found_text;
					       }
				  		}
				  	 });
				}
	    	}

	    	static makefieldNamesIds(fields = [], fieldsLocation) {
	        	let fieldNames = fields.map(item => '#' + fieldsLocation + item);
				return fieldNames;
	        }

		    offWCFields() {
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.npwhOffFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	        onNPwhFields() {
	        	const fieldNames = NPWarehouse.makefieldNamesIds(this.npwhOnFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).prop('disabled', false).closest('.form-row').show();
	        }

	        offWCBillingFields() { // Off billing WooCommerce fields when shipping fields are active
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.npwhOffFields, 'billing');
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	        offNPwhBillingFields() { // Off billing nova poshta fields when shipping fields are active
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.npwhOnFields, 'billing');
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	    }

	    /**
		 * JS Class for 'npttn_address_shipping_method' (на адресу)
		 */
	    class NPAddress {

	    	static config = {
		    	npadrOffFields: ['_address_1', '_address_2', '_city', '_state', '_postcode',
		    		'_mrkvnp_street', '_mrkvnp_house', '_mrkvnp_flat', '_mrkvnp_patronymics',
		    		'_nova_poshta_warehouse'],
		    	npadrOnFields: ['_nova_poshta_region', '_nova_poshta_city',
		    		'_mrkvnp_street', '_mrkvnp_house', '_mrkvnp_flat', '_mrkvnp_patronymics'],
			}

	    	constructor(shippingMethodName, fieldsLocation) {
	    		this.shippingMethodName = shippingMethodName;
		        this.fieldsLocation = fieldsLocation;

		        this.npadrOffFields = NPAddress.config.npadrOffFields;
	    		this.npadrOnFields = NPAddress.config.npadrOnFields;

	    		this.addSelect2;
	    	}

	    	addSelect2() {
				let fieldNames = [
					'#' + this.fieldsLocation + '_nova_poshta_region',
					'#' + this.fieldsLocation + '_nova_poshta_city'
				];
	    		for (let i = 0; i < fieldNames.length; i++) {
				  	jQuery(fieldNames[i]).select2();
				}
	    	}

		    offWCFields() {
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.npadrOffFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	        onNPAdrFields() {
	        	const fieldNames = NPWarehouse.makefieldNamesIds(this.npadrOnFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).prop('disabled', false).closest('.form-row').show();
	        }

	    }

	    /**
	     * JS Class for show fields on Checkout page
	     */
	    class CheckoutPage {
	    	static config = {
		    	wcOnFields: ['_address_1', '_address_2', '_city', '_state', '_postcode'],
		    	npOffFields: ['_nova_poshta_region', '_nova_poshta_city', '_nova_poshta_warehouse',
		    		'_mrkvnp_street', '_mrkvnp_house', '_mrkvnp_flat', '_mrkvnp_patronymics'],
			}

	  		constructor() {
	  			this.shippingMethod = this.getMethod();
	  			this.fieldsLocation = this.getLocation() ? 'shipping' : 'billing';

	  			this.wcOnFields = CheckoutPage.config.wcOnFields;
	  			this.npOffFields = CheckoutPage.config.npOffFields;
	  			this.npwh = new NPWarehouse(this.shippingMethod, this.fieldsLocation, NPWarehouse.config);
	  		}

	  		onNPwhFields() { // Nova Poshta Warehouse
	  			this.npwh.onNPwhFields();
	  			this.npwh.offWCFields();
	  			if (this.isShipToDiffAdr()) {
	  				this.npwh.offNPwhBillingFields();
	  				this.npwh.offWCBillingFields();
				}
	  			this.npwh.addSelect2();
	  		}

	  		onNpAdrFields() { // Nova Poshta on Address
	  			let npadr = new NPAddress(this.shippingMethod, this.fieldsLocation, NPAddress.config);
	  			npadr.offWCFields();
	  			npadr.onNPAdrFields();
	  			if (this.isShipToDiffAdr()) {
	  				this.npwh.offNPwhBillingFields();
	  				this.npwh.offWCBillingFields();
				}
	  			npadr.addSelect2();
	  		}

	  		offNPFields() {
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.npOffFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	  		onWCFields() {
	        	const fieldNames = NPWarehouse.makefieldNamesIds(this.wcOnFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).prop('disabled', false).closest('.form-row').show();
	        }

	        offWCFields() {
		    	const fieldNames = NPWarehouse.makefieldNamesIds(this.wcOnFields, this.fieldsLocation);
	        	const elementsString = fieldNames.join(',');
			    jQuery(elementsString).attr('disabled', 'disabled').closest('.form-row').hide();
	        }

	  		getMethod() {
	    		var value = jQuery('input[name^=shipping_method][type=radio]:checked').val();
				if (!value) value = jQuery('input#shipping_method_0').val();
				if (!value) value = jQuery('input[name^=shipping_method][type=hidden]').val();
				return value;
		    }

		    getLocation() {
		    	let isAdrChk = this.isShipToDiffAdr();
		    	if (isAdrChk) return true;
		    	return false;
		    }

		    isShipToDiffAdr() {
		     	 return jQuery('#ship-to-different-address-checkbox').is(':checked');
		    }

		    setFieldsNames() {
	        	let whName, pmName, shippingMethod = this.getMethod(), wp_lang = jQuery("html").attr("lang");
	        	if ('uk' !== wp_lang && 'uk-UA' !== wp_lang && 'ru' !== wp_lang && 'ru-RU' !== wp_lang) wp_lang = 'other';
	        	const fieldsTranslation = {
	        		'uk': ['Відділення', 'Поштомат'],
	        		'uk-UA': ['Відділення', 'Поштомат'],
	        		'ru-RU': ['Отделение', 'Почтомат'],
	        		'ru': ['Отделение', 'Почтомат'],
	        		'other': ['Warehouse', 'Postomat']
	        	}
	        	if (wp_lang) { whName = fieldsTranslation[wp_lang][0]; pmName = fieldsTranslation[wp_lang][1]; }
		      	if ( 'nova_poshta_shipping_method' == shippingMethod ) {
			        jQuery('#' + this.fieldsLocation + '_nova_poshta_warehouse_field label')
			        	.html(whName+'<span>&nbsp;*</span>');
			    }
			    if ( 'nova_poshta_shipping_method_poshtomat' == shippingMethod ) {
			        jQuery('#' + this.fieldsLocation + '_nova_poshta_warehouse_field label')
			        	.html(pmName+'<span>&nbsp;*</span>');
			    }
	        }

	        setFieldsPlaceholders() {
	        	let whPh, pmPh, shippingMethod = this.getMethod(), wp_lang = jQuery("html").attr("lang");
	        	if ('uk' !== wp_lang && 'uk-UA' !== wp_lang && 'ru' !== wp_lang && 'ru-RU' !== wp_lang) wp_lang = 'other';
	        	const fieldsTranslation = {
	        		'uk': ['Оберіть відділення', 'Оберіть поштомат',],
	        		'uk-UA': ['Оберіть відділення', 'Оберіть поштомат',],
	        		'ru-RU': ['Выберите отделение', 'Выберите почтомат'],
	        		'ru': ['Выберите отделение', 'Выберите почтомат'],
	        		'other': ['Choose warehouse', 'Choose postomat']
	        	}
	        	if (wp_lang) { whPh = fieldsTranslation[wp_lang][0]; pmPh = fieldsTranslation[wp_lang][1]; }
		      	if ( 'nova_poshta_shipping_method' == shippingMethod ) {
			        jQuery('#select2-' + this.fieldsLocation + '_nova_poshta_warehouse-container .select2-selection__placeholder')
			        	.text(whPh);
			    }
			    if ( 'nova_poshta_shipping_method_poshtomat' == shippingMethod ) {
			        jQuery('#select2-' + this.fieldsLocation + '_nova_poshta_warehouse-container .select2-selection__placeholder')
			        	.text(pmPh);
			    }
	        }

	        addHiddenFields(regionRef, regionName, location) {
	        	let elregionref = '<input type="hidden" name="npregionref" location="'+location+'" value="' + regionRef +'"></input>';
	            let elregionname = '<input type="hidden" name="npregionname" location="'+location+'" value="' + regionName +'"></input>';
	        	if ( jQuery( "input[name=npregionref]" ).length == 0 ) {
	                jQuery('form[name=checkout]').append(elregionref);
	            } else {
	                jQuery( "input[name=npregionref]" ).remove();
	                jQuery('form[name=checkout]').append(elregionref);
	            }
	            if ( jQuery( "input[name=npregionname]" ).length == 0 ) {
	                jQuery('form[name=checkout]').append(elregionname);
	            } else {
	                jQuery( "input[name=npregionname]" ).remove();
	                jQuery('form[name=checkout]').append(elregionname);
	            }
	        }

	        setAsteriskColor() { 
	        	jQuery('#' + this.fieldsLocation + '_nova_poshta_city_field label abbr').removeClass('required');
	        	jQuery('#' + this.fieldsLocation + '_nova_poshta_region_field label > .optional').html('<span class="asterisk-color">*</span>');
	        	jQuery('#' + this.fieldsLocation + '_mrkvnp_street_field label > .optional').html('<span class="asterisk-color">*</span>');
            	jQuery('#' + this.fieldsLocation + '_mrkvnp_house_field label > .optional').html('<span class="asterisk-color">*</span>');
                jQuery('#' + this.fieldsLocation + '_mrkvnp_patronymics_field label > .optional').html('<span class="asterisk-color">*</span>');
                if(!jQuery('#' + this.fieldsLocation + '_mrkvnp_patronymics_field').hasClass('mrkv-np-not-required'))
            	{
            		jQuery('#' + this.fieldsLocation + '_mrkvnp_patronymics_field label > .optional').html('<span class="asterisk-color">*</span>');
            	}
	        }

	  	}

	  	var mrkvnpCurrentMethod = ''; // For get current shipping method by radio button 'Доставка'
	  	var mrkvnpelcountry = ''; // For 'Країна' plugin custom field
	  	jQuery('#billing_address_1_field').removeClass('woocommerce-validated');

	  	// If 'Країна' field exists in Сheckout.
		if (jQuery('#billing_country_field').length || jQuery('#shipping_country_field').length) {
		  	var mrkvnpCountry = jQuery('#billing_country');
		  	// Check if checkout has shipping switcher
			if(jQuery('#ship-to-different-address-checkbox').length){
				// Check if shipping checkbox checked
			    if(jQuery('#ship-to-different-address-checkbox').is(':checked')){
			        mrkvnpCountry = jQuery('#shipping_country');
			    }
			    else{
			        mrkvnpCountry = jQuery('#billing_country');    
			    }
			}
		
			jQuery(mrkvnpCountry).on('change', () => {
				mrkvnpCountry = mrkvnpCountry;
			});
			if ('UA' !== mrkvnpCountry.val()) { // If 'Країна' field exists
			// and current country is not Ukraine Nova Poshta fields are removed from Checkout
					const checkoutPage = new CheckoutPage();
					checkoutPage.offNPFields();
				    checkoutPage.onWCFields();
			}
			jQuery( mrkvnpCountry ).on( 'change', () => {
				if ('UA' !== mrkvnpCountry.val()) {
					const checkoutPage = new CheckoutPage();
					checkoutPage.offNPFields();
				    checkoutPage.onWCFields();
				}
			});
		} else { // If 'Країна' field does not exist in Сheckout
			mrkvnpelcountry = jQuery('<input type="hidden" name="mrkvnpcountry" value="UA"></input>');
			jQuery('form[name=checkout]').append(mrkvnpelcountry);
			mrkvnpCountry = mrkvnpelcountry;
			const checkoutPage = new CheckoutPage();
			checkoutPage.offNPFields();
		    checkoutPage.onWCFields();
		}

		// Save local storage shipping method
		if (localStorage) {
            localStorage.setItem('ship_method', '');
            localStorage.setItem('billing_city_np', '');
            localStorage.setItem('np_change_bil_ship', '');
        }

        if(jQuery('#ship-to-different-address-checkbox').length && jQuery('#ship-to-different-address-checkbox').is(':checked'))
        {
        	localStorage.setItem('np_change_bil_ship', jQuery('#ship-to-different-address-checkbox').is(':checked'));
        }
        else
        {
        	localStorage.setItem('np_change_bil_ship', false);
        }

		// Change shipping method with radio button 'Доставка'
		jQuery( document.body ).on( 'updated_checkout', () => {

			const cityRef = (jQuery('#billing_nova_poshta_city').is(":visible"))
				? jQuery('#billing_nova_poshta_city').val()
				: jQuery('#shipping_nova_poshta_city').val();

				let mrkvShippingMethods = document.querySelectorAll('#shipping_method .shipping_method');

				var has_change_ship = false;

			if(jQuery('#ship-to-different-address-checkbox').length)
	        {
	        	if(jQuery('#ship-to-different-address-checkbox').is(':checked') == localStorage.getItem("np_change_bil_ship"))
	        	{
	        		has_change_ship = false;
	        	}
	        	else
	        	{
	        		has_change_ship = true;
	        	}
	        }

			// Check loacl storage
			if (localStorage && (mrkvShippingMethods.length != 0)) {
				// Get shipping method
                var ship_method = '';

                if(jQuery('select.woocommerce-shipping-methods option:selected').length){
                	ship_method = jQuery('select.woocommerce-shipping-methods option:selected').val();
                }
                if(jQuery('.woocommerce-shipping-methods input:checked').length){
                	ship_method = jQuery('.woocommerce-shipping-methods input:checked').val();
                }
                if(jQuery('input.shipping_method:checked').length){
                	ship_method = jQuery('input.shipping_method:checked').val();
                }
                if(jQuery('input.shipping_method').length == 1){
                	ship_method = jQuery('input.shipping_method').val();
                }

                // Check ship method
                if(localStorage.getItem("ship_method") === null){  
                	// Set shipping method                  
                    localStorage.setItem('ship_method', ship_method);
                }
                else{
                	// Check ship method
                    if(localStorage.getItem("ship_method") == ship_method && !has_change_ship){
                    	// Set shipping method     
                        localStorage.setItem('ship_method', ship_method);
                        if((localStorage.getItem("billing_city_np") === null || localStorage.getItem("billing_city_np") === '') && cityRef){
                        	localStorage.setItem('billing_city_np', cityRef);
                        	calcNPAllDeliveries();
                        	return;
                        }

                        if(cityRef && cityRef != localStorage.getItem("billing_city_np")){
                        	if (cityRef) calcNPAllDeliveries();
                        	return;
                        }

                        return;
                    }
                    if(localStorage.getItem("ship_method") == ship_method)
                    {
                		return;
                    }
                    // Set shipping method     
                    localStorage.setItem('ship_method', ship_method);
                }

            }
            
			

			// Make nova poshta fields empty
			jQuery('#billing_nova_poshta_region').find('option:selected').prop("selected",false);
			jQuery('#billing_nova_poshta_region').prop('selectedIndex',0);
			jQuery('#billing_nova_poshta_city').prop('selectedIndex',0);
			jQuery('#billing_nova_poshta_warehouse').prop('selectedIndex',0);
			jQuery('#shipping_nova_poshta_region').find('option:selected').prop("selected",false);
			jQuery('#shipping_nova_poshta_region').prop('selectedIndex',0);
			jQuery('#shipping_nova_poshta_city').prop('selectedIndex',0);
			jQuery('#shipping_nova_poshta_warehouse').prop('selectedIndex',0);

			if(mrkvShippingMethods.length == 0){
			    const checkoutPage = new CheckoutPage();
			      checkoutPage.offNPFields();
			      checkoutPage.onWCFields();
			}

		    for (const mrkvShippingMethod of mrkvShippingMethods){
			    if (jQuery(mrkvShippingMethod).is(":checked") ||
			    	'hidden' == jQuery(mrkvShippingMethods).attr('type')) {
                    if (mrkvShippingMethod.value.indexOf('local_pickup') >= 0
						|| mrkvShippingMethod.value.indexOf('flat_rate') >= 0
						|| mrkvShippingMethod.value.indexOf('free_shipping') >= 0
			        	&& 'UA' == mrkvnpCountry.val()) {
						const checkoutPage = new CheckoutPage();
			        	checkoutPage.offNPFields();
			        	checkoutPage.onWCFields();
					}                    
			        if ('nova_poshta_shipping_method' == mrkvShippingMethod.value
			        	&& 'UA' == mrkvnpCountry.val()) { // UA and to warehouse
						mrkvnpCurrentMethod = mrkvShippingMethod.value;
			            const checkoutPage = new CheckoutPage();
			        	checkoutPage.onNPwhFields();
			        	checkoutPage.setFieldsNames();
			        	checkoutPage.setFieldsPlaceholders();
			        	checkoutPage.offWCFields();
			        	if (cityRef) calcNPAllDeliveries(); // show delivery costs
			        	checkoutPage.setAsteriskColor();
			        } else if ('nova_poshta_shipping_method_poshtomat' == mrkvShippingMethod.value
			        	&& 'UA' == mrkvnpCountry.val()) { // UA and to postomat
						mrkvnpCurrentMethod = mrkvShippingMethod.value;
			        	const checkoutPage = new CheckoutPage();
			        	checkoutPage.onNPwhFields();
			        	checkoutPage.setFieldsNames();
			        	checkoutPage.setFieldsPlaceholders();
			        	checkoutPage.offWCFields();
			        	if (cityRef) calcNPAllDeliveries(); // show delivery costs
			        	checkoutPage.setAsteriskColor();
			        } else if ('npttn_address_shipping_method' == mrkvShippingMethod.value
			        	&& 'UA' == mrkvnpCountry.val()) { // UA and to address
			        	const checkoutPage = new CheckoutPage();
			        	checkoutPage.onNpAdrFields();
			        	checkoutPage.offWCFields();
			        	if (cityRef) calcNPAllDeliveries(); // show delivery costs
			        	checkoutPage.setAsteriskColor();
			        } else if ('UA' != mrkvnpCountry.val() // not UA and to warehouse or postomat
			        	&& (mrkvShippingMethod.value.indexOf('nova_poshta') >= 0
			        	|| mrkvShippingMethod.value.indexOf('npttn_address') >= 0)) {
			        	const checkoutPage = new CheckoutPage();
			        	checkoutPage.offNPFields();
			        	checkoutPage.offWCFields();
			        } else if ('UA' == mrkvnpCountry.val() // UA and to warehouse or postomat
			        	&& ((mrkvShippingMethod.value.indexOf('nova_poshta') >= 0)
			        	|| (mrkvShippingMethod.value.indexOf('npttn_address') >= 0))) {
			        	const checkoutPage = new CheckoutPage();
			        	checkoutPage.onNPwhFields();
			        } else { // all other cases
			        	const checkoutPage = new CheckoutPage();
			        	checkoutPage.offNPFields();
			        }
		    	}

		    }
		});

		var mrkvnpChecPageCtrl = new CheckoutPage();
		var mrkvnpFL = mrkvnpChecPageCtrl.fieldsLocation;
		var mrkvnpMethod = mrkvnpChecPageCtrl.shippingMethod;
		var mrkvnpAreaSelect = jQuery('#'+mrkvnpFL+'_nova_poshta_region');
		if (mrkvnpAreaSelect.length == 0) return;
		var mrkvnpCitySelect = jQuery('#'+mrkvnpFL+'_nova_poshta_city');
		var mrkvnpWhSelect = jQuery('#'+mrkvnpFL+'_nova_poshta_warehouse');

		// Add/Remove hidden fields when 'ship-to-different-address-checkbox' is clicked (change)
		let shipToDifferentAddressCheckbox = jQuery('#ship-to-different-address-checkbox');
        if (!shipToDifferentAddressCheckbox.length) // If WC-setting billing_only is on
			showFieldsSelect2(mrkvnpAreaSelect, mrkvnpCitySelect, mrkvnpWhSelect, mrkvnpFL);
		shipToDifferentAddressCheckbox.on('change', function(event) {
			jQuery( "input[name=npregionref]" ).remove();
			jQuery( "input[name=npregionname]" ).remove();
			mrkvnpFL = mrkvnpChecPageCtrl.getLocation() ? 'shipping' : 'billing';
			let regRef = jQuery('#'+mrkvnpFL+'_nova_poshta_region').find(':selected').val();
	        let regName = jQuery('#'+'select2-'+mrkvnpFL+'_nova_poshta_region-container').attr('title');
			mrkvnpChecPageCtrl.addHiddenFields(regRef, regName, mrkvnpFL);
			mrkvnpMethod = mrkvnpChecPageCtrl.shippingMethod;
			mrkvnpAreaSelect = jQuery('#'+mrkvnpFL+'_nova_poshta_region');
			if (mrkvnpAreaSelect.length == 0) return;
			mrkvnpCitySelect = jQuery('#'+mrkvnpFL+'_nova_poshta_city');
			mrkvnpWhSelect = jQuery('#'+mrkvnpFL+'_nova_poshta_warehouse');
			showFieldsSelect2(mrkvnpAreaSelect, mrkvnpCitySelect, mrkvnpWhSelect, mrkvnpFL);
		});

	// Add hidden field 'npcityref' and autocomplete `Вулиця' field on CHekout page
	mrkvnpCitySelect.on('change', function(event) {
		var mrkvnpShippingMethod = jQuery('input[name^=shipping_method][type=radio]:checked').val();
		if ( 'npttn_address_shipping_method' != mrkvnpShippingMethod) return;
		const senderAPIkey = NovaPoshtaHelper.mrkvnpSenderAPIkey;
		let cityRef = this.value;
		let elcityref = '<input type="hidden" name="npcityref" location="'+mrkvnpFL+'" value="' + this.value +'"></input>';
		if ( jQuery( "input[name=npcityref]" ).length == 0 ) {
			jQuery('form[name=checkout]').append(elcityref);
		} else {
            jQuery( "input[name=npcityref]" ).remove();
            jQuery('form[name=checkout]').append(elcityref);
        }
        streetFieldAutocomplete(senderAPIkey, cityRef);
	});

	function showFieldsSelect2(mrkvnpAreaSelect, mrkvnpCitySelect, mrkvnpWhSelect, mrkvnpFL) {
		// Get Cities from DB by Region and set them in 'Місто' Select2 field options
		mrkvnpAreaSelect.on('change', function() {
		let regName = jQuery('#'+mrkvnpFL+'_nova_poshta_region option:selected').text();
		let regRef = jQuery('#'+mrkvnpFL+'_nova_poshta_region').find(':selected').val();
		mrkvnpChecPageCtrl.addHiddenFields(regRef, regName, mrkvnpFL);
		    var areaRef = this.value;
		    jQuery.ajax({
				url: NovaPoshtaHelper.ajaxUrl,
				method: "POST",
				data: {
					'action': NovaPoshtaHelper.getCitiesAction,
					'parent_ref': areaRef
				},
				beforeSend: function() {
					jQuery(mrkvnpAreaSelect).closest('#'+mrkvnpFL+'_nova_poshta_region_field').addClass('statenp-loading');
				},
				success: function(json) {
					try {
						let data = JSON.parse(json);
						mrkvnpCitySelect
							.find('option:not(:first-child)')
							.remove();

						jQuery.each(data, function(key, value) {
							mrkvnpCitySelect
								.append(jQuery("<option></option>")
									.attr("value", key)
									.text(value)
								);
						});
						mrkvnpWhSelect.find('option:not(:first-child)').remove();
						jQuery(mrkvnpAreaSelect).closest('#'+mrkvnpFL+'_nova_poshta_region_field').removeClass('statenp-loading');
					} catch (s) {
					    // console.log("Error. Response from server was: " + json);
					}
				},
				error: function() {
				    // console.log('Error.');
				}
		    }); // jQuery.ajax({
		});

		// Get Warehouses from DB by City and set them in 'Відділення' або 'Поштомати' Select2 field options
		mrkvnpCitySelect.on('change', function(event) {
			var cityRef = this.value;
			if ( 'string' == typeof(mrkvnpCurrentMethod) &&
				 mrkvnpCurrentMethod.indexOf('poshtomat') > -1 ) {
			    dataObj = {
			        'action': NovaPoshtaHelper.getPoshtomatsAction,
			        'parent_ref': cityRef,
			        'npchosenmethod': 'poshtomat'
			    };
			} else {
				var dataObj = {
				    'action': NovaPoshtaHelper.getWarehousesAction,
				    'parent_ref': cityRef,
				    'npchosenmethod': 'warehouse'
				};
			}
			jQuery.ajax({
				url: NovaPoshtaHelper.ajaxUrl,
				method: "POST",
				data: dataObj,
				beforeSend: function() {
					jQuery('#'+mrkvnpFL+'_nova_poshta_city_field').addClass('statenp-loading');
				},
				success: function(json) {
					try {
						var data = JSON.parse(json);
						if (!jQuery.isEmptyObject(data)) {
							mrkvnpWhSelect
								.find('option:not(:first-child)')
								.remove();
							jQuery.each(data, function(key, value) {
								mrkvnpWhSelect
									.append(jQuery("<option></option>")
										.attr("value", key)
										.text(value)
									);
							});
						} else {

							var current_lang = jQuery('html').attr('lang');

							var not_found_text = 'No results found';

							if(current_lang == 'uk' || current_lang == 'uk-UA')
							{
								not_found_text = 'Нічого не знайдено';
							}
							if(current_lang == 'ru' || current_lang == 'ru-RU')
							{
								not_found_text = 'Результаты не найдены';
							}

							mrkvnpWhSelect
								.append(jQuery("<option></option>")
									.attr("value", 'not_found')
									.text(not_found_text)
							);
						}
						jQuery('#'+mrkvnpFL+'_nova_poshta_city_field').removeClass('statenp-loading');
					} catch (s) {
					    // console.log("Error. Response from server was: " + json);
					}
				},
				error: function() {
				    // console.log('Error. Warehouses not recieved from server.');
				}
			});
		});

		// Set main region city on the first place in Select2 cities list
		jQuery(mrkvnpAreaSelect).on("change", function() {
			jQuery('#'+mrkvnpFL+'_nova_poshta_city').select2({
				sorter: function(data) {
					var first = [ 'Львів', 'Київ', 'Тернопіль', 'Івано-Франківськ', 'Вінниця', 'Дніпро',
						'Хмельницький', 'Рівне', 'Харків', 'Чернівці', 'Луцьк', 'Одеса',
						'Полтава', 'Черкаси', 'Запоріжжя', 'Житомир', 'Кропивницький', 'Ужгород',
						'Миколаїв', 'Суми', 'Херсон', 'Чернігів', 'Донецьк', 'Луганськ', 'Сімферополь'
					];
					var firstRu = [ 'Львов', 'Киев', 'Тернополь', 'Ивано-Франковск', 'Винница', 'Днепр',
						'Хмельницкий', 'Ровно', 'Харьков', 'Черновцы', 'Луцк', 'Одесса',
						'Полтава', 'Черкассы', 'Запорожье', 'Житомир', 'Кропивницкий', 'Ужгород',
						'Николаев', 'Сумы', 'Херсон', 'Чернигов', 'Донецк', 'Луганск', 'Симферополь'
					];
					data.sort(function(a, b) {
						if ( first.includes( a.text ) ) { // Set region sity on the first place in city list - UA
							let indx = first.indexOf( a.text );
							return a.text == first[indx] ? -1 : b.text == first[indx] ? 1 : 0;
						}
						if ( firstRu.includes( a.text ) ) { // Set region sity on the first place in city list - RU
							let indxRu = firstRu.indexOf( a.text );
							return a.text == firstRu[indxRu] ? -1 : b.text == firstRu[indxRu] ? 1 : 0;
						}
						var jQuerysearch = jQuery('.select2-search__field');
						if (0 === jQuerysearch.length || '' === jQuerysearch.val()) {
							return data;
						}
						var textA = a.text.toLowerCase(),
						textB = b.text.toLowerCase(),
						search = jQuerysearch.val().toLowerCase();
						if (textA.indexOf(search) < textB.indexOf(search)) {
							return -1;
						}
						if (textA.indexOf(search) > textB.indexOf(search)) {
							return 1;
						}
						return 0;
					});
					return data;
				}
			});
			jQuery("#shpping_nova_poshta_warehouse").select2("val", "");
		});
	} // function(mrkvnpAreaSelect, mrkvnpCitySelect, mrkvnpWhSelect, mrkvnpFL) {

    // Add Nova Poshta shipping methods calculation values after method name on Checkout page by city name changed
	jQuery('#'+mrkvnpFL+'_nova_poshta_city').on('change', function(e) {
		jQuery('.mrkvnp-hidden').remove();
		calcNPAllDeliveries();
	});

	function calcNPAllDeliveries() {
		jQuery('.mrkvnp-hidden').remove();
		calcShippingDelivery('my_actionfogetnpshippngcost', jQuery('#mrkvnpwh'), 'nova_poshta_shipping_method', 'mrkvnpwh');
		calcShippingDelivery('actionMrkvNpGetPostomatCost', jQuery('#mrkvnppm'), 'nova_poshta_shipping_method_poshtomat', 'mrkvnppm');
		calcShippingDelivery('actionMrkvNpGetAddressCost', jQuery('#mrkvnpadr'), 'npttn_address_shipping_method', 'mrkvnpadr');
	}

	// Show delivery prices for all the plugin`s delivery methods
	function calcShippingDelivery(action, methodCalcEl, methodId, spanId) {
		jQuery('.mrkvnp-hidden').remove();
		const cityRef = (jQuery('#billing_nova_poshta_city').val())
			? jQuery('#billing_nova_poshta_city').val()
			: jQuery('#shipping_nova_poshta_city').val();

		var has_empty = false;

		if((localStorage.getItem("billing_city_np") === null || localStorage.getItem("billing_city_np") === '') && cityRef){
        	localStorage.setItem('billing_city_np', cityRef);
        	has_empty = true;
        }

        if(cityRef && cityRef == localStorage.getItem("billing_city_np") && !has_empty){
        	return;
        }

        if(cityRef && cityRef != localStorage.getItem("billing_city_np")){
        	localStorage.setItem('billing_city_np', cityRef);
        }
            
		var data = {
			action: action,
			c2: cityRef,
			cod: jQuery('#payment_method_cod').attr('checked')
		};
		if (NovaPoshtaHelper.isShowDeliveryPrice) {
			jQuery.post(NovaPoshtaHelper.ajaxUrl, data, function(response) {
				jQuery('#shipcost').remove();
				if (!(response.includes('apinperrors')) && (response != 0)) {
					if (!methodCalcEl.length) {
						jQuery('label[for=shipping_method_0_' + methodId + ']')
							.after('<span id="'+ spanId + '" class="mrkvnp-hidden"><b style="font-weight: 800;"> '+response+' ₴'+'</b></span>');
					}
				} else {}
			});
		}
	}

	// Autocomplete 'Вулиця' field on Chackout page
	function streetFieldAutocomplete(senderAPIkey, npcityref) {
		var addressInputName = jQuery('#billing_mrkvnp_street');
	    addressInputName.autocomplete({
	        minLength: 3,
	        source: function (request, response) {
				jQuery.ajax({
					type: 'POST',
					beforeSend: function(xhr) {
						xhr.setRequestHeader("Content-type", "application/json;charset=UTF-8");
					},
					url: 'https://api.novaposhta.ua/v2.0/json/',
					data:JSON.stringify({
						apiKey: senderAPIkey,
						modelName: "Address",
						calledMethod: "getStreet",
						methodProperties: {
							CityRef: npcityref,
							FindByString: '%'+jQuery('#billing_mrkvnp_street').val()+'%',
							Page: 1,
							Limit: 100
						}
					}),
					success: function (json) {
						var data = json.data;
						response(jQuery.map(data, function (obj, key) {
							searchval = obj.StreetsType + "" +obj.Description;
							return {
								label: obj.StreetsType + " " +obj.Description,
								value: obj.Ref
							}
						}));
					}
				})
			},
			focus: function (event, ui) {
				addressInputName.val(ui.item.label);
				return false;
			},
			select: function (event, ui) {
				addressInputName.val(ui.item.label);
				return false;
			}
		});
	}
	if(!jQuery('#billing_mrkvnp_patronymics_field').hasClass('.mrkv-np-not-required'))
	{
		jQuery('#billing_mrkvnp_patronymics_field label > .optional').html('<span class="asterisk-color">*</span>');
		jQuery('#shipping_mrkvnp_patronymics_field label > .optional').html('<span class="asterisk-color">*</span>');
	}

}); // jQuery(document).ready(function()

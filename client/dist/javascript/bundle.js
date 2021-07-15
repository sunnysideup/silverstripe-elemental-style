(function($) {
		$('[data-extrastyle]').entwine({
			onchange: function() {
				var $extraStyleField = $(this).closest('form').find('[name="ExtraStyle"]').first();
				var $extraStyleOutputField = $(this).closest('form').find('[name="ExtraStyleOutput"]').first();
				if( $extraStyleField.length ){
					var currentValue = $extraStyleField.val();
					var arrStyleObjects = [];
					var stylesObject = new Object();
					$(this).closest('form').find('[data-extrastyle]').each(function(index, element) {
						let selectedValue = $(element).val();
						let indexData = $(element).data('es-index');
						let locationData = $(element).data('es-location');
						let prefixData = $(element).data('es-prefix');
						let suffixData = $(element).data('es-suffix');
						if(selectedValue.length){
							
							var newObject = new Object();
							newObject['Location'] = locationData;
							newObject['Styles'] = {"Selected":selectedValue};
							if(prefixData !== 'undefined' ){
								newObject['Prefix'] = prefixData;
							}
							if(suffixData !== 'undefined' ){
								newObject['Suffix'] = suffixData;
							}
							// update array value or create new
							stylesObject[[indexData]] = newObject;
						}
					});
					let newValue = JSON.stringify(stylesObject);
					$extraStyleField.val(newValue);
					$extraStyleOutputField.val(newValue);
				}
			}
		}); // end data-extrastyle
})(jQuery);


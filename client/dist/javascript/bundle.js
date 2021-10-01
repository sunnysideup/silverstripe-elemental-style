(function($) {
		$('[name="ExtraStyle"]').entwine({
			onmatch: function() {
				$('[data-extrastyle]').first().change();
			}
		}); // end data-extrastyle
		$('[data-extrastyle]').entwine({
			onchange: function() {
				var $extraStyleField = $(this).getextrastylefield();
				var $extraStyleOutputField = $(this).getextrastyleoutputfield();
				if( $extraStyleField.length ){
					var currentValue = $extraStyleField.val();
					var arrStyleObjects = [];
					var stylesObject = new Object();
					$(this).closest('form').find('[data-extrastyle]').each(function(index, element) {
						let selectedValue = $(element).val() || [];
						let indexData = $(element).data('es-index');
						let locationData = $(element).data('es-location');
						let prefixData = $(element).data('es-prefix');
						let suffixData = $(element).data('es-suffix');
						let defaultData = $(element).data('es-default');
						if(selectedValue.length){
							
							var newObject = new Object();
							newObject['Location'] = locationData;
							newObject['Styles'] = {"Selected":selectedValue};
							if(typeof prefixData !== 'undefined' ){
								newObject['Prefix'] = prefixData;
							}
							if(typeof suffixData !== 'undefined' ){
								newObject['Suffix'] = suffixData;
							}
							
							// update array value or create new
							stylesObject[[indexData]] = newObject;
						} else if(typeof defaultData !== 'undefined' ){
							var newObject = new Object();
							newObject['Location'] = locationData;
							newObject['Default'] = defaultData;
							stylesObject[[indexData]] = newObject;
						} 
					});
					let newValue = JSON.stringify(stylesObject);
					$extraStyleField.val(newValue);
					$extraStyleOutputField.val(newValue);
				}
			},
			getextrastylefield: function() {
				return $(this).closest('form').find('[name="ExtraStyle"]').first();
			},
			getextrastyleoutputfield: function() {
				return $extraStyleOutputField = $(this).closest('form').find('[name="ExtraStyleOutput"]').first();
			}
		}); // end data-extrastyle
})(jQuery);


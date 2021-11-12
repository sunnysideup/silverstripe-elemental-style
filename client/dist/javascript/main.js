document.addEventListener("DOMContentLoaded", function(e) { 
	var util = UIkit.util;
	var el_html = document.querySelector('html');
	el_html.classList.add("jes-edit");

	document.addEventListener('beforeshow', function (e) {
		var target = event.target || event.srcElement;
		var id = target.id
		if(id=='jes_form_holder'){
			el_html.classList.add('jes-offcanvas-page');
			UIkit.switcher('.jes-switcher > ul', {'connect':'~div'});
		}
	});
	document.addEventListener('beforehide', function (e) {
		var target = event.target || event.srcElement;
		var id = target.id
		if(id=='jes_form_holder'){
			el_html.classList.remove('jes-offcanvas-page');			
		}
	});
	document.addEventListener('hidden', function (e) {
		var target = event.target || event.srcElement;
		var id = target.id
		if(id=='jes_form_holder'){
			window.dispatchEvent(new Event('resize'));		
		}
	});

	
			
			
	// remove Enter keypress on form
	window.addEventListener('keydown',function(e){
		if(e.keyIdentifier=='U+000A'||e.keyIdentifier=='Enter'||e.keyCode==13){if(e.target.nodeName=='INPUT'&&e.target.type=='text'){e.preventDefault();return false;}}
	},true);

	// create tabs
	document.querySelectorAll('.jes-switcher > ul:first-child').forEach( element => {
		element.classList.add('uk-tab');
		
	});

	// set up all click events
	document.addEventListener('click', function (e) {
		// close button
		if (event.target.matches('.jes-close')){
			e.preventDefault();
			UIkit.offcanvas('#jes_form_holder').hide();
			return;
		}
	}, false);
	
	// slider
	document.querySelectorAll('input.jes-slider').forEach( element => {
		var el_slider_value = document.createElement('span'); //<span class="jes-value"></span>
		var el_slider_unit = document.createElement('span'); //<span class="jes-value"></span>
		el_slider_value.classList.add('jes-value');
		el_slider_unit.classList.add('jes-slide-unit-label');
		el_slider_unit.innerHTML = element.getAttribute('data-unit');
		element.after(el_slider_value);
		el_slider_value.after(el_slider_unit);
		
		element.addEventListener('input', function (e) {
			this.nextElementSibling.innerHTML = this.value;
		});
		let event = new Event('input'); 
	    element.dispatchEvent(event);
	});
	
	initJesEditButton();
	
	// add style change listeners
	document.querySelectorAll('[data-extrastyle]').forEach( element => {
		setNonEmptyClass(element);
		element.addEventListener('change', function (event) {
			setNonEmptyClass(element);
			var el_form = element.closest('form');
			var elementID = element.getAttribute('data-es-id');
			var pageID = el_form.querySelector('[name="PageID"]').value;
			var el_extraStyleField = el_form.querySelector('[name="'+elementID+'_ExtraStyle"]');
			var el_extraStyleOutputField = el_form.querySelector('[name="'+elementID+'_ExtraStyleOutput"]');
			
			if (typeof(el_extraStyleField) != 'undefined' && el_extraStyleField != null){
				var currentValue = el_extraStyleField.value;
				var arrStyleObjects = [];
				var stylesObject = new Object();
				el_form.querySelectorAll('[data-extrastyle][data-es-id="'+elementID+'"]').forEach( element => {
					let selectedValue = element.value;
					var isMulti = element.multiple; // true/false
					if(isMulti==true){
						selectedValue = Array.from(element.selectedOptions).map(el => el.value);	
					}
					let indexData = element.getAttribute('data-es-index');
					let locationData = element.getAttribute('data-es-location');
					let prefixData = element.getAttribute('data-es-prefix');
					let suffixData = element.getAttribute('data-es-suffix');
					let defaultData = element.getAttribute('data-es-default');
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
				el_extraStyleField.value = newValue;
				el_extraStyleOutputField.value = newValue;

				var formData = serializeForm(el_form);
				var formAction = el_form.getAttribute('action');
				var formMethod = el_form.getAttribute('method');
				var encType = el_form.getAttribute('enctype');

				// just pull out the important fields			
				var customFormData = new FormData();
				customFormData.append("SecurityID",el_form.querySelector('[name="SecurityID"]').value);
				customFormData.append("ElementID", elementID);
				customFormData.append("PageID", pageID);
				customFormData.append("ExtraStyle", newValue);
				
				
				
				var xhr = new XMLHttpRequest();		
				xhr.onreadystatechange = function () {
					// Only run if the request is complete
					if (xhr.readyState !== 4) return;
					// Process our return data
					if (xhr.status >= 200 && xhr.status < 300) {
						// This will run when the request is successful
							old_element = document.querySelector('[data-jes-element="'+elementID+'"]');
							old_element.innerHTML = '';
							old_element.insertAdjacentHTML( 'afterend', xhr.response );
							old_element.parentNode.removeChild(old_element);
							initJesEditButton();
					} else {
						console.log(xhr.responseText);
					}
						
				};	
				xhr.open("POST", '/admin/jes/preview/');
				xhr.send(customFormData);

			} // end if			
			
		});
		
	});	 // end listener
});

var setNonEmptyClass = function(element){
	if(element.value!=''){
		element.classList.add('nonempty');
	} else {
		element.classList.remove('nonempty');			
	}
}

// helper function to serialize form data
var serializeForm = function (form) {
	var obj = {};
	var formData = new FormData(form);
	for (var key of formData.keys()) {
		obj[key] = formData.get(key);
	}
	return obj;
};

// add button to each element and create mouseover listener
var initJesEditButton = function(){
	
	document.querySelectorAll('[data-jes-element]').forEach( element => {
		if (element.getElementsByClassName('jes-edit-holder').length == 0) {
			element.insertAdjacentHTML( 'beforeend', '<div class="jes-edit-holder"><button class="jes-edit-element" uk-icon="jes-cog"></button></div>' );
			
			element.addEventListener("mousemove", function( event ) {
				el_edit_holder = event.target.querySelector('.jes-edit-holder');
				if (typeof(el_edit_holder) != 'undefined' && el_edit_holder != null){
					var e = event;
					var rect = e.target.getBoundingClientRect();
					var w = rect.width;
					var h = rect.height;
					var x = e.clientX - rect.left; //x position within the element.
					var y = e.clientY - rect.top;  //y position within the element.
					var classes = 'jes-edit-holder';
					if(w > 600) {
						classes += (x > (w/2)) ? ' right' : ' left';
					}
					if(h > 200) {
						classes += (y > (h/2)) ? ' bottom' : ' top';
					}
					el_edit_holder.className =  classes; 
				}
			}, false);			
		}
	});
	
	document.querySelectorAll('.jes-edit-holder').forEach( element => {

	});
	
	document.querySelectorAll('.jes-edit-element').forEach( element => {
		element.addEventListener("mouseover", function( event ) {
			event.target.closest('[data-jes-element]').classList.add("jes-edit-hover");
		}, false);
		element.addEventListener("mouseleave", function( event ) {
			event.target.closest('[data-jes-element]').classList.remove("jes-edit-hover");
		}, false);
		element.addEventListener("click", function( event ) {	
			event.preventDefault();
			var element = event.target.closest('[data-jes-element]');
			var element_id = element.getAttribute('data-jes-element');

			document.querySelectorAll('[data-jes-tab]').forEach( element => {
				element.style.display = 'none';
			});
			document.querySelector('[data-jes-tab="'+element_id+'"]').style.display = 'block';
			
			document.querySelectorAll('[data-jes-element]').forEach( element => {
				element.classList.remove('jes-active');
			});		
			element.classList.add('jes-active');
			UIkit.offcanvas('#jes_form_holder').show();
		}, false);
	});
}; // end initJesEditButton


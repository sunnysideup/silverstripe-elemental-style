<?php

namespace Jellygnite\ElementalStyle\Extensions;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\Dev\Debug;

use DNADesign\Elemental\Models\BaseElement;

class PageControllerExtension extends Extension {
	
	private static $allowed_actions = [
        'EditStyleForm'
    ];

	public function EditStyleForm()
    {
		if (Permission::check('ADMIN') !== true) {
            return false;
        }
        if ($this->owner->getRequest()->getVar('CMSPreview') === '1') {
	//		return false;	
		}
		// only visible when viewing staged version
        if (strtolower($this->owner->getRequest()->getVar('stage')) !== 'stage') {
			return false;	
		}	
	
		if ($this->owner->hasMethod('supportsElemental') && $this->owner->supportsElemental()) {
			
			$uikit_version = $this->getOwner()->config()->get('uikit_version');
	
			//for now we will only do the main elemental area. assumes the name ElementalArea
			// we could look up all of them by class?
			$elementalArea = $this->owner->ElementalArea();
			if($elementalArea->exists()){
				$elementControllers = $elementalArea->ElementControllers();
				if($elementControllers->Count()){
					
					Requirements::themedCSS('client/uikit-'.$uikit_version.'/css/uikit.css');
					Requirements::themedJavascript('client/uikit-'.$uikit_version.'/js/uikit.min.js');
					Requirements::themedJavascript('client/uikit-'.$uikit_version.'/js/uikit-icons.min.js');
					
      				Requirements::javascript('jellygnite/silverstripe-elemental-style:client/dist/javascript/main.js');
					Requirements::css('jellygnite/silverstripe-elemental-style:client/dist/css/main.css');
					
					
					$fields = FieldList::create();
					$fields->push(HiddenField::create('PageID','PageID',$this->owner->ID));
					
					foreach($elementControllers as $elementController) {
						
						$element = $elementController->getElement();
						$versionedstate = 'published';
						if ($element::has_extension(Versioned::class)) {
							if (!$element->IsPublished()) {
								$versionedstate = 'draft';
							}
							if ($element->IsPublished() && !$element->IsLiveVersion()) {
							  $versionedstate = 'modified';
							}
						}
						
						$ec_fields = $element->getFrontEndFormFields();
	//					Debug::show($elementController->getElement()->getConfigStyles());
						$el_title = $element->Title?:'Untitled '.$element->singular_name();
						$el_tab_id = 'jes'.$element->ID;

						$fields->push(
							Tab::create( $el_tab_id,
								LiteralField::create('ElementTitle','<div class="jes-title">'.$el_title.'</div>'),
							)
							 ->setAttribute('data-jes-tab',$element->ID)
							 ->addExtraClass('jes-fieldgroup')
 							 ->addExtraClass($versionedstate)
						);
						
						$fields->addFieldsToTab( $el_tab_id, $ec_fields);
					}
					$actions = FieldList::create(
						FormAction::create('doSaveStyle', 'Save All')
							->addExtraClass('jes-button'),
						FormAction::create('doPublishStyle', 'Publish All')
							->addExtraClass('jes-button'),
						LiteralField::create('btnCancel', '<button class="jes-close jes-button">Close</button>')
					);	
					$form = Form::create(
						$this->owner, 
						"EditStyleForm", 
						$fields,
						$actions
					);		
					$form->addExtraClass('jes-form');
					return $form;
				}
			}
			
		}
        
    }
	
	// get element controllers for this page
	// filter by optional element id
	protected function get_elements($id = null) {
		if ($this->owner->hasMethod('supportsElemental') && $this->owner->supportsElemental()) {
			$elementalArea = $this->owner->ElementalArea();
			if($elementalArea->exists()){
				if($id){
					$element =  $elementalArea->Elements()->filter('ID',$id)->first();
					return $element;
				} else {
					$elements = $elementalArea->Elements();
					if($elements->Count()){
						return $elements;
					}
				}
			}
		}
		return false;
	}
	
	
	//need to figure out how to check for changes wthout writing every single element 
    public function doSaveStyle($data, $form, $request) {
		if (Permission::check('ADMIN') !== true) {
            return false;
        }
		$this->do_write($data);
		return $this->owner->redirectBack();
    } 
	
    public function doPublishStyle($data, $form, $request) {
		if (Permission::check('ADMIN') !== true) {
            return false;
        }
		$this->do_write($data, 'Live');
		return $this->owner->redirectBack();
    }  
	
	protected function do_write($data, $version = 'Stage') {
		foreach($data as $key => $value){
			if( preg_match('/_ExtraStyle$/', $key)) {
				$elementID = (int) preg_replace('/_ExtraStyle$/', '', $key);
				if($elementID){
					$element = BaseElement::get()->byID($elementID);
					$element->ExtraStyle = $value;
					if($element->isChanged('ExtraStyle')){
						$element->write();
//						$element->writeToStage('Stage');
					}
					if($version == 'Live'){
						$element->publishRecursive();
					}
				}				
			}
		}			
	}

}
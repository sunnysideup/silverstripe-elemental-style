<?php

namespace Jellygnite\ElementalStyle\Extensions;

use Jellygnite\ElementalStyle\Model\StyleObject;
use Jellygnite\SliderField\SliderField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CompositeField;
use DNADesign\Elemental\Controllers\ElementalAreaController;
use DNADesign\Elemental\Forms\EditFormFactory;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

class DynamicStyleExtension extends DataExtension 
{
	
	private $is_duplicate = false;
	
	private static $default_location = 'default';
	
	// add extra style dropdowns to this tab
	private static $default_tab_name = 'Settings';  
	
	// rename the settings tab
	private static $default_tab_title = 'Appearance';  

	// assign a default CSS class for this object
    private static $default_css_class = '';
	
    private static $disable_chosen = false;


    private static $db = [
        'ExtraStyle' => 'Text'  // saves a json object with all style values
    ];

    public function updateCMSFields(FieldList $fields) {
		
		$default_tab_name = $this->getOwner()->config()->get('default_tab_name');
		$default_tab_title = $this->getOwner()->config()->get('default_tab_title');
		$disable_chosen = $this->getOwner()->config()->get('disable_chosen'); 
		
		if(!$tab_field = $fields->fieldByName('Root.' . $default_tab_name)) {
			$tab_field = $fields->findOrMakeTab('Root.' . $default_tab_name, $default_tab_title);
		}
		$tab_field->setTitle($default_tab_title);

        $fields->removeByName('ExtraStyle');
		
		$arr_config_styleobjects = $this->getConfigStyleObjects();
		$arr_extrastyle_styleobjects = $this->getExtraStyleObjects();
		
		// remove any that don't exist in config (incase of updates)
		if(is_array($arr_extrastyle_styleobjects) && is_array($arr_config_styleobjects)){
			$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);
		}
				
		if (is_array($arr_config_styleobjects) && count($arr_config_styleobjects) > 0) {
			foreach($arr_config_styleobjects as $styleobject){
				$index = $styleobject->getIndex();
				$fieldName = self::getStyleFieldName($index);
				$fieldTitle = $styleobject->getTitle();
				$fieldStyles = $styleobject->getStyles();
				$fieldOptions = $styleobject->getOptions();
				$fieldAfter = $styleobject->getAfter();
				if(!empty($fieldStyles) || !empty($fieldOptions)){
					// fix this using objects?
					$fieldValue = (array_key_exists($index, $arr_extrastyle_styleobjects)) ? $arr_extrastyle_styleobjects[$index]->getSelected() : null;
					$styleFormField = null;
					if(!empty($fieldOptions) && $fieldOptions['Type']='slider'){
						$styleFormField = SliderField::create($fieldName, $fieldTitle,$fieldOptions['Min'], $fieldOptions['Max'], $fieldValue);
						// for now jsut use right title even though Description also sets this
						if(array_key_exists('Unit',$fieldOptions) && !empty($fieldOptions['Unit'])){
							$styleFormField->setUnit($fieldOptions['Unit']);
						}
						if(array_key_exists('Step',$fieldOptions) && !empty($fieldOptions['Step'])){
							$styleFormField->setStep($fieldOptions['Step']);
						}
						if($styleobject->getDescription()){
							$styleFormField->setRightTitle($styleobject->getDescription());
						}
					} else {
						
					
						$styleFormField = DropdownField::create($fieldName, $fieldTitle, array_flip($fieldStyles), $fieldValue); 
						$styleFormField->setRightTitle($styleobject->getDescription());
						$styleFormField->setEmptyString($this->getEmptyString($fieldStyles));
						if($disable_chosen){
							$styleFormField->addExtraClass('no-chosen');
						}

					} // end if options
					if(!empty($styleFormField)){
						
						$styleFormField->setAttribute('data-extrastyle','true');
						$styleFormField->setAttribute('data-es-index',$styleobject->getIndex());
						$styleFormField->setAttribute('data-es-location',$styleobject->getLocation());
						$styleFormField->setAttribute('data-es-prefix',$styleobject->getPrefix());
						$styleFormField->setAttribute('data-es-suffix',$styleobject->getSuffix());
						
						$tabName = (!empty($styleobject->getTab())) ?  $styleobject->getTab() : $default_tab_name;
						if(!empty($tabName)) {
							if(!$fields->fieldByName('Root.'.$tabName)) {
								$fields->insertAfter(Tab::create($tabName), 'Settings');
							}			
						}
						if($fieldAfter && $fields->dataFieldByName($fieldAfter)){
							$fields->insertAfter($styleFormField,$fieldAfter);
						} else {
							$fields->addFieldToTab(
								'Root.'. $tabName,
								$styleFormField 
							);
						}
					}
				}
			}
			$fields->addFieldsToTab(
				'Root.' . $default_tab_name,
				[
					HiddenField::create('ExtraStyle','ExtraStyle'),
					TextField::create('ExtraStyleOutput','Extra Style', $this->getOwner()->ExtraStyle)->setReadonly(true)
				]
			);
		}
		

    }



	public function getFrontEndFormFields() {
				
		$default_tab_name = $this->getOwner()->config()->get('default_tab_name');
		$default_tab_title = $this->getOwner()->config()->get('default_tab_title');
		$disable_chosen = $this->getOwner()->config()->get('disable_chosen'); 
		
		
		$fieldNamePrefix = $this->owner->ID . '_';

		$fields = FieldList::create();
		$fields->push(TabSet::create($fieldNamePrefix.'Root')->addExtraClass('jes-switcher'));
		$tab_field = $fields->findOrMakeTab($fieldNamePrefix.'Root.' . $default_tab_name, $default_tab_title);

		$arr_config_styleobjects = $this->getConfigStyleObjects();
		$arr_extrastyle_styleobjects = $this->getExtraStyleObjects();
		
		if(is_array($arr_extrastyle_styleobjects) && is_array($arr_config_styleobjects)){
			$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);
		}		
//		Debug::show($arr_config_styleobjects);
		if (is_array($arr_config_styleobjects) && count($arr_config_styleobjects) > 0) {
			foreach($arr_config_styleobjects as $styleobject){
				$index = $styleobject->getIndex();
				$fieldName = $fieldNamePrefix . self::getStyleFieldName($index);
				$fieldTitle = $styleobject->getTitle();
				$fieldStyles = $styleobject->getStyles();
				$fieldOptions = $styleobject->getOptions();
				$fieldAfter = $styleobject->getAfter();
				if(!empty($fieldStyles) || !empty($fieldOptions)){
					// fix this using objects?
					$fieldValue = (array_key_exists($index, $arr_extrastyle_styleobjects)) ? $arr_extrastyle_styleobjects[$index]->getSelected() : null;
					$styleFormField = null;
					if(!empty($fieldOptions) && $fieldOptions['Type']='slider'){
//						$styleFormField = SliderField::create($fieldName, $fieldTitle,$fieldOptions['Min'], $fieldOptions['Max'], $fieldValue);
						$styleFormField = TextField::create($fieldName, $fieldTitle, ($fieldValue?:$fieldOptions['Min']))
							->setAttribute("type","range")
							->setAttribute("min",$fieldOptions['Min'])
							->setAttribute("max",$fieldOptions['Max'])
							->addExtraClass('jes-slider');
						// for now jsut use right title even though Description also sets this
						if(array_key_exists('Unit',$fieldOptions) && !empty($fieldOptions['Unit'])){
							$styleFormField->setAttribute("data-unit",$fieldOptions['Unit']);
						}
						if(array_key_exists('Step',$fieldOptions) && !empty($fieldOptions['Step'])){
				//			$styleFormField->setStep($fieldOptions['Step']);
							$styleFormField->setAttribute("step",$fieldOptions['Step']);
						}
						if($styleobject->getDescription()){
							$styleFormField->setDescription($styleobject->getDescription());
						}
					} else {
						
					
						$styleFormField = DropdownField::create($fieldName, $fieldTitle, array_flip($fieldStyles), $fieldValue); 
						$styleFormField->setRightTitle($styleobject->getDescription());
						$styleFormField->setEmptyString($this->getEmptyString($fieldStyles));
						if($disable_chosen){
							$styleFormField->addExtraClass('no-chosen');
						}

					} // end if options
					if(!empty($styleFormField)){
						
						$styleFormField->setAttribute('data-es-id',$this->owner->ID);
						$styleFormField->setAttribute('data-extrastyle','true');
						$styleFormField->setAttribute('data-es-index',$styleobject->getIndex());
						$styleFormField->setAttribute('data-es-location',$styleobject->getLocation());
						$styleFormField->setAttribute('data-es-prefix',$styleobject->getPrefix());
						$styleFormField->setAttribute('data-es-suffix',$styleobject->getSuffix());
						$styleFormField->setAttribute('name',null); // prevent field from being submitted.

					
						// using tabbed layout
						$tabName = (!empty($styleobject->getTab())) ?  $styleobject->getTab() : $default_tab_name;
						if(!empty($tabName)) {
							if(!$fields->fieldByName($fieldNamePrefix.'Root.'.$tabName)) {
								$fields->insertAfter(Tab::create($tabName), 'Settings');
							}			
						}
						if($fieldAfter && $fields->dataFieldByName($fieldAfter)){
							$fields->insertAfter($styleFormField,$fieldAfter);
						} else {
							$fields->addFieldToTab(
								$fieldNamePrefix.'Root.'. $tabName,
								$styleFormField 
							);
						}
						
						// no tabs all fields in line
						/*
						$fields->push(
							$styleFormField 
						);
						*/
					}
				}
			}

			$fields->push(
				HiddenField::create($fieldNamePrefix.'ExtraStyle','ExtraStyle', $this->getOwner()->ExtraStyle)
			);
			$fields->push(
				HiddenField::create($fieldNamePrefix.'ExtraStyleOutput','Extra Style', $this->getOwner()->ExtraStyle)->setReadonly(true)
			);
		}	
		
		return $fields;	
	}

	/**
	* search fieldstyles array for empty value and use key as label. 
	*
	* @return array
	*/	
	protected function getEmptyString($fieldStyles)
	{
		$emptystring =  _t(__CLASS__.'.EXTRA_STYLES', 'Please select...');
		foreach($fieldStyles as $key=>$value){
			if(empty($value)) {
				$emptystring = $key;
				break;
			}
		}
				
		return $emptystring;
	}
	
	/**
	* Get all styles saved to ExtraStyle data field
	*
	* @return array
	*/	
	protected function getExtraStyles()
	{
		return json_decode($this->getOwner()->ExtraStyle, true);
	}	
	
	/**
	* Extension point to allow element or object to update styles programatically
	*
	* @return array
	e.g.
	public function updateConfigStyles($config_styles)  {
		
		// this will override the `Background` style
		$extra_styles = [
			'Background' => [
				'Title' => 'Background',
				'Description' => '',
				'Styles' => [
					'Inherit' => '',
					'White'=> 'bg-white',
				]
			],
		];
		if(is_array($config_styles)){
			$config_styles = array_merge($config_styles,$extra_styles);
		}
		return $config_styles;

	}
	*/	
	public function updateConfigStyles($config_styles)  {
		return $config_styles;
	}
	

	/**
	* Get all styles from config
	*
	* @return array
	*/	
	protected function getConfigStyles()
	{
		$config_styles = $this->getOwner()->config()->get('extra_styles');
		$config_styles = $this->getOwner()->updateConfigStyles($config_styles);
		

		return $config_styles;
	}
	
	/**
	* Get all styles from config as array of StyleObject::class
	*
	* @return array
	*/	
	protected function getConfigStyleObjects()
	{
		$config_styles = $this->getConfigStyles();
		return self::array_to_styleobjects($config_styles);
		 

	}
	/**
	* Get all styles from config as array of StyleObject::class
	*
	* @return array
	*/	
	protected function getExtraStyleObjects()
	{
		$extra_style_value = $this->getExtraStyles(); 
		return self::array_to_styleobjects($extra_style_value);
	}
    /**
     * Take an array of styles and convert them to StyleObject class
     *
     * @return array[$index => StyleObject::class]
     */	
	protected static function array_to_styleobjects($arr_styles){
		$arr_styleobjects = [];
		if (is_array($arr_styles) && count($arr_styles) > 0) {
			foreach($arr_styles as $index => $style){
				if(is_array($style) && !empty($style)){
					$arr_styleobjects[$index] = new StyleObject($index, $style);
				}
			}
		}
		// not working properly yet - affecting the save
//		usort($arr_styleobjects, function($a, $b) {return strcmp($a->getSort(), $b->getSort());});
		return $arr_styleobjects;
	}
	
	
    /**
     * Get unique title for a style dropdown
     *
     * @param string $index (index of the style field)
     *
     * @return string
     */		
	protected static function getStyleFieldName($index){
		return str_replace('\\', '_', __CLASS__) . '_' . $index;
			
	}

    /**
     * Check if this element contains a CSS class, can pass a location if needed
     *
     * To do : search array converted from string to prevent false positives
     *
     * @return boolean
     */	
	public function hasCustomCSSClass($cssclass, $location = false){
		//$haystack = $this->getOwner()->ExtraClass. ' ' . $this->getOwner()->Style;
		if($location){
			$haystack = $this->getStyleByLocation($location);
		} else {
			if(method_exists ( $this->getOwner() , 'getStyleVariant' )){
				$haystack = $this->getOwner()->getStyleVariant();
			} else {
				$haystack = $this->getStyleByLocation();
			}
		}
		return ((((strpos($haystack, $cssclass )) ) !== false));
	}
	
    /**
     * Return the last node of a class name
     *
     * @return string
     */		
    public function getBaseClassName()
    {	
		$classname = $this->getOwner()->ClassName;
        $classParts = explode('\\', $classname);
        return array_pop($classParts);
    }
	
    /**
     * Return the default css class if exists
     *
     * @return string
     */		
    public function getDefaultCssClass()
    {
		$default_css_class = $this->getOwner()->config()->get('default_css_class');
 		$default_css_class = strtolower($default_css_class);
        return $default_css_class;
    }



	public function getStyleByID($id = null) 
	{
		
		$extra_css_classes = [];
		$config_styles = $this->getConfigStyles();
		$extra_style_value = $this->getExtraStyles(); 
		
		$arr_config_styleobjects = self::array_to_styleobjects($config_styles);
		$arr_extrastyle_styleobjects = self::array_to_styleobjects($extra_style_value);
		
		// remove any that don't exist in config (incase of updates)
		if(is_array($arr_extrastyle_styleobjects) && is_array($arr_config_styleobjects)){
			$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);
		}
		

		if (is_array($arr_extrastyle_styleobjects) 
			&& count($arr_extrastyle_styleobjects) > 0 
			&& array_key_exists($id,$arr_extrastyle_styleobjects) 
			) {
			return $arr_extrastyle_styleobjects[$id]->getFormattedSelected();
		}
		return null;
		
	}
	
    /**
     * Get a user defined style variant for this element, if available
     *
     * @return string
     */
	private function getStyleByLocation($location = null) 
	{
		
		$extra_css_classes = [];
		$config_styles = $this->getConfigStyles();
		$extra_style_value = $this->getExtraStyles(); 
		
		$arr_config_styleobjects = self::array_to_styleobjects($config_styles);
		$arr_extrastyle_styleobjects = self::array_to_styleobjects($extra_style_value);
		
		// remove any that don't exist in config (incase of updates)
		if(is_array($arr_extrastyle_styleobjects) && is_array($arr_config_styleobjects)){
		$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);
		}
		

		if (is_array($arr_extrastyle_styleobjects) && count($arr_extrastyle_styleobjects) > 0) {
			foreach($arr_extrastyle_styleobjects as $styleobject){
				if(
					( $styleobject->getLocation() == $location ) || 
					( empty($location) && empty($styleobject->getLocation()) ) 
				){
					$extra_css_classes[] = $styleobject->getFormattedSelected();
				}
			}
			return implode(' ', $extra_css_classes);
		}
		return null;
		
	}
	
	/**
	* Get a user defined style variant for this element, if available
	*
	* @return string
	*/
    public function StyleByLocation($location = null)
    {
		return $this->getStyleByLocation($location);
    }
	
    /**
     * Get all the CSS classes from everywhere and update the StyleVariant. Do not include styles that use Location field as these are for elsewhere in the template
     *
     * @return string
     */	
    public function updateStyleVariant(&$style)
	{
		$extra_css_classes = [];	
		
		// add existing styles
		$extra_css_classes[] = $style;
		
		// add base class name
		$extra_css_classes[] = strtolower($this->getOwner()->getBaseClassName());
		// add default css class 
		$extra_css_classes[] = strtolower($this->getOwner()->getDefaultCssClass());
		// add extra css class to end of list
		$extra_css_classes[] = strtolower($this->getOwner()->ExtraClass);
		// add extra styles with null location
		$extra_css_classes[] = $this->getStyleByLocation();

		$style = implode(' ', $extra_css_classes);
		
		// remove duplicates
		$style = implode(' ', array_unique(explode(' ', $style)));
		
			
    }

	public function onBeforeDuplicate() {
		$this->is_duplicate = true;
	}
}
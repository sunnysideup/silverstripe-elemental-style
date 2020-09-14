<?php

namespace Jellygnite\ElementalStyle\Extensions;

use Jellygnite\ElementalStyle\Model\StyleObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use DNADesign\Elemental\Controllers\ElementalAreaController;
use DNADesign\Elemental\Forms\EditFormFactory;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\Debug;

class DynamicStyleExtension extends DataExtension 
{

	private static $default_location = 'default';
	
	// add extra style dropdowns to this tab
	private static $default_tab_name = 'Settings';  
	
	// rename the settings tab
	private static $default_tab_title = 'Appearance';  

	// assign a default CSS class for this object
    private static $default_css_class = '';


    private static $db = [
        'ExtraStyle' => 'Text'  // saves a json object with all style values
    ];

    public function updateCMSFields(FieldList $fields) {
		
		$default_tab_name = self::$default_tab_name;
		$default_tab_title = self::$default_tab_title;
		
		if(!$tab_field = $fields->fieldByName('Root.' . $default_tab_name)) {
			$tab_field = $fields->insertAfter(Tab::create($default_tab_name, $default_tab_title), 'Settings');
		}
		$tab_field->setTitle($default_tab_title);

        $fields->removeByName('ExtraStyle');
		
		$arr_config_styleobjects = $this->getConfigStyleObjects();
		$arr_extrastyle_styleobjects = $this->getExtraStyleObjects();
		
		// remove any that don't exist in config (incase of updates)
		$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);
				
		if (is_array($arr_config_styleobjects) && count($arr_config_styleobjects) > 0) {
			foreach($arr_config_styleobjects as $styleobject){
				$index = $styleobject->getIndex();
				$fieldName = self::getStyleFieldName($index);
				$fieldTitle = $styleobject->getTitle();
				$fieldOptions = $styleobject->getStyles();
				
				if(!empty($fieldOptions)){
					// fix this using objects?
					$fieldValue = (array_key_exists($index, $arr_extrastyle_styleobjects)) ? $arr_extrastyle_styleobjects[$index]->getSelected() : null;
				
					$styleDropdown = DropdownField::create($fieldName, $fieldTitle, array_flip($fieldOptions), $fieldValue); 
					$styleDropdown->setRightTitle($styleobject->getDescription());
					
					$styleDropdown->setEmptyString(_t(__CLASS__.'.EXTRA_STYLES', 'Please select...'));
					
					$tabName = (!empty($styleobject->getTab())) ?  $styleobject->getTab() : $default_tab_name;
					if(!empty($tabName)) {
						if(!$fields->fieldByName('Root.'.$tabName)) {
							$fields->insertAfter(Tab::create($tabName), 'Settings');
						}			
					}
					$fields->addFieldToTab(
						'Root.'. $tabName,
						$styleDropdown 
					);
				}
			}
			$fields->addFieldToTab(
				'Root.' . $default_tab_name,
				ReadonlyField::create('ExtraStyle','ExtraStyle')
			);
		}
		

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
	* Get all styles from config
	*
	* @return array
	*/	
	protected function getConfigStyles()
	{
		return $this->getOwner()->config()->get('extra_styles');
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
     * Check if this element contains a CSS class
     *
     * @return boolean
     */	
	public function hasCustomCSSClass($cssclass){
		//$haystack = $this->owner->ExtraClass. ' ' . $this->owner->Style;
		$haystack = $this->owner->getStyleVariant();
		return ((((strpos($haystack, $cssclass )) ) !== false));
	}
	
    /**
     * Return the last node of a class name
     *
     * @return string
     */		
    public function getBaseClassName()
    {	
		$classname = $this->owner->ClassName;
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
		$default_css_class = $this->owner->config()->get('default_css_class');
 		$default_css_class = strtolower($default_css_class);
        return $default_css_class;
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
		$arr_extrastyle_styleobjects = array_intersect_key($arr_extrastyle_styleobjects,$arr_config_styleobjects);

		if (is_array($arr_extrastyle_styleobjects) && count($arr_extrastyle_styleobjects) > 0) {
			foreach($arr_extrastyle_styleobjects as $styleobject){
				if(
					( $styleobject->getLocation() == $location ) || 
					( empty($location) && empty($styleobject->getLocation()) ) 
				){
					$extra_css_classes[] = $styleobject->getSelected();
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
		$extra_css_classes[] = strtolower($this->owner->getBaseClassName());
		// add default css class 
		$extra_css_classes[] = strtolower($this->owner->getDefaultCssClass());
		// add extra css class to end of list
		$extra_css_classes[] = strtolower($this->owner->ExtraClass);
		// add extra styles with null location
		$extra_css_classes[] = $this->getStyleByLocation();

		$style = implode(' ', $extra_css_classes);
		
		// remove duplicates
		$style = implode(' ', array_unique(explode(' ', $style)));
		
			
    }
  	
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
		
		$bool_process = false;
		$request = Controller::curr()->getRequest();
		$postVars = $request->postVars();

		$arr_config_styleobjects = $this->getConfigStyleObjects();
		
		if (is_array($arr_config_styleobjects) && count($arr_config_styleobjects) > 0) {
			$extra_style_values = json_decode($this->owner->ExtraStyle, true);
			$bool_process = true;
		} else {
			$extra_style_values = [];
		}

		if ($bool_process) {
			$new_extra_style_values = [];
			foreach($arr_config_styleobjects as $styleobject){
				$index = $styleobject->getIndex();
				$defaultFieldName = self::getStyleFieldName($index);
				$namespacedFieldName = sprintf(EditFormFactory::FIELD_NAMESPACE_TEMPLATE, $this->owner->ID, $defaultFieldName);
				$post_value = null;
				if(array_key_exists($namespacedFieldName, $postVars)){
					$post_value =  $postVars[$namespacedFieldName];
				} elseif(array_key_exists($defaultFieldName, $postVars)) {
					$post_value =  $postVars[$defaultFieldName];
				}
				if(!empty($post_value)){
					$new_object = [
							'Location' => $styleobject->getLocation(),
							'Styles' => [
								'Selected' =>  $post_value,
							]
					];
					$new_extra_style_values[$index] = $new_object;
				}				
			}
			if(!empty($new_extra_style_values)){
				$extra_style_values = $new_extra_style_values;
			}
		} 
		
		$this->owner->ExtraStyle = json_encode($extra_style_values);
		
    }

}
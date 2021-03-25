<?php

namespace Jellygnite\ElementalStyle\Extensions;

use Jellygnite\ElementalStyle\Model\StyleObject;
use Jellygnite\SliderField\SliderField;
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


    private static $db = [
        'ExtraStyle' => 'Text'  // saves a json object with all style values
    ];

    public function updateCMSFields(FieldList $fields) {
		
		$default_tab_name = $this->getOwner()->config()->get('default_tab_name');
		$default_tab_title = $this->getOwner()->config()->get('default_tab_title'); 
		
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
				if(!empty($fieldStyles)){
					// fix this using objects?
					$fieldValue = (array_key_exists($index, $arr_extrastyle_styleobjects)) ? $arr_extrastyle_styleobjects[$index]->getSelected() : null;
					
					if(!empty($fieldOptions) && $fieldOptions['Type']='slider'){
						$styleFormField = SliderField::create($fieldName, $fieldTitle,$fieldOptions['Min'], $fieldOptions['Max'], $fieldValue);
						// for now jsut use right title even though Description also sets this
						if(array_key_exists('Unit',$fieldOptions) && !empty($fieldOptions['Unit'])){
							$styleFormField->setRightTitle($fieldOptions['Unit']);
						}
						if(array_key_exists('Step',$fieldOptions) && !empty($fieldOptions['Step'])){
							$styleFormField->setStep($fieldOptions['Step']);
						}
						if($styleobject->getDescription()){
							$styleFormField->setDescription($styleobject->getDescription());
						}
					} else {
						
					
						$styleFormField = DropdownField::create($fieldName, $fieldTitle, array_flip($fieldStyles), $fieldValue); 
						$styleFormField->setRightTitle($styleobject->getDescription());
						
						$styleFormField->setEmptyString($this->getEmptyString($fieldStyles));

					} // end if options
					
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
			$fields->addFieldToTab(
				'Root.' . $default_tab_name,
				TextField::create('ExtraStyle','ExtraStyle')->setReadonly(true)
			);
		}
		

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
		//$haystack = $this->owner->ExtraClass. ' ' . $this->owner->Style;
		if($location){
			$haystack = $this->getStyleByLocation($location);
		} else {
			if(method_exists ( $this->owner , 'getStyleVariant' )){
				$haystack = $this->owner->getStyleVariant();
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
			return $arr_extrastyle_styleobjects[$id]->getSelected();
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
		
		if($this->is_duplicate){
			return;
		}
		
		$bool_process = false;
		$request = Controller::curr()->getRequest();
		$postVars = $request->postVars();
		//		Injector::inst()->get(LoggerInterface::class)->warning($this->owner->ID." | ". $this->owner->Title  . " | postVars: ". print_r($postVars,true));
		
		$arr_config_styleobjects = $this->getConfigStyleObjects();
		
		if (is_array($arr_config_styleobjects) && count($arr_config_styleobjects) > 0) {
			$extra_style_values = json_decode($this->owner->ExtraStyle, true);
			$bool_process = true;
		} else {
			$extra_style_values = [];
		}
		
		if(is_array($extra_style_values) && is_array($arr_config_styleobjects)){
			$extra_style_values = array_intersect_key($extra_style_values,$arr_config_styleobjects);
		}

		if ($bool_process) {
			// start off with existing extra_style_values
			$new_extra_style_values = $extra_style_values;
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
					// update array value or create new
					$new_extra_style_values[$index] = $new_object;
				}				
			}
			
			$extra_style_values = $new_extra_style_values;
		} 
		
		$this->owner->ExtraStyle = json_encode($extra_style_values);
		
    }

	public function onBeforeDuplicate() {
		$this->is_duplicate = true;
	}
}
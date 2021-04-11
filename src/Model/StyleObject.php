<?php
namespace Jellygnite\ElementalStyle\Model;

use SilverStripe\Forms\FormField;
use SilverStripe\Dev\Debug;
/**
 * Class StyleObject.
 *
 * @property string $Index
 * @property string $Title
 * @property string $Description
 * @property string $Tab
 * @property string $Location
 * @property array $Styles
 * @property integer $Sort		// optional to move items to top or bottom of list
 *
 * @method string getIndex()
 * @method string getTitle()
 * @method string getDescription()
 * @method string getTab()
 * @method string getLocation()
 * @method array getStyles()
 *
 */
 
class StyleObject implements \JsonSerializable {
	
	
	protected $index;
	protected $title;
	protected $description;
	protected $tab;
	protected $location;
	protected $after;	// insert field after
	protected $styles;
	protected $options; // use this to create other form fields
	protected $prefix;
	protected $suffix;
	
	
	private static $arr_default = [
		'Title' => null,
		'Description' => null,
		'Tab' => null,
		'Location' => null,
		'After' => null,
		'Styles' => [],		
		'Sort' => 100,
		'Options' => [],	
		'Prefix' => null,
		'Suffix' => null,	
	];

    public function __construct(String $index, Array $arr_object = [])
    {
		
		$arr_style = array_merge(self::$arr_default, $arr_object);
		
		
        $this->index = $index;
        $this->title = $arr_style['Title']; //(!empty($arr_style['Title'])) ? $arr_style['Title'] : FormField::name_to_label($this->index);
        $this->description = $arr_style['Description'];
        $this->tab = $arr_style['Tab'];
        $this->location = $arr_style['Location'];
        $this->after = $arr_style['After'];
        $this->styles = $arr_style['Styles'];
        $this->sort = $arr_style['Sort'];
        $this->options = $arr_style['Options'];
        $this->prefix = $arr_style['Prefix'];
        $this->suffix = $arr_style['Suffix'];
    }

    public function jsonSerialize()
    {
        return array(
             'Index' => $this->getIndex(),
             'Title' => $this->getTitle(),
             'Description' => $this->getDescription(),
             'Tab' => $this->getTab(),
             'Location' => $this->getLocation(),
             'After' => $this->getAfter(),
             'Styles' => $this->getStyles(),
        );
    }

	public function getIndex(){
		return $this->index;
	}
	public function getTitle(){
		return (!empty($this->title)) ?  $this->title : FormField::name_to_label($this->index);
	}
	public function getDescription(){
		return $this->description;
	}
	public function getTab(){
		return $this->tab;
	}
	public function getLocation(){
		return $this->location;
	}
	public function getAfter(){
		return $this->after;
	}
	public function getStyles(){
		return $this->styles;
	}
	public function getOptions(){
		return $this->options;
	}
	public function getSort(){
		return $this->sort;
	}
	public function getPrefix(){
		return $this->prefix;
	}
	public function getSuffix(){
		return $this->suffix;
	}
	
	// return selected value
	public function getSelected(){
		$styles = $this->getStyles();
		if(is_array($styles) && array_key_exists('Selected', $styles)){
			return $styles['Selected'];
		}
		return null;
	}

	// return selected value nicely formatted
	public function getFormattedSelected(){
		$selected = $this->getSelected();
		if(!empty($selected) || $selected==='0'){
			return $this->getPrefix() . $selected . $this->getSuffix();			
		}
		return null;
	}

}

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
	protected $styles;
	
	
	private static $arr_default = [
		'Title' => null,
		'Description' => null,
		'Tab' => null,
		'Location' => null,
		'Styles' => [],		
	];

    public function __construct(String $index, Array $arr_object = [])
    {
		
		$arr_style = array_merge(self::$arr_default, $arr_object);
		
		
        $this->index = $index;
        $this->title = $arr_style['Title']; //(!empty($arr_style['Title'])) ? $arr_style['Title'] : FormField::name_to_label($this->index);
        $this->description = $arr_style['Description'];
        $this->tab = $arr_style['Tab'];
        $this->location = $arr_style['Location'];
        $this->styles = $arr_style['Styles'];
    }

    public function jsonSerialize()
    {
        return array(
             'Index' => $this->getIndex(),
             'Title' => $this->getTitle(),
             'Description' => $this->getDescription(),
             'Tab' => $this->getTab(),
             'Location' => $this->getLocation(),
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
	public function getStyles(){
		return $this->styles;
	}
	
	
	public function getSelected(){
		$styles = $this->getStyles();
		if(is_array($styles) && array_key_exists('Selected', $styles)){
			return $styles['Selected'];
		}
		return null;
	}

}

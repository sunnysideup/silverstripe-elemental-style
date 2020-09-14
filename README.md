# Jellygnite Elemental Dynamic Styles

## Introduction

Add as many style dropdown fields as you like to any element via a YML file. 

Styles are grouped by location. This allows you to insert CSS classes into various positions in your template other than just the holding container. 

A good example of where you might want extra styles within your template is when you need to offer the ability to change the number of columns within a grid.


## Requirements

* SilverStripe ^4.0
* dnadesign/silverstripe-elemental ^4.0

## Installation

```
composer require jellygnite/silverstripe-elemental-style
```


## Example configurations

### Method 1

You can add extra styles to your individual Elements, e.g.

```
    private static $extra_styles = [
		'Background' => [
			'Title' => 'Background',
			'Description' => '',
			'Styles' => [
				'Inherit' => '',
				'Default' => 'bg-default',
			]
		],
		'PaddingTop' => [
			'Title' => 'Padding Top',
			'Description' => 'Adjust the padding on top side',
			'Styles' => [
				'Default' => '',
				'None' => 'pt-0',
				'Small' => 'pt-4',
				'Medium' => 'pt-6',
				'Large' => 'pt-8',
			]
		],
		'PaddingBottom' => [
			'Title' => 'Padding Bottom',
			'Description' => 'Adjust the padding on bottom side',
			'Styles' => [
				'Default' => '',
				'None' => 'pb-0',
				'Small' => 'pb-4',
				'Medium' => 'pb-6',
				'Large' => 'pb-8',
			]
		],
		'PaddingVertical' => [
			'Title' => 'Padding Vertical',
			'Description' => 'Adjust the padding on top and bottom sides',
			'Styles' => [
				'Default' => '',
				'None' => 'py-0',
				'Small' => 'py-4',
				'Medium' => 'py-6',
				'Large' => 'py-8',
			]
		],
		'MarginTop' => [
			'Title' => 'Margin Top',
			'Description' => 'Adjust the margins on all sides',
			'Styles' => [
				'Default' => '',
				'None' => 'm-0',
				'Small' => 'm-4',
				'Medium' => 'm-6',
				'Large' => 'm-8',
			]
		]
	];
```


### Method 2

Or add using yml file, e.g. to make the styles available to Elements that extend the BaseElement:

```yaml

DNADesign\Elemental\Models\BaseElement:
  extra_styles:
    # Then define your styles
    MarginTop:
      'Title': 'Margin Top'
      'Description': 'Adjust the margin on the top'
      'Styles':
        'None': 'mt-0'
        'Small': 'mt-4'
    GridMobile:
      'Title': 'Grid Mobile'
      'Description': 'Set the number of columns for a grid'
      'Location': 'grid'
      'Tab': 'Layout'
      'Styles':
        'Full': 'uk-child-width-1-1'
        'Two column': 'uk-child-width-1-2'
        'Three column': 'uk-child-width-1-3'

```


### Method 3

Alternative method using an Extension on your Elements, create a file called CustomBaseElementExtension.php.

```
<?php

use SilverStripe\ORM\DataExtension;

class CustomBaseElementExtension extends DataExtension 
{

	private static $extra_styles = [
		'Background' => [
			'Title' => 'Background',
			'Description' => '',
			'Styles' => [
				'Inherit' => '',
				'Default'=> 'bg-default',
				'Blue'=> 'bg-blue',
				'Grey'=> 'bg-grey',
			]
		],
		'PaddingTop' => [
			'Title' => 'Padding Top',
			'Description' => 'Adjust the padding on top side',
			'Styles' => [
				'Default' => '',
				'None' => 'pt-0',
				'Small' => 'pt-4',
				'Medium' => 'pt-6',
				'Large' => 'pt-8',
				'X-Large' => 'pt-9',
			]
		],
		'PaddingBottom' => [
			'Title' => 'Padding Bottom',
			'Description' => 'Adjust the padding on bottom side',
			'Styles' => [
				'Default' => '',
				'None' => 'pb-0',
				'Small' => 'pb-4',
				'Medium' => 'pb-6',
				'Large' => 'pb-8',
				'X-Large' => 'pb-9',
			]
		],
		'PaddingVertical' => [
			'Title' => 'Padding Vertical',
			'Description' => 'Adjust the padding on top and bottom sides',
			'Styles' => [
				'Default' => '',
				'None' => 'py-0',
				'Small' => 'py-4',
				'Medium' => 'py-6',
				'Large' => 'py-8',
				'X-Large' => 'py-9',
			]
		],
		'MarginTop' => [
			'Title' => 'Margin Top',
			'Description' => 'Adjust the margin on top side',
			'Styles' => [
				'Default' => '',
				'None' => 'mt-0',
				'Small' => 'mt-4',
				'Medium' => 'mt-6',
				'Large' => 'mt-8',
				'X-Large' => 'mt-9',
			]
		],
		'MarginBottom' => [
			'Title' => 'Margin Bottom',
			'Description' => 'Adjust the margin on bottom side',
			'Styles' => [
				'Default' => '',
				'None' => 'mb-0',
				'Small' => 'mb-4',
				'Medium' => 'mb-6',
				'Large' => 'mb-8',
				'X-Large' => 'mb-9',
			]
		],
	];
}
```

And then in your .yml file:

```
DNADesign\Elemental\Models\BaseElement:
  extensions:
    - CustomBaseElementExtension
```

## Notes

The minimum requirements for a style is as follows. Title will use the style's index, (in this case 'MarginTop').

```
DNADesign\Elemental\Models\BaseElement:
  extra_styles:
    MarginTop:
      'Styles':
        'None': 'mt-0'
        'Small': 'mt-4'
```

You can disable an inherited style on a per element basis by setting the index to null, e.g.

```
Namespace\YourElement:
  extra_styles:
    MarginTop: null
```
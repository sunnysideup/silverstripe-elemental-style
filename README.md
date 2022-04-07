# Jellygnite Elemental Dynamic Styles

## Introduction

Add as many style dropdown fields as you like to any element via a YML file. 

Styles are grouped by location. This allows you to insert CSS classes into various positions in your template other than just the holding container. 

A good example of where you might want extra styles within your template is when you need to offer the ability to change the number of columns within a grid.

**UPDATES**

- v4.4.2 : Minor updates. Ability to group styles. Responsive helper function to create multiple versions of a single style for different viewports.
- v4.4.0 : Extra fields are now available. Multiselect, Optionset and ImageOptionset
- v4.3.0 : Includes front end editing for styles. With almost real time results you can see exactly what your elements will look like with various styles applied. You can also use the back end preview area to access this feature.
 
**TO DO**
- move front end editor into iframe
- improve responsive helper UI


## Requirements

* SilverStripe ^4.0
* dnadesign/silverstripe-elemental ^4.0

## Installation

```
composer require jellygnite/silverstripe-elemental-style
```


## Example configurations

Include the following information in your config:

- 'Index'       : Unique identifier for the style field
- 'Title'       : Label for the style field
- 'Description' : Optional brief description
- 'Location'    : Use this style in a different location in the template
- 'Tab'         : Name of the Tab to add the style field to
- 'After'       : or, the name of the existing field to add the style field after
- 'Styles'      : Array of styles to appear in the style field ['title' => 'css classes']
- 'Prefix'      : Prefix for the outputted style
- 'Suffix'      : Suffix for the outputted style
- 'Options'     : Array of options to create different form fields. Slider and Multiselect (listbox) are the available field types.
- 'Default'     : Allows a default value when nothing is selected in the dropdown field. Note this does not have to exist as an option in the allowed Styles, i.e. it can be whatever you want.
- 'Group'       : Group multiple styles on one line. Use CamelCase for the Group name as this will also be used as the Group title.
- 'Responsive'  : This is a helper function to quickly create multiple styles for responsive viewports.


### Method 1

You can add extra styles to your individual Elements, e.g.

```
    private static $extra_styles = [
		'ElementBackgroundColor' => [
			'Title' => 'Element Background Color',
			'Description' => 'Set the background color for the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Prefix' => 'bg-',
			'Styles' => [
				'Inherit' => '',
			]
		],
		'ElementPaddingVertical' => [
			'Title' => 'Element Padding Vertical',
			'Description' => 'Adjust the padding on top and bottom sides of the block element',
			'Location' => 'element.class',
			'Prefix' => 'py-',
			'Styles' => [
				'Default' => '',
				'None' => '0',
				'X-Small' => '2',
				'Small' => '4',
				'Medium' => '6',
				'Large' => '8',
				'X-Large' => '9',
			],
			'Responsive' => [
				'Mobile' => [
				],
				'Tablet' => [
					'Prefix' => 'py-sm-', // You can override any of the original style options
				],
				'Desktop' => [
					'Prefix' => 'py-lg-',
				],
			],
		],
		'ElementPaddingTop' => [
			'Title' => 'Element Padding Top',
			'Description' => 'Adjust the padding on top side of the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Styles' => [
				'Default' => '',
				'None' => 'pt-0',
				'X-Small' => 'pt-2',
				'Small' => 'pt-4',
				'Medium' => 'pt-6',
				'Large' => 'pt-8',
				'X-Large' => 'pt-9',
			]
		],
		'ElementPaddingBottom' => [
			'Title' => 'Element Padding Bottom',
			'Description' => 'Adjust the padding on bottom side of the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Styles' => [
				'Default' => '',
				'None' => 'pb-0',
				'X-Small' => 'pb-2',
				'Small' => 'pb-4',
				'Medium' => 'pb-6',
				'Large' => 'pb-8',
				'X-Large' => 'pb-9',
			]
		],
		'ElementMarginVertical' => [
			'Title' => 'Element Margin Vertical',
			'Description' => 'Adjust the margin on top and bottom sides of the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Styles' => [
				'Default' => '',
				'None' => 'my-0',
				'X-Small' => 'my-2',
				'Small' => 'my-4',
				'Medium' => 'my-6',
				'Large' => 'my-8',
				'X-Large' => 'my-9',
			]
		],
		'ElementMarginTop' => [
			'Title' => 'Element Margin Top',
			'Description' => 'Adjust the margin on top side of the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Styles' => [
				'Default' => '',
				'None' => 'mt-0',
				'X-Small' => 'mt-2',
				'Small' => 'mt-4',
				'Medium' => 'mt-6',
				'Large' => 'mt-8',
				'X-Large' => 'mt-9',
				'Negative Small' => 'mt-n4',
				'Negative Medium' => 'mt-n6',
			]
		],
		'ElementMarginBottom' => [
			'Title' => 'Element Margin Bottom',
			'Description' => 'Adjust the margin on bottom side of the block element',
			'Tab' => 'Container',
			'Location' => 'element.class',
			'Styles' => [
				'Default' => '',
				'None' => 'mb-0',
				'X-Small' => 'mb-2',
				'Small' => 'mb-4',
				'Medium' => 'mb-6',
				'Large' => 'mb-8',
				'X-Large' => 'mb-9',
			]
		],
	];
```

Options e.g.

```
		'SwatchShape' => [
			'Title' => 'Swatch Shape',
			'Description' => 'Set the border radius as a percentage from 0% (square) to 50% (circle).',
			'Tab' => 'Palette',
			'Location' => 'item.swatch.style',
			'Prefix' => 'border-radius:',
			'Suffix' => '%;',
			'Options' => [
				'Type' => 'slider',
				'Min' => '0',
				'Max' => '50',
				'Step' => '5',
				'Unit' => '%',
			],
		],
		'ImageSettings' => [
			'Title' => 'Image Settings',
			'Description' => 'Adjust the look of the image',
			'Location' => 'image.css',
			'Styles' => [
				'Round border' => 'b-circle',
				'Scale on hover' => 'hover-scale',
				'No background colour' => 'bg-none',
			]
			'Options' => [
				'Type' => 'multiselect',
			],
		],
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
      'Group': 'Grid'
      'Styles':
        'Full': 'uk-child-width-1-1'
        'Two column': 'uk-child-width-1-2'
        'Three column': 'uk-child-width-1-3'
    GridTablet:
      'Title': 'Grid Tablet'
      'Description': 'Set the number of columns for a grid'
      'Location': 'grid'
      'Tab': 'Layout'
      'Group': 'Grid'
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


## Using Styles in your templates

There are a few ways to use the styles in your templates.

The original $StyleVariant variable will output all styles that don't use the location option. e.g.

```
<section class="element <% if $StyleVariant %> $StyleVariant<% end_if %> id="$Anchor">
```

The $StyleByLocation method is more powerful and allows you to create multiple styles that can be used throughout your templates.

```
<section class="element $StyleByLocation('element.class')" id="$Anchor">
```

You don't have to limit yourself to using it for class names. 

```
private static $extra_styles = [
	'MenuAnimation' => [
		'Title' => 'Menu Animation',
		'Description' => '',
		'Location' => 'offcanvas.options',
		'Tab' => 'Appearance.Navigation',
		'Prefix' => 'mode:',
		'Suffix' => ';',
		'Styles' => [
			'Slide' => '',
			'Push' => 'push',
			'Reveal' => 'reveal',
			'None' => 'none',
		]
	],
];
```

And in the template: 

```
<div id="offcanvas" data-uk-offcanvas="{$StyleByLocation('offcanvas.options')}" style="display:none">
```


## Front end editing

In order to view the front end editing features you need to ensure the following things:

- View the page in draft mode, i.e. `/page-url?stage=Stage`
- Add this to your Page.ss template at the bottom of the page before the `</body>` closing tag
```
<% include Jellygnite/ElementalStyle/EditStyleForm %>
```
- Add this to your ElementHolder.ss template to the top level HTML tag `data-jes-element="$ID"`, e.g.
```
<section class="element $StyleByLocation('element.class')" id="$Anchor" data-jes-element="$ID">
	$Element
</section>
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
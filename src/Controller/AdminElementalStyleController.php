<?php

namespace Jellygnite\ElementalStyle\Controller;

use \Page;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Dev\Debug;

class AdminElementalStyleController extends ContentController {
	
//	$Action	[ preview ]
//	$ID		$ElementID
//	$Name	$PageID ?? required?
	
	private static $allowed_actions = [
		'preview'
    ];

	// get element controllers for this page
	// filter by optional element id
	protected function get_element($elementID, $pageID) {
		if($pageID){
			$page = Page::get()->byID($pageID);
			if ($page->exists() && $page->hasMethod('supportsElemental') && $page->supportsElemental()) {
				$elementalArea = $page->ElementalArea();
				if($elementalArea->exists()){
					if($elementID){
						$element = $elementalArea->Elements()->filter('ID',$elementID)->first();
						return $element;
					} 
				}
			}
		}
		return false;
	}
	
	// this returns the rendered element with new styles
	// does not modify database
    public function preview(HTTPRequest $request) {
		if (Permission::check('ADMIN') !== true) {
			throw new HTTPResponse_Exception("Unauthorized request", 401);
        }
				
		if($request->isAjax() || true) {
			$elementID =  $request['ElementID'];
			$pageID = $request['PageID'];
			$securityID = $request['SecurityID'];
			
			if(!SecurityToken::inst()->checkRequest($request)) {
				throw new HTTPResponse_Exception("Invalid request", 400);	
			}
			
			$element = $this->get_element($elementID, $pageID);
		
			$element->ExtraStyle = $request['ExtraStyle'];

			return $element->getController()->forTemplate(); 		
		}
		
		
			throw new HTTPResponse_Exception("Invalid request", 404);
	}
	


}
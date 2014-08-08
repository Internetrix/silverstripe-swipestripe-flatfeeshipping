<?php

class ShippingCourier extends DataObject {

	private static $db = array(
		'Title' => 'Varchar',
		'Description' => 'Varchar(255)'
	);
	
	private static $has_one = array(
		'ShopConfig' => 'ShopConfig'
	);
	
	private static $default_records = array(
		array('Title' => "AUS POST"),
		array('Title' => "Fast Way"),
		array('Title' => "DHL"),
		array('Title' => "TNT")
	);

	private static $summary_fields = array(
		'Title' => 'Title',
		'Description' => 'Description'
	);
	
	public function getCMSFields() {
	
		return new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('ShippingRate',
					TextField::create('Title', 'Name'),
					TextField::create('Description', _t('FlatFeeShippingRate.DESCRIPTION', 'Description'))
				)
			)
		);
	}
	
	
	
	
	
	
}
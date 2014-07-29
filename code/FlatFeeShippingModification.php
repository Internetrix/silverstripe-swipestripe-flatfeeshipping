<?php

class FlatFeeShippingModification extends Modification {

	private static $has_one = array(
		'FlatFeeShippingRate' => 'FlatFeeShippingRate'
	);

	private static $defaults = array(
		'SubTotalModifier' => false,
		'SortOrder' => 50
	);

	private static $default_sort = 'SortOrder ASC';
	
	protected $default_rate = false;

	public function add($order, $value = null) {
		
		$postData = Controller::curr()->request->postVars();
		$postData = Convert::raw2sql($postData);
		
		if( ! isset($postData['ShippingCountryCode'])){
			return '';
		}

		$this->OrderID = $order->ID;

		$country = Country_Shipping::get()
				->filter("Code",$order->ShippingCountryCode)
				->first();

		$rates = $this->getFlatShippingRates($country);
		
		if ($rates && $rates->exists()) {
			
			$rate = false;

			if($this->default_rate){
				$orderTotalPrice = $order->TotalPrice();
			
				$TotalPrice = $orderTotalPrice->getAmount();
				
				$rate = $rates->first();
				
				foreach ($rates as $rateDO){
					if($TotalPrice > $rateDO->ThresholdPrice){
						$rate = $rateDO;
					}
				}
			}else{	
				//Pick the rate
				$rate = $rates->find('ID', $value);
	
				if (!$rate || !$rate->exists()) {
					$rate = $rates->first();
				}
			}

			//Generate the Modification now that we have picked the correct rate
			$mod = new FlatFeeShippingModification();

			$mod->Price = $rate->Amount()->getAmount();
			$mod->Description = $rate->Description;
			$mod->OrderID = $order->ID;
			$mod->Value = $rate->ID;
			$mod->FlatFeeShippingRateID = $rate->ID;
			$mod->write();
		}
	}

	public function getFlatShippingRates(Country_Shipping $country) {
		//Get valid rates for this country
		$countryID = ($country && $country->exists()) ? $country->ID : null;
		
		$rates = FlatFeeShippingRate::get()->filter("CountryID", $countryID);
		
		//couldn't find country for this rates
		if( ! ( $rates && $rates->Count())){
			$country = Country_Shipping::get()
				->filter("Code", 'OA')
				->first();
				
			$countryID = $country->ID;
				
			$rates = FlatFeeShippingRate::get()
						->filter("CountryID", $countryID)
						->sort('"ThresholdPrice" ASC');
			
			if($rates && $rates->Count()){
				$this->default_rate = true;
			}
		}
		
		$this->extend("updateFlatShippingRates", $rates, $country);
		
		return $rates;
	}

	public function getFormFields() {
		
		$allowedMultiple = false;

		$fields = new FieldList();
		$rate = $this->FlatFeeShippingRate();
		$rates = $this->getFlatShippingRates($rate->Country());

		if ($rates && $rates->exists()) {

			if ($allowedMultiple && $rates->count() > 1) {
				$field = FlatFeeShippingModifierField_Multiple::create(
					$this,
					_t('FlatFeeShippingModification.FIELD_LABEL', 'Shipping'),
					$rates->map('ID', 'Label')->toArray()
				)->setValue($rate->ID);
			}
			else {
				if( ! $allowedMultiple){
					$newRate = $rate;
				}else{
					$newRate = $rates->first();
				}
				
				$field = FlatFeeShippingModifierField::create(
					$this,
					$newRate->Title,
					$newRate->ID
				)->setModification($this)->setAmount($newRate->Price());
			}

			$fields->push($field);
		}

		if (!$fields->exists()) Requirements::javascript('swipestripe-flatfeeshipping/javascript/FlatFeeShippingModifierField.js');

		return $fields;
	}
}
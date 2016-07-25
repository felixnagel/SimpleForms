<?php

namespace LuckyNail\SimpleForms;

class Form extends BaseForm{
	public function __construct($aSettings = []){
		parent::__construct($aSettings);
	}

	protected function __filter__numeric($sValue){
		return preg_replace('=[^0-9]=', '', $sValue);
	}

	protected function __filter__encrypt($sString, $iStrength = 4){
		return password_hash($sString, PASSWORD_BCRYPT, array('cost' => $iStrength));
	}

	/**
	 * Default form filter: Normalizes the Name of a person.
	 * @param 	string 	$sName	form field value
	 * @return 	string 			filtered form field value
	 */
	protected function __filter__name_of_person($sName){
		$sName = ucwords(strtolower(trim($sName)));
		
		// remove double whitespaces
		$sName = preg_replace('=\s+=', ' ', $sName);
		
		// remove whitespaces around "-"
		$sName = preg_replace('=\s*(\-)\s*=', '$1', $sName);

		// removes whitespaces after "'"
		$sName = preg_replace('=(\')\s*=', '$1', $sName);
		
		// casts the character after "-" and "'" to upperxase
		$sName = preg_replace_callback('=[\-\']([a-z])=', function($aMatches){
			return strtoupper($aMatches[0]);
		}, $sName);

		return $sName;
	}
}


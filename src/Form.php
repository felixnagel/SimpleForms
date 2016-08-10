<?php

namespace LuckyNail\SimpleForms;

class Form extends BaseForm{
	public function __construct($aSettings = []){
		parent::__construct($aSettings);
	}
	protected function __filter__numeric($sValue){
		return preg_replace('=[^0-9]=', '', $sValue);
	}
	protected function __filter__rdws($sVal){
		return preg_replace('=\s+=', ' ', $sVal);
	}
	protected function __filter__name($sVal){
		$sVal = ucwords(strtolower(trim($sVal)));
		$sVal = $this->__filter__rdws($sVal);
		// remove whitespaces around "-"
		$sVal = preg_replace('=\s*(\-)\s*=', '$1', $sVal);
		// removes whitespaces after "'"
		$sVal = preg_replace('=(\')\s*=', '$1', $sVal);
		// casts the character after "-" and "'" to uppercase
		$sVal = preg_replace_callback('=[\-\']([a-z])=', function($aMatches){
			return strtoupper($aMatches[0]);
		}, $sVal);
		return $sVal;
	}
}

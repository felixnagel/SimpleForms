<?php

namespace LuckyNail\SimpleForms;

class Form extends BaseForm{
	public function __construct($aSettings = []){
		parent::__construct($aSettings);
	}

	/**
	 * Create a html label tag. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated html tag string
	 */
	public function label($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'label', $aSettings);
	}

	/**
	 * Create a html label tag without for attribute. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated html tag string
	 */
	public function label_for($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'label_for', $aSettings);
	}

	/**
	 * Create a text input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function text($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'text', $aSettings);
	}

	/**
	 * Create a textarea input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function textarea($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'textarea', $aSettings);
	}

	/**
	 * Create a select input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aOptions 	select options
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function select($sFieldName, $aOptions, $aSettings = []){
		$aSettings['options'] = $aOptions;
		return $this->field($sFieldName, 'select', $aSettings);
	}

	/**
	 * Create a multiselect input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aOptions 	select options
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function multiselect($sFieldName, $aOptions, $aSettings = []){
		$aSettings['multiple'] = true;
		return $this->select($sFieldName, $aOptions, $aSettings);
	}

	/**
	 * Create a checkbox input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function checkbox($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'checkbox', $aSettings);
	}

	/**
	 * Create a file input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function file($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'file', $aSettings);
	}

	/**
	 * Create a hidden input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function hidden($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'hidden', $aSettings);
	}

	/**
	 * Create a radio input field. This is a mapping to ->field().
	 * @param  string 	$sFieldName form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated input field string
	 */
	public function radio($sFieldName, $aSettings = []){
		return $this->field($sFieldName, 'radio', $aSettings);
	}


	protected function __filter__numeric($sValue){
		return preg_replace('=[^0-9]=', '', $sValue);
	}
	protected function __filter__rdws($sVal){
		return preg_replace('=\s\s+=', ' ', $sVal);
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

<?php

namespace LuckyNail\SimpleForms;

use LuckyNail\Simple\ArrayDotSyntax;

abstract class BaseValidator{
	use ArrayDotSyntax;


	/**
	 * Flag stating, if data has been validated yet
	 * @var boolean
	 */
	private $_bIsValidated = false;
	
	/**
	 * Flag stating, if validator stops after the first error
	 * @var boolean
	 */
	private $_bQuick = false;
	
	/**
	 * Set up validators
	 * @var array
	 */
	private $_aVldtrs = [];

	/**
	 * Errors found during validation
	 * @var array
	 */
	private $_aErrors = [];

	/**
	 * Pool of error messages
	 * @var array
	 */
	protected $_aVldtrErrMsg = [];

	/**
	 * Pool of custom validators
	 * @var array
	 */
	private $_aCustomVldtrs = [];

	/**
	 * Data to validate
	 * @var array
	 */
	private $_aData = [];

	private $_sMaskParamPattern = '=^~\{(.+?)\}~$=';
	private $_sMastParamProto = '~{%s}~';

	/**
	 * Sets quick flag.
	 * @param	bool 	$bQuick		flag boolean value
	 */
	public function set_quick($bQuick){
		$this->_bQuick = $bQuick;
	}


	/**
	 * Adds validators. Each field may be added multiple validators. Respective fields are
	 * referenced with array dot syntax.
	 * Example array structure: [
	 * 		// field_name: adds only the 'required' validator
	 * 		'field_name' => ['required'], 
	 * 		// drinks: must be array, each value of this array must be one of
	 * 		// 'juice', 'cola', 'tea', 'coffee'
	 * 		'drinks' => ['array' => ['in' => ['juice', 'cola', 'tea', 'coffee']]],
	 * 		// subkey 'softdrink' must be of value 'cola'
	 *		'drinks.softdrink' => ['eq' => 'cola'],
	 * 		// subkey 'at_work' must not be of value 'cola'
	 *		'drinks.healthy' => ['!eq' => 'cola'],
	 *  	// free_text field must be same value as value of 'drinks.softdrink' field
	 *   	'free_text' => ['same' => 'drinks.softdrink'],
	 * ]
	 * @param	array 	$aSettings 	array of validators
	 */
	public function add_validators($aSettings){
		foreach($aSettings as $sField => $aValidators){
			$this->_aVldtrs[$sField] = $this->_normalize_cnfg_arr($aValidators);
		}
	}

	/**
	 * Gets invalid form field errors.
	 * @return	array 	invalid form field errors
	 */
	public function get_errors(){
		return $this->_aErrors;
	}

	/**
	 * Adds custom error messages. Example array structure: [
	 * 		'required' => 'Fill this field, please.',
	 * 		'drinks.!eq' => 'Dont drink beer, it\'s unhealthy!',
	 * 	]
	 * @param	array 	$aErrorMessages		array of error messages
	 */
	public function add_error_messages($aErrorMessages){
		$this->_aVldtrErrMsg = array_merge($this->_aVldtrErrMsg, $aErrorMessages);
	}

	/**
	 * Validates given data.
	 * @return	array 	array with field validations (boolean values of field keys)
	 */
	private function _validate(){
		$this->_aErrors = [];
		foreach($this->_aVldtrs as $sFieldId => $aVldtrDef){
			$mInputValue = $this->ads_get($this->_aData, $sFieldId);
			$this->_validate_single_field($sFieldId, $mInputValue, $aVldtrDef);
		}
		$this->_bIsValidated = true;
	}

	/**
	 * Check if form is valid.
	 * @return boolean 	form is valid
	 */
	public function is_valid(){
		if(!$this->_bIsValidated){
			$this->_validate();
		}
		return !(bool)$this->_aErrors;
	}

	/**
	 * Adds a custom validator to the pool.
	 * @param	string 		$sVldtrName	name of custom validator
	 * @param 	callable	$fValidator validator callback function, must return boolean
	 */
	public function add_custom_validator($sVldtrName, $fValidator){
		$this->_aCustomVldtrs[$sVldtrName] = $fValidator;
	}

	/**
	 * Sets data to validate.
	 * @param	array 	$aData 	data to validate
	 */
	public function set_data($aData){
		$this->_bIsValidated = false;
		$this->_aData = $aData;
	}

	/**
	 * Gets data to validate.
	 * @return 	array 	data to validate
	 */
	public function get_data(){
		return $this->_aData;
	}
	
	/**
	 * Normalizes validator config arrays. Behaviour:
	 *  ['validator'] will be transformed to ['validator' => null]
	 *  ['validator' => 'params'] remains
	 * @param  array 	$mVldtrDef 	given validator config array
	 * @return array 				normalized validator config array
	 */
	protected function _normalize_cnfg_arr($mVldtrDef){
		$aVldtrDef = [];
		foreach($mVldtrDef as $mKey => $mValue){
			if(is_numeric($mKey)){
				$sVldtrName = $mValue;
				$mVldtrParams = null;
			}else{
				$sVldtrName = $mKey;
				$mVldtrParams = $mValue;
			}
			$aVldtrDef[$sVldtrName] = $mVldtrParams;
		}
		return $aVldtrDef;
	}


	protected function _decipher_masked_field_references(&$sParamDefinition, $aSource){
		if(is_array($sParamDefinition)){
			foreach($sParamDefinition as &$sParamDefinitionPart){
				$sParamDefinition = &$sParamDefinitionPart;
				$this->_decipher_masked_field_references($sParamDefinition, $aSource);
			}
		}else{
			if(preg_match($this->_sMaskParamPattern, $sParamDefinition, $aMatches)){
				$sParamDefinition = $this->ads_get($aSource, $aMatches[1]);
			}	
		}
	}

	/**
	 * Executes a single validator. 
	 * @param 	string 	$sVldtrName		name of validator
	 * @param  	mixed 	$mInputValue 	input value to validate
	 * @param  	mixed 	$mVldtrParams 	params for validator
	 * @param  	string 	$sFieldId     	form field id
	 * @return 	boolean 				is valid flag
	 */
	protected function _execute_validator($sVldtrName, $mInputValue, $mVldtrParams, $sFieldId){
		// check each param for field value references and replace them by its referenced value
		$this->_decipher_masked_field_references($mVldtrParams, $this->_aData);

		// check if given validator name refers to a default validator
		$sExistingVldtr	= '__validator__' . $sVldtrName;
		if(method_exists($this, $sExistingVldtr)){
			// execute default validator
			return $this->$sExistingVldtr($mInputValue, $mVldtrParams, $sFieldId, $this->_aData);
		}else{
			// check if given validator name refers to custom validator
			$sCustomVldtr = $this->_aCustomVldtrs[$sVldtrName];
			if(is_callable($sCustomVldtr)){
				return $sCustomVldtr($mInputValue, $mVldtrParams, $sFieldId, $this->_aData);
			}else{
				// if no validator is found, throw exception
				throw new \Exception(
					__CLASS__.': Validator "'.$sVldtrName.'" does not exist.'
				);
			}
		}
	}

	/**
	 * Creates the fitting error message and inserts it into errors member.
	 * @param 	string 	$sFieldId		form field id
	 * @param  	string 	$sVldtrName 	name of validator
	 * @param  	mixed 	$mVldtrParams 	parameters of validator
	 */
	protected function _insert_error_message($sFieldId, $sVldtrName, $mVldtrParams){
		// check if there is a more specific error message specified with array dor syntax
		$sMessage = $sFieldId.'.'.$sVldtrName;
		if(isset($this->_aVldtrErrMsg[$sFieldId.'.'.$sVldtrName])){
			$sMessage = $this->_aVldtrErrMsg[$sFieldId.'.'.$sVldtrName];
		}
		elseif(isset($this->_aVldtrErrMsg[$sVldtrName])){
			$sMessage = $this->_aVldtrErrMsg[$sVldtrName];
		}

		// cast validator parameters to string in order to be able to print them as part of the
		// error message
		if(!is_scalar($mVldtrParams)){
			$sVldtrParams = json_encode($mVldtrParams);
		}else{
			$sVldtrParams = (string)$mVldtrParams;
		}

		// add error message to member
		$this->_aErrors[$sFieldId][] = sprintf($sMessage, $sVldtrParams);		
	}

	/**
	 * Validates a given data field using all assigned validators.
	 * @param 	string 	$sFieldId		data field id
	 * @param  	mixed 	$mInputValue 	given input to validate
	 * @param  	array 	$aVldtrDef   	normalized validator definition
	 * @return 	bool					field validation result
	 */
	protected function _validate_single_field($sFieldId, $mInputValue, $aVldtrDef){
		// get all fields this validator will invalidate, at least it will invalidate its own field
		$aInvldtdFields = $this->_extract_invalidated_fields($aVldtrDef);
		if(!$aInvldtdFields){
			$aInvldtdFields = [$sFieldId];
		}

		// loop through all defined validators and execute them one by one, a single failing
		// validator causes the field to be invalid
		$bFieldIsValid = true;
		foreach($aVldtrDef as $sVldtrName => $mVldtrParams){
			// check if defined validator is negated and keep a flag
			$sVldtrName = str_replace('!', '', $sVldtrName, $iNegation);
			
			// execute single validator
			$bIsValid = $this->_execute_validator(
				$sVldtrName, $mInputValue, $mVldtrParams, $sFieldId
			);

			// if validator is negated, negate its result
			if($iNegation){
				$bIsValid = !$bIsValid;
				$sVldtrName = '!'.$sVldtrName;
			}

			// if validator result is negative, set field validation to false and add an error
			// message to each invalidated field
			if(!$bIsValid){
				$bFieldIsValid = false;
				foreach($aInvldtdFields as $sInvldtdField){
					$this->_insert_error_message($sInvldtdField, $sVldtrName, $mVldtrParams);
				}
				// is quick flag is set, break validation iteration right here
				if($this->_bQuick){
					break;
				}
			}
		}

		return $bFieldIsValid;
	}

	/**
	 * Gets and unsets the "invalidates" validator param.
	 * @param	array 	&$aVldtrDef 	validator definition by reference
	 * @return 	array             		invalidating fields
	 */
	private function _extract_invalidated_fields(&$aVldtrDef){
		$aInvldtdFields = [];
		if(isset($aVldtrDef['invalidates'])){
			$aInvldtdFields = $aVldtrDef['invalidates'];
			if(!is_array($aInvldtdFields)){
				$aInvldtdFields = [$aInvldtdFields];
			}
			unset($aVldtrDef['invalidates']);
		}
		return $aInvldtdFields;
	}

	/**
	 * Mask a form field reference id by using delimiters.
	 * @param  string 	$sFieldId 	form field id
	 * @return string         		created masked form field reference
	 */
	protected function _mask_field_reference($sFieldId){
		return sprintf($this->_sMastParamProto, $sFieldId);
	}
}

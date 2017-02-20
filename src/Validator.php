<?php

namespace LuckyNail\SimpleForms;

class Validator extends BaseValidator{
	protected $_aVldtrErrMsg = [
		'array'       => 'VALIDATOR_ARRAY',
		'count'       => 'VALIDATOR_COUNT',
		'date'        => 'VALIDATOR_DATE',
		'date_after'  => 'VALIDATOR_DATE_AFTER',
		'date_before' => 'VALIDATOR_DATE_BEFORE',
		'email'       => 'VALIDATOR_EMAIL',
		'eq'          => 'VALIDATOR_EQUAL',
		'eq_strict'   => 'VALIDATOR_EQUAL_STRICT',
		'fl_range'    => 'VALIDATOR_FL_RANGE',
		'imagetype'   => 'VALIDATOR_IMAGETYPE',
		'in'          => 'VALIDATOR_IN',
		'in_strict'   => 'VALIDATOR_IN_STRICT',
		'int'         => 'VALIDATOR_INT',
		'numeric'     => 'VALIDATOR_NUMERIC',
		'regex'       => 'VALIDATOR_REGEX',
		'required'    => 'VALIDATOR_REQUIRED',
		'strlen'      => 'VALIDATOR_STRING_RANGE',
		'upload'      => 'VALIDATOR_UPLOAD',
		'url'         => 'VALIDATOR_URL',
	];
	protected function __validator__array($aValue, $aVldtrDef = [], $sFieldId){
		if(!is_null($aValue) && !is_array($aValue)){
			return false;
		}
		$bResult = true;
		foreach($aValue as $mValue){
			$bIsValid = $this->_validate_single_field($sFieldId, $mValue, $aVldtrDef);
			if(!$bIsValid){
				$bResult = false;
				if($this->_bQuick){
					break;
				}
			}
		}
		return $bResult;
	}
	protected function __validator__count($sValue, $iCount){
		return $iCount === count($sValue);
	}
	protected function __validator__date($sValue, $sFormat = 'Y-m-d'){
		$oDate = DateTime::createFromFormat($sFormat, $sValue);
    	return $oDate && $oDate->format($sFormat) === $sValue;
	}
	protected function __validator__date_after($sValue, $sCompare, $sFormat = 'Y-m-d'){
		$oDateVal = DateTime::createFromFormat($sFormat, $sValue);
		if(!$oDateVal || $oDateVal->format($sFormat) !== $sValue){
			return false;
		}
		$oDateCompare = DateTime::createFromFormat($sFormat, $sCompare);
		if(!$oDateCompare || $oDateCompare->format($sFormat) !== $sCompare){
			return false;
		}
		return $oDateVal > $oDateCompare;
	}
	protected function __validator__date_before($sValue, $sCompare, $sFormat = 'Y-m-d'){
		$oDateVal = DateTime::createFromFormat($sFormat, $sValue);
		if(!$oDateVal || $oDateVal->format($sFormat) !== $sValue){
			return false;
		}
		$oDateCompare = DateTime::createFromFormat($sFormat, $sCompare);
		if(!$oDateCompare || $oDateCompare->format($sFormat) !== $sCompare){
			return false;
		}
		return $oDateVal < $oDateCompare;
	}	
	protected function __validator__email($sValue){
		return filter_var($sValue, FILTER_VALIDATE_EMAIL);
	}	
	protected function __validator__eq($sValue, $sCompareVal){
		return $sValue == $sCompareVal;
	}
	protected function __validator__eq_strict($sValue, $sCompareVal){
		return $sValue === $sCompareVal;
	}
	protected function __validator__fl_range($sValue, $sInterval){
		if(is_array($sValue)){
			$sValue = count($sValue);
		}
		if(!is_numeric($sValue)){
			return false;
		}
		$iValue = (float)$sValue;
		$mNumMatches = preg_match_all(
			'=^(\(|\]|\[)\s*(\d*)\s*\,\s*(\d*)\s*(\)|\]|\[)$=', $sInterval, $aMatches
		);
		if($mNumMatches){
			$iMin = $aMatches[2][0];
			if(is_numeric($iMin)){
				$iMin = (float)$iMin;
				if($aMatches[1][0] == '['){
					if($iValue < $iMin){
						return false;
					}
				}else{
					if($iValue <= $iMin){
						return false;
					}
				}
			}
			$iMax = $aMatches[3][0];
			if(is_numeric($iMax)){
				$iMax = (float)$iMax;
				if($aMatches[4][0] == ']'){
					if($iValue > $iMax){
						return false;
					}
				}else{
					if($iValue >= $iMax){
						return false;
					}
				}
			}
		}else{
			return false;
		}
		return true;
	}
	protected function __validator__imagetype($aValue, $aTypes){
		try{
			$iImageType = exif_imagetype($aValue['tmp_name']);
			if(!is_array($aTypes)){
				$aTypes = [$aTypes];
			}
			$aTypes = array_map('strtolower', $aTypes);
			$aTypes = array_intersect_key([
				'bmp' => IMAGETYPE_BMP,
				'gif' => IMAGETYPE_GIF,
				'ico' => IMAGETYPE_ICO,
				'jpg' => IMAGETYPE_JPEG,
				'png' => IMAGETYPE_PNG,
				'psd' => IMAGETYPE_PSD,
			], array_flip($aTypes));
			if(in_array($iImageType, $aTypes)){
				return true;
			}
		}catch(\Exception $e){
			return false;
		}
		return false;
	}	
	protected function __validator__in($sValue, $aHaystack){
		return in_array($sValue, $aHaystack);
	}
	protected function __validator__in_strict($sValue, $aHaystack){
		return in_array($sValue, $aHaystack, true);
	}
	protected function __validator__int($sValue){
		return filter_var($sValue, FILTER_VALIDATE_INT);
	}
	protected function __validator__numeric($sValue){
		return is_numeric($sValue);
	}
	protected function __validator__regex($sValue, $sPattern){
		return (bool)preg_match($sPattern, $sValue) ;
	}
	protected function __validator__required($sValue){
		return !($sValue === null || $sValue === '' || !count($sValue));
	}
	protected function __validator__strlen($sValue, $sInterval){
		if(is_numeric($sInterval)){
			return strlen($sValue) == $sInterval;
		}
		if(preg_match_all('=^(\d*)\s*\,\s*(\d*)$=', $sInterval, $aMatches)){
			$iMin = (int)$aMatches[1][0];
			if($iMin){
				if(strlen($sValue) < $iMin){
					return false;
				}
			}
			$iMax = (int)$aMatches[2][0];
			if($iMax){
				if(strlen($sValue) > $iMax){
					return false;
				}
			}
		}else{
			return false;
		}
		return true;		
	}
	protected function __validator__upload($aValue) {
		try{
			return
				is_array($aValue)
				&&
				array_keys($aValue) === ['name', 'type', 'tmp_name', 'error', 'size']
				&&
				$aValue['error'] === 0
				&&
				is_uploaded_file($aValue['tmp_name']);
		}catch(\Exception $e){
			return false;
		}
	}
	protected function __validator__url($sValue){
		// source: https://mathiasbynens.be/demo/url-regex
		return (bool)preg_match("=^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$=iuS", $sValue);
	}
}

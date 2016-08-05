<?php

namespace LuckyNail\SimpleForms;

class BaseForm extends Validator{
	/**
	 * Form Id. Will be printed into id-attribute and be used as name for form data array.
	 * @var	string
	 */
	private $_sFormId;
	
	/**
	 * Form data array
	 * @var	array
	 */
	private $_aFormData = [];

	/**
	 * Raw Form data array
	 * @var array
	 */
	private $_aRawFormData = [];

	/**
	 * Default form data.
	 * @var array
	 */
	private $_aDefaultData = [];

	/**
	 * Array of allowed field names. If any are specified, only those will be left. If left empty,
	 * no whitelisting will be done.
	 * @var array
	 */
	private $_aWhitelistedFields = [];

	/**
	 * Defined filters for this form.
	 * Example array structure: [
	 * 		// field_1: uses custom filter on its own field value
	 * 		'field_1' => '@predefined_filter_name_1', 
	 * 		// field_2: uses str_replace on its own field value and custom filter "encrypt" on 
	 * 		// field value of field_1
	 *		'field_2' => [
	 *			'str_replace' => ['=\s+=', '', '~{field_2}~'], 
	 *			'@encrypt' => '~{field_1}~',
	 *		],
	 *		// uses strtolower, then ucwords on its own field value
	 *		'field_3' => ['strtolower', 'ucwords'], 
	 * ]
	 * @var array
	 */
	private $_aFieldFilters = [];

	/**
	 * Filtered form data array. Will always be whitelisted before filtering.
	 * @var array
	 */
	private $_aFilteredData = [];

	/**
	 * Manually added filter callables. Array structure: [
	 * 		'name_of_filter_1' => function($this, $anyFurtherParam, ...){
	 *   		...
	 * 			return $filterResult;
	 * 		},
	 * 		'name_of_filter_N' => ...
	 * ]
	 * @var array
	 */
	private $_aCustomFilters = [];

	/**
	 * Default html tag attribute values per field
	 * @var array
	 */
	private $_aDefaultAttr = [];

	/**
	 * Css class printed inside invalid tags.
	 * @var string
	 */
	private $_sCssErrorClass = 'error';

	/**
	 * Form-submit-method, may only be "POST" or "GET".
	 * @var	string
	 */
	private $_sFormSubmitMethod = 'POST';

	/**
	 * Encryption-Type 
	 * @var	String
	 */
	private $_sEnctype = 'multipart/form-data';

	/**
	 * Static count of created forms. Needed for automated form id creation.
	 * @var int
	 */
	private static $_iFormCount = 0;

	/**
	 * Key of hidden csrf token field.
	 * @var string
	 */
	private $_sCrsfTokenKey = '::csrf_token';

	/**
	 * Value of csrf token. If false, it will be ignored.
	 * @var mixed
	 */
	private $_sCrsfToken = false;

	/**
	 * Key of hidden submitted field.
	 * @var string
	 */
	private $_sIsSubmittedKey = '::is_submitted';
	
	/**
	 * Callback to manipulate inner html output, for example for the sake of i18n.
	 * This callback must return the manipulated inner html as string.
	 * @var callable
	 */
	protected $_fInnerHtmlCallback;

	/**
	 * Html templates for different tags.
	 * @var array
	 */
	protected $_aFieldProtos = [
		'datalist' => '<datalist %s>%s</datalist>',
		'default'  => '<input %s/>',
		'error'    => '<span %s>%s</span>',
		'label'    => '<label %s>%s</label>',
		'optgroup' => '<optgroup %s>%s</optgroup>',
		'option'   => '<option %s>%s</option>',
		'select'   => '<select %s>%s</select>',
		'textarea' => '<textarea %s>%s</textarea>',
	];

	/**
	 * Characteristics of different input types. These are used to assign different behaviours to
	 * respective input types.
	 *	checked       : Field has a checked-attribute if its field value is found in form data.
	 *	class         : Field has a class-attribute. Error class will automatically be appended.
	 *	data-error_for: Field has a data-attribute named "error_for" containing its name.
	 *	for           : Field has a for-attribute containing its id.
	 *	id            : Field has an id-attribute.
	 *	label         : Field has a label-attribute.
	 *	multiple      : Field has a multiple-attribute.
	 *	name          : Field has a name-attribute.
	 *	selected      : Field has a selected-attribute if its field value is found in form data.
	 *	type          : Field has a type-attribute.
	 *	value         : Field has a value-attribute.
	 * @var array
	 */
	protected $_aAttrTags = [
		'checkbox'       => ['checked', 'class', 'id', 'name', 'type', 'value',],
		'color'          => ['class', 'id', 'name', 'type', 'value',],
		'date'           => ['class', 'id', 'name', 'type', 'value',],
		'datetime'       => ['class', 'id', 'name', 'type', 'value',],
		'datetime-local' => ['class', 'id', 'name', 'type', 'value',],
		'email'          => ['class', 'id', 'name', 'type', 'value',],
		'error'          => ['data-error_for',],
		'file'           => ['class', 'id', 'name', 'type',],
		'hidden'         => ['class', 'id', 'name', 'type', 'value',],
		'label'          => ['class', 'for',],
		'month'          => ['class', 'id', 'name', 'type', 'value',],
		'number'         => ['class', 'id', 'name', 'type', 'value',],
		'optgroup'       => ['label',],
		'option'         => ['selected', 'value',],
		'password'       => ['class', 'id', 'name', 'type', 'value',],
		'radio'          => ['checked', 'class', 'id', 'name', 'type', 'value',],
		'range'          => ['class', 'id', 'name', 'type', 'value',],
		'search'         => ['class', 'id', 'name', 'type', 'value',],
		'select'         => ['class', 'id', 'name', 'multiple',],
		'tel'            => ['class', 'id', 'name', 'type', 'value',],
		'text'           => ['class', 'id', 'name', 'type', 'value',],
		'textarea'       => ['class', 'id', 'name',],
		'time'           => ['class', 'id', 'name', 'type', 'value',],
		'url'            => ['class', 'id', 'name', 'type', 'value',],
		'week'           => ['class', 'id', 'name', 'type', 'value',],
	];

	private $_sEncoding = 'utf-8';

	/**
	 * An optional settings array may be passed to skip additional
	 * setter method calls. All settings might be done later using the specific
	 * setter methods.
	 * 
	 * @param array 	$aSettings 	Associative array with all property values to be passed to
	 * their respective setter methods. Possible keys:
	 * 	'default_values', 'encoding', 'enctype', 'form_submit_method', 'filters', 'id',
	 * 	'inner_html_callback', 'token', 'validators', 'whitelist'
	 */
	public function __construct($aSettings = []){
		// html output settings:
		if(isset($aSettings['id'])){
			$this->set_id($aSettings['id']);
		}else{
			$this->_sFormId = 'form_'.++self::$_iFormCount;			
		}

		if(isset($aSettings['encoding'])){
			$this->_sEncoding = $aSettings['encoding'];
		}

		if(isset($aSettings['enctype'])){
			$this->set_enctype($aSettings['enctype']);
		}

		if(isset($aSettings['form_submit_method'])){
			$this->set_submit_method($aSettings['form_submit_method']);
		}

		if(
			isset($aSettings['inner_html_callback'])
			&&
			is_callable($aSettings['inner_html_callback'])
		){
			$this->_fInnerHtmlCallback = $aSettings['inner_html_callback'];
		}

		// security settings:
		$this->add_whitelisted_fields([$this->_sCrsfTokenKey, $this->_sIsSubmittedKey]);
		if(isset($aSettings['token'])){
			$this->_sCrsfToken = $aSettings['token'];
		}

		if(isset($aSettings['whitelist'])){
			$this->add_whitelisted_fields($aSettings['whitelist']);
		}

		// form processing settings:
		if(isset($aSettings['default_values'])){
			$this->add_default_values($aSettings['default_values']);
		}

		$this->_aRawFormData = $this->get_raw_form_data();
		$this->_aFilteredData = $this->_aRawFormData;

		if(isset($aSettings['filters'])){
			$this->add_filters($aSettings['filters']);
		}

		if(isset($aSettings['validators'])){
			$this->add_validators($aSettings['validators']);
		}
	}

	/**
	 * Add custom filter closures. These may be referred in filters array.
	 * @param  array  $aNewCustomFilters 	new custom filter closures
	 */
	public function add_custom_filter_functions($aNewCustomFilters){
		$this->_aCustomFilters = array_merge($this->_aCustomFilters, $aNewCustomFilters);
	}

	/**
	 * Adds default values to form data. This will not override existing values. Only working, if
	 * form is not submitted at the time.
	 * @param 	array 	$aData 	array of form data
	 */
	public function add_default_values($aData){
		$this->_aDefaultData = $this->_array_merge_recursive_ex($this->_aDefaultData, $aData);
	}

	/**
	 * Gets validated fields' error messages. Those may be modified by a defined innerHtmlCallback.
	 * @param  boolean 	$bInnerHtmlCallback 	flag for using the defined innerHtmlCallback
	 * @return array 							all generated
	 */
	public function get_error_messages($bInnerHtmlCallback = false){
		if(!$bInnerHtmlCallback){
			return $this->_aVldtrErrMsg;
		}
		$aResult = [];
		foreach($this->_aVldtrErrMsg as $sKey => $sMessage){
			$aResult[$sKey] = call_user_func_array(
				$this->_fInnerHtmlCallback, [$this->_sFormId, 'error', $sMessage]
			);
		}
		return $aResult;
	}

	/**
	 * Add any number of field filters.
	 * @param 	array 	$aSettings 	Filter definition, see property description of $_aFieldFilters
	 */
	public function add_filters($aSettings){
		// loop through first level of settings array, each representing a form field, cast string
		// values to arrays for sake of normalization
		foreach($aSettings as $sFieldId => $aActions){
			if(!is_array($aActions)){
				$aActions = [$aActions];
			}

			// normalize definition array, representing filter actions
			$aActions = $this->_normalize_cnfg_arr($aActions);
		
			// loop through actions array, each representing a filter with its params
			foreach($aActions as $fCallable => $aParams){
				// if there are no params, put the masked field's value as single param by default
				if($aParams === null){
					$aParams = $this->_mask_field_reference($sFieldId);
				}
				
				// cast string params to array for normalizition
				if(!is_array($aParams)){
					$aParams = [$aParams];
				}

				// add created filter set as array to $_aFieldFilters
				$aSet = ['callable' => $fCallable, 'field' => $sFieldId, 'params' => $aParams];
				$this->_aFieldFilters[] = $aSet;
			}
		}
	}

	/**
	 * Set whitelisted fields
	 * @param 	array 	$aWhitelistedFields whitelisted field(s)
	 * @return  array 						currently whitelisted fields
	 */
	public function add_whitelisted_fields($aWhitelistedFields){
		if(!is_array($aWhitelistedFields)){
			$aWhitelistedFields = [$aWhitelistedFields];
		}
		$this->_aWhitelistedFields = array_merge($this->_aWhitelistedFields, $aWhitelistedFields);
		array_unique($this->_aWhitelistedFields);
		return $this->_aWhitelistedFields;
	}

	/**
	 * Create attribute string for specific html tag.
	 * @param  string 	$sFieldId 	form field id
	 * @param  string 	$sType     	type of the form field ()
	 * @param  array 	$aAttr 		predefined attributes
	 * @return string 				created attribute string
	 */
	public function attr($sFieldId, $sType, $aAttr = []){
		// if defined, merge given attributes with set up default attributes
		if(isset($this->_aDefaultAttr[$sFieldId])){
			$aAttr = $this->_array_merge_recursive_ex($this->_aDefaultAttr[$sFieldId], $aAttr);
		}

		// get property tags assigned to this type of html tag, fallback to no properties if not
		// defined
		$aAttrTags = isset($this->_aAttrTags[$sType]) ? $this->_aAttrTags[$sType] : [];

		// handle specific properties...

		// class=""
		// ...add a class attribute, prefilled with error class if this field isnt valid
		if(in_array('class', $aAttrTags)){
			// handle error class
			if(
				$this->is_submitted()
				&&
				!$this->is_valid()
				&&
				isset($this->get_errors()[$sFieldId])
			){
				if(isset($aAttr['class'])){
					$aAttr['class'] .= ' '.$this->_sCssErrorClass;
				}else{
					$aAttr['class'] = $this->_sCssErrorClass;
				}
			}
		}
		
		// name="", id="", for=""
		// ...add name, id and for attributes, all prefilled with field id
		foreach(array_intersect(['name', 'id', 'for', 'data-error_for'], $aAttrTags) as $sAttr){
			if(!isset($aAttr[$sAttr])){
				// create regular array notation string from array dot syntax and prepend form
				// namespace
				$aAttr[$sAttr] = $this->_sFormId . '[' . str_replace('.', '][', $sFieldId) . ']';
			}			
		}

		// type=""
		// ...add type attribute prefilled with field type
		if(in_array('type', $aAttrTags)){
			$aAttr['type'] = $sType;
		}

		// value=""
		// ...add value attribute prefilled with field value
		if(in_array('value', $aAttrTags)){
			if(!isset($aAttr['value'])){
				$aAttr['value'] = $this->ads_get($this->_aRawFormData, $sFieldId);
			}
		}

		// checked, selected
		// ...add checked or selected attribute if field value is submitted and attribute not 
		// specified
		foreach(array_intersect(['checked', 'selected'], $aAttrTags) as $sAttr){
			if(!isset($aAttr[$sAttr])){
				// check for specific value or in_array because field may be array field (multiple)
				if(
					$aAttr['value'] == $this->ads_get($this->_aRawFormData, $sFieldId)
					||
					in_array($aAttr['value'], $this->ads_get($this->_aRawFormData, $sFieldId))
				){
					$aAttr[$sAttr] = $sAttr;
				}
			}
		}

		// return created attributes string
		return $this->_create_attr_string($aAttr);
	}

	/**
	 * Create a html datalist tag. This is a mapping to ->field().
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated html tag string
	 */
	public function datalist($aSettings){
		return $this->field(null, 'datalist', $aSettings);
	}

	private function ads_to_classic($sAdsString){

	}

	/**
	 * Always use this inside html form. This will print hidden csrf-token and is-submitted fields.
	 */
	public function enable(){
		return
			$this->field($this->_sCrsfTokenKey, 'hidden', ['value' => $this->_sCrsfToken])
			.$this->field($this->_sIsSubmittedKey, 'hidden', ['value' => true]);
	}

	/**
	 * Print html error message.
	 * @param	string	$sFieldId	form field id error is related to
	 * @param	array	$aSettings	associative array of tag attributes
	 * @return	string				generated HTML string
	 */
	public function error($sFieldId, $aSettings = []){
		// no error output when..
		// ... form is not submitted or valid
		if(!$this->is_submitted() || $this->is_valid()){
			return '';
		}

		// ... or specified field is valid
		// NOTE: following line will trigger validation process (if form has been submitted)
		$aErrors = $this->get_errors();
		if(!isset($aErrors[$sFieldId])){
			return '';
		}

		// get and return html		
		$aSettings['html'] = array_shift($aErrors[$sFieldId]);
		return $this->field($sFieldId, 'error', $aSettings);
	}

	/**
	 * Fetches the raw form data from $_GET/$_POST. Also adds the defined default data. Default
	 * data fields will always appear, others only on form submission.
	 * 
	 * @return 	array 	form raw data array
	 */
	public function get_raw_form_data(){
		$aResult = [];

		// get raw form data, from $_POST/$_GET
		$aRawFormData = 
			$this->_sFormSubmitMethod === 'POST'
			? $_POST[$this->_sFormId]
			: $_GET[$this->_sFormId]
		;

		// get additional data from $_FILES and put them into form data
		if(isset($_FILES[$this->_sFormId])){
			$aFileData = $_FILES[$this->_sFormId];
			foreach($aFileData as $sFileKey => $aFields){
				foreach($aFields as $sField => $sValue){
					$aRawFormData[$sField][$sFileKey] = $sValue;
				}
			}
		}

		// only store non-empty array as form data member because it has to stay an array value and
		// otherwise it would be overriden with null in some cases
		if($aRawFormData){
			$aResult = array_merge($aResult, $aRawFormData);
		}

		// start whitelisting only if any field has been specified for this
		if($this->_aWhitelistedFields){
			$aResult = array_intersect_key($aResult, array_flip($this->_aWhitelistedFields));
		}

		// now add default values
		$aResult = $this->_array_merge_recursive_ex($this->_aDefaultData, $aResult);

		// return the stored form data
		return $aResult;
	}

	/**
	 * Get filtered form data (final product).
	 * @param  	boolean 	$bForce 	flag to refetch all data
	 * @return 	array          			stored filtered form data array
	 */
	public function get($sKey = null){
		$mResult = null;
		if($sKey === null){
			$mResult = $this->_aFilteredData;
		}else{
			if(isset($this->_aFilteredData[$sKey])){
				$mResult = $this->_aFilteredData[$sKey];
			}
		}
		return $mResult;
	}

	/**
	 * Create any of the configured html tags (defined in ->_aFieldProtos) of any of the 
	 * configured form input types (defined in ->_aAttrTags). The optional parameter $aSettings is
	 * used to specify certain tag-specific options and to set up attributes. Specified attribute
	 * values will always override the automatically generated ones.
	 * @param  string 	$sFieldId 	form field id
	 * @param  string 	$sType     	type of the form field ()
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated html tag string
	 */
	public function field($sFieldId, $sType, $aSettings = []){
		// extract optionally defined html content
		$sInnerHtml = isset($aSettings['html']) ? $aSettings['html'] : false;
		unset($aSettings['html']);
		
		// extract optionally defined options (for select, optgroup and datalist tags)
		$aOptions = isset($aSettings['options']) ? $aSettings['options'] : [];
		unset($aSettings['options']);
		
		// rename variable since it now should only contain attributes
		$aAttr = $aSettings;
		
		// get the fitting html tag, fallback to default input tag if none is found
		if(!isset($this->_aFieldProtos[$sType])){
			$sHtml = $this->_aFieldProtos['default'];
		}else{
			$sHtml = $this->_aFieldProtos[$sType];
		}

		// generate attributes and put them into html tag
		$sHtml = sprintf($sHtml, $this->attr($sFieldId, $sType, $aAttr), '%s');

		// handle special html tags...

		// ...html tags that may contain option tags (datalist, optgroup, select)
		if(in_array($sType, ['datalist', 'optgroup', 'select'])){
			foreach($aOptions as $sCaption => $sValue){
				// options setting may be a nested array, signalizing that an optgroup is specified
				if(is_array($sValue)){
					// setup settings, then call ->field() to generate optgroup html tag
					$aSettings = ['label' => $sCaption, 'options' => $sValue];
					$sInnerHtml .= $this->field($sFieldId, 'optgroup', $aSettings);
				}else{
					if(is_int($sCaption)){
						$sCaption = $sValue;
					}
					// setup settings, then call ->field() to generate option html tag
					$aSettings = ['html' => $sCaption, 'value' => $sValue];
					$sInnerHtml .= $this->field($sFieldId, 'option', $aSettings);
				}
			}
		}

		// execute inner html callback for specific tags, if available
		if(
			is_callable($this->_fInnerHtmlCallback)
			&&
			in_array($sType, ['error', 'label', 'option', 'textarea'])
		){
			$sInnerHtml = call_user_func_array(
				$this->_fInnerHtmlCallback,
				[$this->_sFormId, $sType, $sInnerHtml]
			);
		}	

		// ...html tags that contain their values as inner html (textarea)
		if($sType === 'textarea'){
			// put its value as inner html if none is defined
			if($sValue = $this->ads_get($this->_aRawFormData, $sFieldId)){
				$sInnerHtml = $sValue;
			}
		}	

		// ...html tags with inner html that should be escaped (option, textarea)
		if(in_array($sType, ['label', 'option', 'textarea'])){
			$sInnerHtml = $this->_escape($sInnerHtml);
		}

		// set inner html and return generated html tag as string
		if(in_array($sType, [
			'datalist',
			'error',
			'label',
			'optgroup',
			'option',
			'select',
			'textarea',
		])){
			$sHtml = sprintf($sHtml, $sInnerHtml);
		}

		return $sHtml;
	}

	/**
	 * Execute defined field filters, store and return the resulting form data. These filters will
	 * manipulate field values stored in filtered data array. Changes will be applied on the fly,
	 * meaning that, if you define multiple filters for the same field, each filter will use the
	 * returned value of the previous filter.
	 * Example: make a field value lowercase and the first character uppercase:
	 * 	['field_N' => ['strtolower', 'ucfirst']]
	 */
	public function filter(){
		$this->_aFilteredData = $this->_aRawFormData;
		// execute each single filter and remove it, meaning that you can only filter the data once
		foreach($this->_aFieldFilters as $aFldprcssr){
			$this->_execute_filter(
				$aFldprcssr['field'],
				$aFldprcssr['callable'],
				$aFldprcssr['params']
			);
		}
	}

	/**
	 * Gets the filtered form data. Also removes csrf token value and is submitted value before
	 * returning.
	 * @return	array 	filtered form data array
	 */
	public function get_filtered_form_data(){
		return array_diff_key(
			$this->_aFilteredData,
			array_flip([$this->_sCrsfTokenKey, $this->_sIsSubmittedKey])
		);
	}

	/**
	 * Get form enctype.
	 * @return 	string 		form encryption type
	 */
	public function get_enctype(){
		return $this->_sEnctype;
	}

	/**
	 * Get css error class.
	 * @return string 	css error class
	 */
	public function get_error_class(){
		return $this->is_valid() ? '' : $this->_sCssErrorClass;
	}

	/**
	 * Get form id
	 * @return 	string 	form id
	 */
	public function get_id(){
		return $this->_sFormId;	
	}

	/**
	 * Get form submit method.
	 * @return 	string 		form submit method
	 */
	public function get_submit_method(){
		return $this->_sFormSubmitMethod;
	}

	/**
	 * Check if form has been submitted.
	 * @return boolean 	form is submitted
	 */
	public function is_submitted(){
		return $this->_check_token() && $this->_aRawFormData[$this->_sIsSubmittedKey];
	}

	/**
	 * Create a html label tag. This is a mapping to ->field().
	 * @param  string 	$sFieldId 	form field id label is related to
	 * @param  array 	$aSettings 	tag settings
	 * @return string            	generated html tag string
	 */
	public function label($sFieldId, $aSettings){
		return $this->field($sFieldId, 'label', $aSettings);
	}

	/**
	 * Set csrf token value
	 * @param 	mixed 	$mToken 	token value
	 */
	public function set_csrf_token($mToken){
		$this->_sCrsfToken = $mToken;
	}

	/**
	 * Set css error class
	 * @param 	string 	$sErrorClass	css error class
	 */
	public function set_css_error_class($sErrorClass){
		$this->_sCssErrorClass = $sErrorClass;
	}

	/**
	 * Set default html tag attributes.
	 * @param 	array 	$aSettings	default attribute values per field
	 */
	public function set_default_attr($aSettings){
		$this->_aDefaultAttr = $this->_array_merge_recursive_ex($this->_aDefaultAttr, $aSettings);
	}

	/**
	 * Set form enctype.
	 * @param 	string 	$sEnctype 	form encryption type
	 */
	public function set_enctype($sEnctype){
		$this->_sEnctype = $sEnctype;
	}

	/**
	 * Set form id
	 * @param	string	$sFormIdentifier 	form id
	 */
	public function set_id($sFormIdentifier){
		$this->_sFormId = $sFormIdentifier;
	}

	/**
	 * Set form submit method.
	 * @param string	$sFormSubmitMethod 	form submit method ['POST'|'GET']
	 */
	public function set_submit_method($sFormSubmitMethod){
		if(!in_array($sFormSubmitMethod, ['POST', 'GET'])){
			throw new Exception('Given string must be of "POST" or "GET".');
		}
		$this->_sFormSubmitMethod = $sFormSubmitMethod;
	}

	/**
	 * Validate the form.
	 * @return	array 	array with field validations (boolean values of field keys)
	 */
	public function validate(){
		$this->set_data($this->_aFilteredData);
		return parent::validate();
	}

	/**
	 * Helper functino to merge arrays recursively.
	 * @todo	EXPORT TO HELPER CLASS
	 * @param   array 	&$array1	Array to merge into
	 * @param  	array 	&$array2	Array to merge from
	 * @return 	array          		Merged result array
	 */
	private function _array_merge_recursive_ex(&$array1, &$array2){
    	$merged = $array1;
	    foreach($array2 as $key => &$value){
	        if(is_array($value) && isset($merged[$key]) && is_array($merged[$key])){
	            $merged[$key] = $this->_array_merge_recursive_ex($merged[$key], $value);
	        }elseif(is_numeric($key)){
	             if(!in_array($value, $merged)){
	             	$merged[] = $value;
	             }
	        }else{
	            $merged[$key] = $value;
			}
		}
	    return $merged;
	}

	/**
	 * Check crsf token. Returns true, if token is disabled (value === false) or if token value is
	 * matching submitted token field value.
	 * @return	boolean 	csrf token matching
	 */
	private function _check_token(){
		if(
			$this->_sCrsfToken === false
			||
			$this->_aRawFormData[$this->_sCrsfTokenKey] === $this->_sCrsfToken
		){
			return true;
		}
		return false;
	}

	/**
	 * Transforms an array of html tag attributes into a string of those ready to output.
	 * @param  array 	$aAttr 	array of html tag attributes
	 * @return string 			transformed attribute string
	 */
	private function _create_attr_string($aAttr){
		$aAttr = $this->_normalize_cnfg_arr($aAttr);
		$sAttr = '';
		foreach($aAttr as $sAttrName => $sAttrValue){
			$sAttr .= ' '.$sAttrName;
			if($sAttrValue){
				$sAttr .= '="'.$this->_escape($sAttrValue).'"';
			}
		}
		return $sAttr;		
	}

	/**
	 * Escapes html string.
	 * @param	string	$sS		string
	 * @return	string			escaped string.
	 */
	private function _escape($sS) {
		return htmlspecialchars($sS, ENT_QUOTES, $this->_sEncoding);
	}

	/**
	 * Execute a single filter. If callable is not valid or execution if it fails for some reason,
	 * this method will do nothing.
	 * @param  string 	$sFieldId 	form field id
	 * @param  string 	$sCallable  callable name
	 * @param  array 	$aParams   	callbale params array
	 */
	private function _execute_filter($sFieldId, $sCallable, $aParams){
		// check each param for field value references and replace them by its referenced value
		foreach($aParams as $iKey => $mValue){
			if($sFieldKey = $this->_get_masked_field_reference($mValue)){
				$aParams[$iKey] = $this->ads_get($this->_aFilteredData, $sFieldKey);
			}
		}
		
		// if given callable name is not a default php function
		if(!is_callable($sCallable)){
			// check if its a reference to a custom filter function
			$sFiltername = $this->_get_masked_filter_reference($sCallable);
			if(isset($this->_aCustomFilters[$sFiltername])){
				$sCallable = $this->_aCustomFilters[$sFiltername];
			}else{
				// otherwise check if its an existing default filter function
				$sCallable = '__filter__'.$sFiltername;
				if(method_exists($this, $sCallable)){
					$sCallable = [$this, $sCallable];
				}else{
					return;
				}
			}
		}

		// finally call the filter function and directly put its result into the filtered form
		// data member array
		try{
			$mResult = call_user_func_array($sCallable, $aParams);
			$this->ads_set($this->_aFilteredData, $sFieldId, $mResult);
		}catch(\Exception $e){
			return;
		}
	}

	/**
	 * Check if filter callable is a masked filter name and return its unmasked name or otherwise
	 * return false.
	 * @param   $sFilter 	filter callable name
	 * @return 	mixed 		unmasked filter callable name or false
	 */
	private function _get_masked_filter_reference($sFilter){
		$sPattern = '=^@(.+?)$=';
		if(is_string($sFilter) && preg_match($sPattern, $sFilter)){
			return preg_replace($sPattern, '$1', $sFilter);
		}
		return false;
	}
}

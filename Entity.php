<?php
/**
 * DataMapper entity class - each item is fetched into this object
 * 
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Entity
{
	protected $_loaded;
	protected $_data = array();
	protected $_dataModified = array();
	protected $_getterIgnore = array();
	protected $_setterIgnore = array();
	private $errors = array();
	
	
	
	/**
	 * Constructor function
	 */
	public function __construct($data = null)
	{
		// Set given data
		if($data !== null) {
			$this->data($data);
		}
		
		// Mark record as loaded
		$this->loaded(true);
	}
	
	
	/**
	 * Mark row as 'loaded'
	 * Any data set after row is loaded will be modified data
	 *
	 * @param boolean $loaded
	 */
	public function loaded($loaded)
	{
		$this->_loaded = (bool) $loaded;
	}
	
	/**
	 * Set attributes from an array
	 * @param Array $attributes
	 */
	public function attributes($attributes) {
		if(!$attributes) return;
		foreach($attributes as $key => $value) {
			$this->{$key} = $value;
		}
	}
	
	
	/**
	 * Returns false if the record has a numeric ID
	 */
	public function new_record() {
		return !is_numeric($this->id);
	}
	
	/**
	 *	Sets an object or array
	 */
	public function data($data = null)
	{
		if(null !== $data) {
			if(is_object($data) || is_array($data)) {
				foreach($data as $k => $v) {
					$this->$k = $v;
				}
				return $this;
			} else {
				throw new InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
			}
		} else {
			return $this->toArray();
		}
	}
	
	
	/**
	 * Returns array of key => value pairs for row data
	 * 
	 * @return array
	 */
	public function dataModified()
	{
		return $this->_dataModified;
	}
	
	
	/**
	 * Returns array of key => value pairs for row data
	 * 
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($this->_data, $this->_dataModified);
	}
	
	
	/**
	 * Return JSON-encoded row (convenience function)
	 * Only works for basic objects right now
	 * 
	 * @todo Return fully mapped row objects with related rows (has one, has many, etc)
	 */
	public function toJson()
	{
		return json_encode($this->getData());
	}
	
	
	/**
	 * Enable isset() for object properties
	 */
	public function __isset($key)
	{
		return ($this->$key !== null) ? true : false;
	}
	

	public function validate($category = false) {

		if(!isset($this->validations)) return true;

		foreach($this->validations as $field => $rules) {
			
			if(isset($rules['allow_blank']) && $rules['allow_blank'] && empty($this->$field)) continue;
			if($category && (!isset($rules['category']) || $rules['category'] != $category)) continue;
			
			foreach($rules as $rule => $details) {
				switch($rule) {
				case 'required':
					if(empty($this->$field)) $this->add_error($field, 'must be provided.');
					break;
				case 'format': 
					switch($details) {
						case 'email': $details = '^[\w\d+_\-\.]+@[\w\d_\-\.]+\.\w{2,4}$'; break;
						default: break;
					}
					if(preg_match('/'.$details.'/', $this->$field) == 0) $this->add_error($field, 'is invalid.');
					break;
				case 'length':
					if($details['min'] && (strlen($this->$field) < $details['min'])) $this->add_error($field, 'must be at least '.$details['min'].' characters long.');
					break;
				case 'requires_confirmation':
					$confirmation_field = $field . '_confirmation';
					if($this->$field != $this->$confirmation_field) $this->add_error($field, 'must match the confirmation');
					break;
				default:
				}
			}
		}
		$valid = empty($this->errors);
		return $valid;
	}
	
	public function add_error($field, $message) {
		if(!isset($this->errors[$field])) $this->errors[$field] = array();
		$this->errors[$field][] = $message;
	}
	
	public function errors() {
		return $this->errors;
	}

	public function has_errors() {
		return (!empty($this->errors));
	}
	
	public function valid() {
		return empty($this->errors);
	}
	
	public function error_on($field) {
		return (isset($this->errors[$field])) ? $this->errors[$field] : null;
	}
	
	/**
	 * Getter
	 */
	public function __get($var)
	{
		// Check for custom getter method (override)
		$getMethod = 'get_' . $var;
		if(method_exists($this, $getMethod) && !array_key_exists($var, $this->_getterIgnore)) {
			$this->_getterIgnore[$var] = 1; // Tell this function to ignore the overload on further calls for this variable
			$result = $this->$getMethod(); // Call custom getter
			unset($this->_getterIgnore[$var]); // Remove ignore rule
			return $result;
		
		// Handle default way
		} else {
			if(isset($this->_dataModified[$var])) {
				return $this->_dataModified[$var];
			} elseif(isset($this->_data[$var])) {
				return $this->_data[$var];
			} else {
				return null;
			}
		}
	}
	
	
	/**
	 * Setter
	 */
	public function __set($var, $value)
	{
		// Check for custom setter method (override)
		$setMethod = 'set_' . $var;
		if(method_exists($this, $setMethod) && !array_key_exists($var, $this->_setterIgnore)) {
			$this->_setterIgnore[$var] = 1; // Tell this function to ignore the overload on further calls for this variable
			$result = $this->$setMethod($value); // Call custom setter
			unset($this->_setterIgnore[$var]); // Remove ignore rule
			return $result;
		
		// Handle default way
		} else {
			if($this->_loaded) {
				$this->_dataModified[$var] = $value;
			} else {
				$this->_data[$var] = $value;
			}
		}
	}
	
	
	
}
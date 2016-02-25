<?php
namespace glasteel;

trait ResourceModelInterfaceTrait
{
	/*
	protected (string) $name
	*/

	protected $to_validate = null;
	public $last_validation_errors = null; //TODO getter-setter

	//INSTANCE FUNCTIONS

	abstract protected function validateOwnAttributes();

	public function getInstanceName(){
		return $this->name;
	}//getInstanceName()

	public function getResourceRelationships(){
		return false;
	}//getResourceRelationships()

	public function getResourceIncluded(){
		return false;
	}//getResourceIncluded()

	public function validateResource($input){
		if ( !isset($input['relationships']) ){
			$input['relationships'] = [];
		}
		return (
			$this->validateResourceAttributes($input['attributes'])
			&& $this->validateResourceRelationships($input['relationships'])
		);
	}//validateResource()

	public function saveResource($input){
		if ( !isset($input['relationships']) ){
			$input['relationships'] = [];
		}
		//RedBeanPHP Transaction http://redbeanphp.com/index.php?p=/database
		$this->db->begin();
		if (
			$this->saveResourceAttributes($input['attributes'])
			&& $this->saveResourceRelationships($input['relationships'])
		){
			$this->db->commit();
			return true;
		}
		$this->db->rollback();
		return false;
	}//saveResource()

	protected function validateResourceAttributes($input){
		$this->to_validate = $this->augmentAttributes($input);
		$this->last_validation_errors = [];

		$this->validateOwnAttributes();

		$this->to_validate = null;
		if ( count($this->last_validation_errors) > 0 ){
			return false;
		}
		$this->last_validation_errors = null;
		return true;
	}//validateResourceAttributes()

	protected function saveResourceAttributes($to_save){
		$to_save = $this->augmentAttributes($to_save);

		foreach ($to_save as $key => $value) {
			$this->$key = $value;
		}
		
		try {
			$this->db->store($this->primary_bean);
		} catch (\RedBeanPHP\RedException\SQL $e) {
			//pre_r($e->getMessage());
			return false;
		}
		return true;
	}//saveResourceAttributes()

	protected function validateResourceRelationships($input){
		return true;
	}//validateResourceRelationships()

	protected function saveResourceRelationships($input){
		return true;
	}//saveResourceRelationships()

	protected function validateRequired($field){
		if ( !$this->to_validate[$field] ){
			$this->last_validation_errors[$field] = 'missing_required';
			return false;
		}
		return true;
	}//validateRequired()

	protected function validateUnique($field){
		$samename = $this->db->findOne($this->primary_bean_table,$field . ' = :' . $field,[':' . $field=>$this->to_validate[$field]]);
		if ( (!$this->id && $samename) || ($samename && $this->id && $samename->id !== $this->id) ){
			$this->last_validation_errors[$field] = 'not_unique';
			return false;
		}
		return true;
	}//validateUnique()

	protected function augmentAttributes($input){
		foreach ($input as $key => $value) {
			$input[$key] = trim($value);
		}
		$expected = $this->getResourceAttributes();
		foreach ($expected as $key => $current_value) {
			if ( !isset($input[$key]) ){
				$input[$key] = $current_value;
			}
		}
		return $input;
	}//augmentAttributes()

	protected function parseSingle($result){
		if ( is_array($result) ){
			$result = array_pop($result);
			if ( isset($result['single']) ){
				return $result['single'];
			}
		}
		return false;
	}//parseSingle()

	//CLASS META FUNCTIONS

	public static function getResourceSlug(){
		$called_class = get_called_class();
		return $called_class::$pbt;
	}//getResourceSlug()

	public static function getResourceNicename(){
		$base = get_called_class();
		$parts = explode('\\', $base);
		$last = array_pop($parts);
		return $last;
	}//getResourceNicename()

	public static function getResourcesNicename(){
		$called = get_called_class();
		return $called::getResourceNicename() . 's';
	}//getResourcesNicename()

	public static function getActiveCollection(){
		$called = get_called_class();
		$active = [];
		$beans = $called::$DB->find($called::$pbt, 'active = 1 ORDER BY id ASC');
		foreach ($beans as $idx => $bean) {
			$active[] = new $called($bean);
		}
		return $active;
	}//getActiveCollection()

}//trait ResourceModelInterfaceTrait
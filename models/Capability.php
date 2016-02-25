<?php
namespace glasteel;

class Capability extends ModelBase
{
	protected $primary_bean_table = 'capability';
	protected static $pbt = 'capability';
	protected static $active_caps = null;

	public static function getResourcesNicename(){
		return 'Capabilities';
	}//getResourceNicename()

	public function getResourceAttributes(){
		return [
			'name' => $this->name, //required, unique
			'description' => $this->description,
			'slug' => $this->slug, //required, unique
		];
	}//getResourceAttributes()

	//TODO abstract this in ModelBase?
	public function getResourceRelationships(){
		return [
			'role' => $this->getRolesUsingThis(),
		];
	}//getResourceRelationships()

	//TODO abstract this in ModelBase?
	protected function validateResourceRelationships($input){
		return $this->validateOrSaveRoles($input);
	}//validateResourceRelationships()

	protected function saveResourceRelationships($input){
		return $this->validateOrSaveRoles($input,'save');
	}//saveResourceRelationships()

	private function validateOrSaveRoles($input,$action='validate'){
		if ( $action != 'validate' && $action != 'save' ){
			//TODO exception
			return false;
		}
			
		$submitted_roles = isset($input['role']) ? $input['role'] : false;
		if ( !$submitted_roles ){
			return true;
		}
		
		$new_role_ids = [];
		foreach ($submitted_roles as $idx => $role_id_object) {
			if ( $role_id_object['type'] != 'role' ){
				return false;	
			}
			$new_role_ids[] = $role_id_object['id'];
		}

		if ( $action == 'validate' ){
			$role_beans = $this->db->find(
				'role',
				' id IN (' . $this->db->genSlots($new_role_ids) . ')', //TODO test active
				$new_role_ids
			);
			//TODO remove exportAll & count $db rows?
			return ( count($this->db->exportAll($role_beans)) == count($new_role_ids) ) ? true : false;
		}

		if ( $action == 'save' ){
			foreach (Role::getActiveCollection() as $idx => $role) {
				
				if ( in_array($role->id, $new_role_ids) ){
					$mod = $role->addCap($this->slug);
				}else{
					$mod = $role->removeCap($this->slug);
				}
				if (!$mod) {
					return false;
				}
			}
			return true;
		}
	}//validateOrSaveRoles()

	protected function validateOwnAttributes(){
		if ( $this->validateRequired('name') ){
			$this->validateUnique('name');
		}
		if ( $this->validateRequired('slug') ){
			$this->validateUnique('slug');
		}
	}//validateOwnAttributes()

	protected function getRolesUsingThis(){
		return Role::rolesUsingCap($this->slug);
	}//getRolesUsingThis()

}//class Capability
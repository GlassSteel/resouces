<?php
namespace glasteel;

class User extends ModelBase
{
	use RollsCapsTrait;

	protected $primary_bean_table = 'user';
	protected static $pbt = 'user';
	
	public function setUserByPID($pid){
		$user_bean = $this->db->findOne(
			'user',
			'unc_pid = :unc_pid',
			[
				':unc_pid' => $pid,
			]
		);
		if ( $user_bean ){
	        $this->setPrimaryBean($user_bean);
	        return true;
		}
		return false;
	}//getUserByPID()

	public function getInstanceName(){
		return ucfirst( trim($this->first_name) ) . ' ' . ucfirst( trim($this->last_name) );
	}//getInstanceName()

	public function getResourceAttributes(){}
	protected function validateOwnAttributes(){}
	public function saveResourceAttributes($to_save){}

}//class User
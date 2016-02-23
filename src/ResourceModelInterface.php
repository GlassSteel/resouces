<?php
namespace glasteel;

interface ResourceModelInterface
{
	
	/**
	 * Return a natural language identifier for the instance,
	 * i.e. $Userinstance->getInstanceName() returns 'John Doe'
	 */
	public function getInstanceName();

	/**
	 * Return a natural language identifer for the instance class,
	 * i.e. $Userinstance->getResourceNicename() returns 'User'
	 */
	public static function getResourceNicename();

	/**
	 * Return a brief unique identifer for the instance class,
	 * i.e. $Userinstance->getResourceSlug() returns 'user'
	 */
	public static function getResourceSlug();

}//interface ResourceModelInterface
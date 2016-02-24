<?php
namespace glasteel;

trait ResourceControllerInterfaceTrait
{
	/*
	*/
	
	public function init($resource_model_class,$container){

	}//init()

	//CREATE METHODS

	//Access a new single resource web form, via GET
	//e.g. /new/user returns Web Form for new User info
	public function getNewSingleForm($request, $response, $args){

	}//getNewSingleForm()

	//Submit a new single resource, via POST
	//e.g. /create/user recieves Web Form POST of new User info
	public function createSingle($request, $response, $args){

	}//createSingle()

	//READ METHODS

	//Access an API representation of a single resource instance, via GET
	//e.g. /api/user/2 returns JSON representation of User with ID = 2
	public function getSingleApi($request, $response, $args){

	}//getSingleApi()

	//Access a web representation of a single resource instance, via GET
	//e.g. /view/user/2 returns Web Page with info on User with ID = 2
	public function getSingleView($request, $response, $args){

	}//getSingleView()

	//Access an API representation of a collection of resources, via GET
	//e.g. /api/users returns JSON representation of all Users
	public function getCollectionApi($request, $response, $args){

	}//getCollectionApi()

	//Access a web representation of a collection of resources, via GET
	//e.g. /index/users returns Web Page with list of all Users
	public function getCollectionWeb($request, $response, $args){

	}//getCollectionWeb()

	//UPDATE METHODS

	//Access an existing single resource editing web form, via GET
	//e.g. /edit/user/2 returns Web Form for editing User with ID = 2
	public function getEditSingleForm($request, $response, $args){

	}//getEditSingleForm()

	//Submit updates to single resource, via POST
	//e.g. /update/user recieves Web Form POST of edits for User with ID = 2
	public function updateSingle($request, $response, $args){

	}//updateSingle()

	//DELETE METHODS

	//Access an existing single resource delete confirmation web form, via GET
	//e.g. /confirm/user/2 returns Web Form for confirming deletion of User with ID = 2
	public function getDeleteSingleForm($request, $response, $args){

	}//getDeleteSingleForm()

	//Delete to single resource, via POST
	//e.g. /delete/user recieves Web Form POST of User ID = 2 confirmed deletion
	public function deleteSingle($request, $response, $args){

	}//deleteSingle()

}//trait ResourceControllerInterfaceTrait
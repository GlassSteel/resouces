<?php
namespace glasteel;

trait ResourceControllerInterfaceTrait
{
	protected $renderer;
	protected $resource_model_class;
	protected $router;
	protected $auth_user;
	protected $flash;
	protected $allowed_roles = [
		'resource_editor' => 'resource_editor'
	];
	protected $relationships_for_meta = [];

	public function init($resource_model_class,$container){
		$this->resource_model_class = $resource_model_class;
		$this->renderer = $container->get('renderer');
		$this->router = $container->get('router');
		$this->auth_user = $container->get('auth')->userGS();
		$this->flash = $container->get('flash');
	}//init()

	// format resource collection
	// @param $resources is instances of $resource_class or ids of same
	public function formatCollection($resource_class,$resources){
		$collection = [];
		foreach ($resources as $idx => $resource) {
			$collection[] = $this->formatSingle($resource_class,$resource);
		}
		return $collection;
	}//getCollection()

	// format single resource
	// @param $resource is instanceof $resource_class or id of same
	public function formatSingle($resource_class,$resource=null,$flat=false){
		if ( !( $resource instanceof $resource_class ) ){
			$resource = new $resource_class($resource);
		}
		$id = $resource->id;
		$slug = $resource_class::getResourceSlug();
		$relationships = $resource->getResourceRelationships();
		
		$json = [
			'type' => $slug,
			'id' => ($id) ? $id : 0,
			'attributes' => ($id) ? $resource->getResourceAttributes() : new \stdclass,
			'meta' => [
				'resource_nicename' => $resource::getResourceNicename(),
				'instance_name' => ($id) ? $resource->getInstanceName() : '',
			],

		];

		if ( $relationships ){
			$json['relationships'] = [];
			$relationship_classes = $resource::getResourceRelationshipClasses();
			
			foreach ($relationships as $rel => $members) {
				$json['relationships'][$rel] = [];
				$class = $relationship_classes[$rel];
				if ( is_array($members) ){
					foreach ($members as $idx => $member) {
						$rel_data = [
							'type' => $class::getResourceSlug(),
							'id' => $member->id,
						];

						if ( method_exists($resource, 'getResourceRelationshipMeta') ){
							$rel_data['meta'] = $resource->getResourceRelationshipMeta($rel,$member);
						}

						$json['relationships'][$rel][] = $rel_data;
					}
				}
				$this->relationships_for_meta[$rel] = $class::getResourceSlug();
			}
		}

		return $json;
	}//formatSingle()

	protected function wrapJSON($data,$collection=false){
		$resource_model_class = $this->resource_model_class;
		$resource_slug = $resource_model_class::getResourceSlug();

		if ( $collection===true ){
			//Collection
			$self = $this->router->pathFor('get_collection_api_' . $resource_slug);
		}else{
			//Single
			if ( $data['id'] ){
				$self = $this->router->pathFor('get_single_api_' . $resource_slug, [$resource_slug . '_id' => $data['id']]);
			}else{
				$self = $this->router->pathFor('create_single_' . $resource_slug);
			}	
		}
		$meta = [
			'related_collections' => [],
		];
		foreach ($this->relationships_for_meta as $rel => $resource_slug) {
			$meta['related_collections'][$rel]['url'] = $this->router->pathFor('get_collection_api_' . $resource_slug);
			$meta['related_collections'][$rel]['type'] = $resource_slug;
		}
		$this->relationships_for_meta = [];
		return [
			'data' => $data,
			'links' => [
				'self' => $self,
			],
			'meta' => $meta,
		];
	}//wrapJSON()

	//CREATE METHODS

	//Access a new single resource web form, via GET
	//e.g. /new/user returns Web Form for new User info
	public function getNewSingleForm($request, $response, $args, $vars=[]){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
	 		return $response;
	 	}

 		$vars['resource'] = $this->wrapJSON(
 			$this->formatSingle($this->resource_model_class)
 		);

 		$template = 'resources/resource_form.php';
	 	return $this->renderer->render($response, $template, $vars);
	}//getNewSingleForm()

	//Submit a new single resource, via POST
	//e.g. /create/user recieves Web Form POST of new User info
	public function createSingle($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response;
		}

		$resource = new $this->resource_model_class;
		$input = $request->getParsedBody()['data'];

		$return = $this->validateAndSave($resource,$input);
		return $response->withJsonAPI($return['to_json'],$return['code']);
	}//createSingle()

	//READ METHODS

	//Access an API representation of a single resource instance, via GET
	//e.g. /api/user/2 returns JSON representation of User with ID = 2
	public function getSingleApi($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response; //TODO return json api error
		}
		$this->relationships_for_meta = [];
		$resource_model_class = $this->resource_model_class;
		$resource_slug = $resource_model_class::getResourceSlug();
		return $response->withJsonAPI(
			$this->wrapJSON(
				$this->formatSingle($this->resource_model_class,$args[$resource_slug])
			)
		);
	}//getSingleApi()

	//Access a web representation of a single resource instance, via GET
	//e.g. /view/user/2 returns Web Page with info on User with ID = 2
	public function getSingleView($request, $response, $args){
		$response->write(__METHOD__ . '<br />');
		foreach ($args as $key => $value) {
			$response->write($key .' =<br />');
			$response->write( print_r($value,true) );
		}

	}//getSingleView()

	//Access an API representation of a collection of resources, via GET
	//e.g. /api/users returns JSON representation of all Users
	public function getCollectionApi($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response; //TODO return json api error
		}
		$resource_model_class = $this->resource_model_class;
		return $response->withJsonAPI(
			$this->wrapJSON(
				$this->formatCollection(
					$resource_model_class, $resource_model_class::getActiveCollection()
				),
				true //collection flag
			)
		);
	}//getCollectionApi()

	//Access a web representation of a collection of resources, via GET
	//e.g. /index/users returns Web Page with list of all Users
	public function getCollectionWeb($request, $response, $args){
		$response->write(__METHOD__ . '<br />');
		foreach ($args as $key => $value) {
			$response->write($key .' =<br />');
			$response->write( print_r($value,true) );
		}

	}//getCollectionWeb()

	//UPDATE METHODS

	//Access an existing single resource editing web form, via GET
	//e.g. /edit/user/2 returns Web Form for editing User with ID = 2
	public function getEditSingleForm($request, $response, $args, $vars=[]){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
	 		return $response;
	 	}
	 	$this->relationships_for_meta = [];
	 	$resource_model_class = $this->resource_model_class;
		$resource_slug = $resource_model_class::getResourceSlug();
	 	$vars['resource'] = $this->wrapJSON(
 			$this->formatSingle($this->resource_model_class,$args[$resource_slug])
 		);

 		$template = 'resources/resource_form.php';
	 	return $this->renderer->render($response, $template, $vars);
	}//getEditSingleForm()

	//Submit updates to single resource, via POST
	//e.g. /update/user recieves Web Form POST of edits for User with ID = 2
	public function updateSingle($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
	 		return $response;
	 	}
		//TODO check data type & id against resource > via middleware, serve 409 error on mismatch
	 	$resource_model_class = $this->resource_model_class;
	 	$resource_slug = $resource_model_class::getResourceSlug();
 		$resource = $args[$resource_slug];
		$input = $request->getParsedBody()['data'];
 		$return = $this->validateAndSave($resource,$input);
 		return $response->withJsonAPI($return['to_json'],$return['code']);
	}//updateSingle()

	//DELETE METHODS

	//Access an existing single resource delete confirmation web form, via GET
	//e.g. /confirm/user/2 returns Web Form for confirming deletion of User with ID = 2
	public function getDeleteSingleForm($request, $response, $args){
		$response->write(__METHOD__ . '<br />');
		foreach ($args as $key => $value) {
			$response->write($key .' =<br />');
			$response->write( print_r($value,true) );
		}

	}//getDeleteSingleForm()

	//Delete to single resource, via POST
	//e.g. /delete/user recieves Web Form POST of User ID = 2 confirmed deletion
	public function deleteSingle($request, $response, $args){
		$response->write(__METHOD__ . '<br />');
		foreach ($args as $key => $value) {
			$response->write($key .' =<br />');
			$response->write( print_r($value,true) );
		}

	}//deleteSingle()

	//AUTHORIZATION UTILITIES

	protected function gateway($capabillity,$response){
		foreach ($this->allowed_roles as $role => $_role) {
			if ( $this->auth_user->is($role) ){
				return $response;
			}
		}
		if ( !$this->auth_user->can($capabillity) ){
			$this->flash->addMessage('Permissions Error', 'Missing capabillity "' . $capabillity . '"');
			$response = $response->withStatus(403);
		}
		return $response;
	}//gateway()

	protected function getNeededCap($method){
		return $method . '_' . $this->resource_model_class;
	}//getNeededCap()

	public function addRole($role){
		if ( !array_key_exists($role, $this->allowed_roles) ){
			$this->allowed_roles[$role] = $role;
		}
	}//addRole()

	//CRUD UTILITIES

	protected function validateAndSave($resource,$input){
		if( $resource->validateResource($input) ){
			if ( !$resource->saveResource($input) ){
				//Valid submission did not save due to database error
				$code = 500;
				$to_json = [
					'errors' => [
						'code' => 'failed_save',
						'title' => 'Your submission could not be saved due to an internal error',
					],
				];
			}else{
				//Valid submission saved to database
				$code = 200;
				//As per http://jsonapi.org/format/#crud-updating, return entire resource b/c of e.g. last_modified 
				$to_json = $this->wrapJSON(
					$this->formatSingle($this->resource_model_class,$resource->id)
				);
			}
		}else{
			//Invalid submission
			$code = 409;
			$to_json = [
				'errors' => [
					'code' => 'failed_validation',
					'title' => 'Your submission contains one or more invalid or missing values',
					'meta' => [
						'fields' => $resource->last_validation_errors,
					],
				],
			];
		}
		return [
			'code' => $code,
			'to_json' => $to_json,
		];
	}//validateAndSave()

}//trait ResourceControllerInterfaceTrait
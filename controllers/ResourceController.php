<?php
namespace glasteel;

class ResourceController
{
	protected $renderer;
	protected $resource_type;
	protected $router;
	protected $auth_user;
	protected $flash;
	protected $jsonapi_controller;
	protected $allowed_roles = [];

	public function __construct($renderer, $resource_type, $router, User $auth_user, $flash, JSONApiController $jsonapi_controller){
		if ( !method_exists($renderer, 'render') ){
			throw new \InvalidArgumentException(
				__METHOD__ . ' requires parameter $renderer to implement a \'render\' method.'
			);
		}
		$this->renderer = $renderer;
		$this->resource_type = $resource_type;
		$this->router = $router;
		$this->auth_user = $auth_user;
		$this->flash = $flash;
		$this->jsonapi_controller = $jsonapi_controller;
	}//__construct()

	public function index($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response; //TEMP
		}

		$modelclass = __NAMESPACE__ . '\\' . ucfirst($this->resource_type);

		return $response->withJsonAPI(
			$this->jsonapi_controller->formatCollection(
				$this->resource_type, $modelclass::getActiveCollection()
			)
		);
	}//index()

	public function get($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response; //TEMP
		}
		return $response->withJsonAPI(
			$this->wrap_resource_for_get($args[$this->resource_type . '_jsonapi'])
		);
	}//get()

	//TODO move to jsonapi_controller class?
	protected function wrap_resource_for_get($data){
		if ( $data['id'] ){
			$self = $this->router->pathFor('get_' . $data['type'], [$data['type'] . '_id' => $data['id']]);
		}else{
			$self = $this->router->pathFor('create_' . $data['type']);
		}
		return [
			'data' => $data,
			'links' => [
				'self' => $self,
			],
		];
	}//wrap_resource_for_get()

	public function newitem($request, $response, $args, $vars=[]){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response;
		}
		
		$vars['resource'] = $this->wrap_resource_for_get(
			$this->jsonapi_controller->getResource($this->resource_type)
		);
		$template = 'resources/resource_form.php';
		
		return $this->renderer->render($response, $template, $vars);
	}//new()

	public function create($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response;
		}
		
		$modelclass = __NAMESPACE__ . '\\' . ucfirst($this->resource_type);
		$resource = new $modelclass;
		$input = $request->getParsedBody()['data'];

		$return = $this->validateAndSave($resource,$input);
		return $response->withJsonAPI($return['to_json'],$return['code']);
	}//create()

	public function edit($request, $response, $args, $vars=[]){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response;
		}

		$vars['resource'] = $this->wrap_resource_for_get(
			$args[$this->resource_type . '_jsonapi']
		);
		$vars['method'] = 'patch';
		$vars['action'] = $vars['resource']['links']['self'];
		
		return $this->renderer->render($response, 'resources/resource_form.php', $vars);
	}//get()

	public function update($request, $response, $args){
		$response = $this->gateway($this->getNeededCap(__FUNCTION__), $response);
		if ( $response->getStatusCode() != 200 ){
			return $response;
		}

		$resource = $args[$this->resource_type];
		$input = $request->getParsedBody()['data'];
		//TODO check data type & id against resource > via middleware, serve 409 error on mismatch
		
		$return = $this->validateAndSave($resource,$input);
		return $response->withJsonAPI($return['to_json'],$return['code']);
	}//update()

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
				$to_json = $this->wrap_resource_for_get(
					$this->jsonapi_controller->getResource($this->resource_type,$resource->id)
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

	public static function registerResource($resource_type,$app,$plural=false,$roles=[]){
		//TODO move roles to $app->settings
		$resource_type_plural = strtolower(($plural) ? $plural : $resource_type . 's');
		
		$subclass_key = ucfirst($resource_type) . substr(strrchr(__CLASS__, '\\'), 1);
		
		$controller_class = __NAMESPACE__ . '\\' . $subclass_key;
		
		if ( !class_exists($controller_class) ){
			$controller_class = __CLASS__;
		}

		$container = $app->getContainer();

		$container[$subclass_key] = function($c) use ($controller_class,$resource_type,$roles){
			$controller = new $controller_class(
				$c->get('renderer'),
				$resource_type,
				$c->get('router'),
				$c->get('auth')->userGS(),
				$c->get('flash'),
				$c->get('jsonapi')
			);
			foreach ($roles as $role) {
				$controller->addRole($role);
			}
			return $controller;
		};

		$routes_registered = [];

		//	GET  list/resource_type_plural 			> 	view index (list) TODO
		//	GET  view/resource_type_plural/{id}		>	view single item TODO
		
		// 	DELETE TODO
		
		//	ACCESS RESOURCE & COLLECTION JSON
		$route = '/' . $resource_type_plural;
		$routes_registered['collection_json'] = $route;
		$app->get($route, $subclass_key . ':index')
			->setName('get_' . $resource_type)
		;

		$route = '/' . $resource_type_plural . '/{' . $resource_type . '_id}';
		$routes_registered['single_item_json'] = $route;
		$app->get($route, $subclass_key . ':get')
			->setName('get_' . $resource_type)
			->add('IdParamsJSONAPI') //Move out of middleware into this class?
			->add('IdParamsExist')
			->add('IdParamsAreInt')
		;

		// 	NEW ITEM FORM / POST TO CREATE
		// 	GET /new/resource_type_plural 	>   show new single item form
		$route = '/new/' . $resource_type_plural;
		$routes_registered['new_single_item_form'] = $route;
		$app->get($route, $subclass_key . ':newitem')
			->setName('newitem_' . $resource_type)
		;

		// 	POST /resource_type_plural 	>   create new single item
		$route = '/' . $resource_type_plural;
		$routes_registered['post_new_single_item'] = $route;
		$app->post($route, $subclass_key . ':create')
			->setName('create_' . $resource_type)
		;

		//	EDIT FORM / PATCH TO UPDATE
		//	GET  /edit/resource_type_plural/{id} >	show single item edit form
		$route = '/edit/' . $resource_type_plural . '/{' . $resource_type . '_id}';
		$routes_registered['single_item_edit_form'] = $route;
		$app->get($route, $subclass_key . ':edit')
			->setName('edit_' . $resource_type)
			->add('IdParamsJSONAPI') //Move out of middleware into this class?
			->add('IdParamsExist')
			->add('IdParamsAreInt')
		;
		
		//	PATCH  /resource_type_plural/{id}	>	update single item
		$route = '/' . $resource_type_plural . '/{' . $resource_type . '_id}';
		$routes_registered['patch_edit_single_item'] = $route;
		$app->patch($route, $subclass_key . ':update')
			->setName('update_' . $resource_type)
			->add('IdParamsJSONAPI') //Move out of middleware into this class?
			->add('IdParamsExist')
			->add('IdParamsAreInt')
		;

		return $routes_registered;
	}//registerResource()

	protected function getNeededCap($method){
		return $method . '_' . $this->resource_type;
	}//getNeededCap()

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

	public function addRole($role){
		if ( !array_key_exists($role, $this->allowed_roles) ){
			$this->allowed_roles[$role] = $role;
		}
	}//addRole()

	public function removeRole($role){
		if ( array_key_exists($role, $this->allowed_roles) ){
			unset($this->allowed_roles[$role]);
		}
	}//removeRole()

	protected function allowRole($role){
		if ( array_key_exists($role, $this->allowed_roles) ){
			return true;
		}
		return false;
	}//allowRole()

}//class ResourceController
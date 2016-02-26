<?php
namespace glasteel;

use Slim\App;
/**
 * Adds Slim 3 routes for basic CRUD views and operations on Resources
 */
class ResourceRoutesBuilder
{
	/**
	 * Slim 3 $app instance, for router and container access
	 * TODO: remove in favor of $container && $router properties
	 */
	protected $app;
	protected $settings = [
		'controller_class_base' => __NAMESPACE__ . '\\' . 'ResourceController',
	];

	static $routes_registered = [];

	const RESOURCE_CONTROLLER_INTERFACE = __NAMESPACE__ . '\\' . 'ResourceControllerInterface';
	const RESOURCE_MODEL_INTERFACE = __NAMESPACE__ . '\\' . 'ResourceModelInterface';

	/**
	 * DI Slim 3 instance from container
	 */
	public function __construct(\Slim\App $app){
		$this->app = $app;
		$container = $this->app->getContainer();
		//Overwrite default settings with settings from container, if any
		$settings = $container->get('settings');
		$settings = 
			(isset($settings[__CLASS__]) && is_array($settings[__CLASS__]))
			? $settings[__CLASS__] : []
		;
		foreach ($this->settings as $key => $value) {
			if ( array_key_exists($key, $settings) && $settings[$key] ){
				$this->settings[$key] = $settings[$key];
			}
		}
	}//__construct()

	public function buildDefaultRoutes(){
		$resource_model_classes = [
			'glasteel\User' => [],
			'glasteel\Role' => [],
			'glasteel\Capability' => [
				'plural' => Capability::getResourcesNicename(),
			],
		];
		foreach ($resource_model_classes as $class => $config) {
			$this->buildRoutes($class,$config);
		}
	}

	/**
	 * Registers CRUD routes with the Slim router
	 * Routes will call the methods of a controller class implementing ResourceControllerInterface
	 * @param string $resource_model_class name of a model class implementing ResourceModelInterface
	 * @param array $config May contain the following keys:
	 * <code>
	 * $config = [
	 * 	'single' => optional string, slug for URL segments, defaults to strtolower( ResourceModelInterface::getResourceSlug() )
	 *  'plural' => optional string, plural version of slug for URL segments, defaults to single . 's'
	 * 	'controller_class_base' => optional string, fully qualified name of class implementing ResourceControllerInterface
	 * 	'controller_class' => optional string, fully qualified name of class implementing ResourceControllerInterface
	 * ];
	 * </code>
	 */
	public function buildRoutes($resource_model_class,array $config=null){
		if ( is_null($config) ){
			$config = [];
		}
		$app = $this->app;
		$container = $app->getContainer();
		$settings = $this->settings;

		//model class must implement ResourceModelInterface else throw exception
		$resource_model_implements = (class_exists($resource_model_class)) ? class_implements($resource_model_class) : [];
		if ( !in_array(self::RESOURCE_MODEL_INTERFACE, $resource_model_implements) ){
			//TODO exception
			die($resource_model_class . ' must implement ' . self::RESOURCE_MODEL_INTERFACE);
		}
		$resource_slug = $resource_model_class::getResourceSlug();
		//pre_r('$resource_slug = ' . $resource_slug);

		//URL segments should always be lower case
		//If no plural in config provided, append 's' to $url_single
		$url_single = strtolower(
			(isset($config['single']) && $config['single']) ? $config['single'] : $resource_slug
		);

		$url_plural = strtolower(
			(isset($config['plural']) && $config['plural']) ? $config['plural'] : $url_single . 's'
		);

		//pre_r($url_single,$url_plural);

		/*
		Determine the class to be used as the controller for the CRUD routes
		Determination Priority
			1) 	fully qualified class name as specified in $config['controller_class']
			2) 	subclass of fully qualified class name as specified in $config['controller_class_base'],
				of naming convention namespace\ . ucfirst($resource_model_class) . $config['controller_class_base'], if exists
			3) 	fully qualified class name as specified in $config['controller_class_base'],
				if subclass above does not exist
			4)	subclass of __NAMESPACE__\ . ucfirst($resource_model_class) . $settings['controller_class_base'], if exists
			5)	subclass of __NAMESPACE__\ . $settings['controller_class_base'], if subclass above does not exist
		Determined controller class must implement RESOURCE_CONTROLLER_INTERFACE
		*/
		if ( isset($config['controller_class']) ){
			$controller_class = $config['controller_class'];
		}else{
			$controller_class_base = (isset($config['controller_class_base']))
				? $config['controller_class_base'] : $settings['controller_class_base']
			;
			$controller_class_base_namesegments = explode('\\', $controller_class_base);
			$controller_class_base_root = array_pop($controller_class_base_namesegments);
			$controller_class =
				implode('\\', $controller_class_base_namesegments)
				. '\\'
				. ucfirst($resource_model_class)
				. $controller_class_base_root
			;
			if ( !class_exists($controller_class) ){
				$controller_class = $controller_class_base;
			}
		}

		//controller class must implement ResourceControllerInterface else throw exception
		$controller_implements = (class_exists($controller_class)) ? class_implements($controller_class) : [];
		if ( !in_array(self::RESOURCE_CONTROLLER_INTERFACE, $controller_implements) ){
			die($controller_class . ' must implement ' . self::RESOURCE_CONTROLLER_INTERFACE);
			//TODO exception
		}

		//Instantiate the controller and register with the app container
		$container[$controller_class . '_' . $resource_slug] = function($c) use ($controller_class,$resource_model_class){
			$controller = new $controller_class;
			$controller->init($resource_model_class,$c);
			return $controller;
		};

		/*
		Build the routes
		*/

		//CREATE ROUTES

		//Access a new single resource web form, via GET
		//e.g. /new/user returns Web Form for new User info
		$route_key = 'get_new_single_form';
		$route = '/' . $url_single . '/new';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//Submit a new single resource, via POST
		//e.g. /create/user recieves Web Form POST of new User info
		$route_key = 'create_single';
		$route = '/' . $url_single . '/new';
		$app->post($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//READ ROUTES

		//Access an API representation of a single resource instance, via GET
		//e.g. /api/user/2 returns JSON representation of User with ID = 2
		$route_key = 'get_single_api';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			//->add('IdParamsJSONAPI')	//TODO Make method of JSON API class? //TODO weed out this class
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;


		//Access a web representation of a single resource instance, via GET
		//e.g. /view/user/2 returns Web Page with info on User with ID = 2
		$route_key = 'get_single_view';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}/view';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//Access an API representation of a collection of resources, via GET
		//e.g. /api/users returns JSON representation of all Users
		$route_key = 'get_collection_api';
		$route = '/' . $url_plural;
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//Access a web representation of a collection of resources, via GET
		//e.g. /index/users returns Web Page with list of all Users
		$route_key = 'get_collection_web';
		$route = '/' . $url_plural . '/index';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//UPDATE ROUTES

		//Access an existing single resource editing web form, via GET
		//e.g. /edit/user/2 returns Web Form for editing User with ID = 2
		$route_key = 'get_edit_single_form';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}/edit';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//Submit updates to single resource, via PATCH
		//e.g. /update/user recieves Web Form PATCH of edits for User with ID = 2
		$route_key = 'update_single';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}';
		$app->patch($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//DELETE ROUTES

		//Access an existing single resource delete confirmation web form, via GET
		//e.g. /confirm/user/2 returns Web Form for confirming deletion of User with ID = 2
		$route_key = 'get_delete_single_form';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}/delete';
		$app->get($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

		//Delete to single resource, via DELETE
		//e.g. /delete/user recieves Web Form DELETE of User ID = 2 confirmed deletion
		$route_key = 'delete_single';
		$route = '/' . $url_single . '/{' . $resource_slug . '_id}';
		$app->delete($route, $controller_class . '_' . $resource_slug . ':' . $this->routeKeyToMethodName($route_key) )
			->setName($route_key . '_' . $resource_slug)
			->add('IdParamsExist')		//TODO Make method of Resource Model Class?
			->add('IdParamsAreInt')		//TODO Make method of Resource Model Class?
		;
		self::$routes_registered[$resource_model_class][] = $route_key;

	}//buildRoutes()

	protected function routeKeyToMethodName($route_key){
		$keyparts = explode('_', $route_key);
		$firstpart = array_shift($keyparts);
		$keyparts = array_map('ucfirst',$keyparts);
		return $firstpart . implode('', $keyparts);
	}//routeKeyToMethodName()

	public function getRoutesRegistered(){
		ksort(self::$routes_registered);
		return self::$routes_registered;
	}//getRoutesRegistered()	

}//class ResourceRoutesBuilder

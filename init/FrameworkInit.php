<?php
namespace glasteel;

class FrameworkInit
{

	protected $root_dir;
	protected $framework_dir;

	const JSONAPI_MEDIA_TYPE = 'application/vnd.api+json';

	public function __construct($root_dir){
		$this->root_dir = $root_dir;

		if( !session_id() ) {
		    @session_start();
		}

		// Load variables from .env
		$this->dotenv();

		$this->framework_dir = __DIR__ . '/..';

		if ( !defined('SITE_NAME') ){
			define('SITE_NAME', 'My Framework Site');
		}

		define('JSONAPI_MEDIA_TYPE',self::JSONAPI_MEDIA_TYPE);
	}//__construct()

	public function getInitialSettings(){
		return [
		    'settings' => [
		        'displayErrorDetails' => ( isset($_ENV['MODE']) && $_ENV['MODE'] === 'local' ) ? true : false,

		        // Renderer settings
		        'renderer' => [
		            'debug' => ( isset($_ENV['MODE']) && $_ENV['MODE'] === 'local' ) ? true : false,
		            'strict_variables' => ( isset($_ENV['MODE']) && $_ENV['MODE'] === 'local' ) ? true : false,
		            'template_path' => $this->root_dir . '/templates/',
		            'cache' => ( isset($_ENV['MODE']) && $_ENV['MODE'] === 'local' ) ? false : $this->root_dir . '/cache/twig/',
		        ],

		        // settings
		        // 'logger' => [
		        //     'name' => 'slim-app',
		        //     'path' => $this->root_dir . '/../logs/app.log',
		        // ],
		    ],
		];
	}//getInitialSettings()

	public function getSlim($settings=false){
		//TODO $settings overrides
		$settings = $this->getInitialSettings();
		$slim = new \Slim\App($settings);
		$container = $slim->getContainer();

		//register the JSON API media type
		//see http://jsonapi.org/format/
		$container->get('request')->registerMediaTypeParser(self::JSONAPI_MEDIA_TYPE, function ($input) {
			return json_decode($input, true);
		});

		// SET UP DEPENDENCIES

		//Replace Slim's response object with our extension, to serve the JSON API media type
		$container['response'] = function($c){
			$headers = new \Slim\Http\Headers(['Content-Type' => 'text/html; charset=UTF-8']);
			$response = new Response(200, $headers);
			return $response->withProtocolVersion($c->get('settings')['httpVersion']);
		};
		
		// Twig view renderer
		$container['renderer'] = function ($c) {
			$settings = $c->get('settings')['renderer'];
			$twig = new \Slim\Views\Twig($settings['template_path'], $settings);

			$twig->addExtension(new \Twig_Extension_Debug());

			// Instantiate and add Slim specific extension
		    $twig->addExtension(new \Slim\Views\TwigExtension(
		        $c['router'],
		        $c['request']->getUri()
		    ));

		    $loader = $twig->getLoader();
		    $loader->addPath( $this->framework_dir . '/templates');

		    return $twig;
		};

		//https://github.com/slimphp/Slim-Flash
		$container['flash'] = function () {
		    return new \Slim\Flash\Messages();
		};

		//RedBeanPHP ORM
		$container['db'] = function ($c) {
			$db = new RedBeanWrapper;
			$db->setup(
				'mysql:host=' . $c->environment['DB_HOST'] . ';dbname=' . $c->environment['DB_NAME'],
				$c->environment['DB_USER'],
				$c->environment['DB_PASS']
			);
			$db->freeze(true);
			return $db;
		};

		//JSON Api Wrapper
		$container['jsonapi'] = function ($c) {
			return new JSONApiController;
		};

		//SSO authenticated user
		$container['auth'] = function ($c) {
			$auth = new AuthUserFromSSO;
			return $auth;
		};

		//ROUTE MIDDLEWARE CLASSES
		$container['catch403'] = function ($c) {
			return new catch403($c->get('forbiddenHandler'));
		};
		$container['catch404'] = function ($c) {
		    return new catch404($c->get('notFoundHandler'));
		};
		$container['IdParamsAreInt'] = function ($c) {
		    return new IdParamsAreInt;
		};
		$container['IdParamsExist'] = function ($c) {
		    return new IdParamsExist($c->get('db'));
		};
		$container['IdParamsJSONAPI'] = function ($c) {
		    return new IdParamsJSONAPI(
		    	$c->get('db'),
		    	$c->get('jsonapi')
		    );
		};

		//Error Handlers
		$container['forbiddenHandler'] = function ($c) {
			return new Forbidden(
		    	$c->get('flash'),
		    	$c->get('settings')['displayErrorDetails']
		    );
		};

		//Route Utility
		$container['ResourceRoutesBuilder'] = function ($c) use ($slim) {
			return new ResourceRoutesBuilder($slim);
		};

		// APPLICATION MIDDLEWARE

		//Set up authorized user, if one exists, via PID from AuthUserFromSSO and User from RedBeanPHP
		//Add is_auth flag and auth_user object to Twig (renderer) vars
		$slim->add(function ($request, $response, $next){
		    $this->renderer->offsetSet('is_auth',false);
		    $pid = $this->auth->getPID();
		    if ( $pid ){
		        $user = new User();
		        if ( $user->setUserByPID($pid) ){
		    		$this->auth->userGS($user);
				    $this->renderer->offsetSet('is_auth',true);
		            //TODO pare down 'auth_user' to most essential keys via User method replacing export()
		            $this->renderer->offsetSet('auth_user', $this->auth->userGS()->export());
		        }else{
		            //TODO redirect to signup
		        }
		    }
			$response = $next($request, $response);
		    return $response;
		});

		//inject db into models
		$slim->add(function ($request, $response, $next){
			ModelBase::setDB($this->db,$response);
		    $response = $next($request, $response);
		    return $response;
		});

		//TODO replace with single "non-200" class
		$slim->add( $container['catch404'] );
		$slim->add( $container['catch403'] );

		//Disconnect DB
		$slim->add(function ($request, $response, $next){
		    $response = $next($request, $response);
			$this->db->close();
		    return $response;
		});

		return $slim;

	}//getSlim()

	protected function dotenv(){
		//load environment configuration values from .env
		//dotenv will throw exceptions on any values missing or invalid
		//https://github.com/vlucas/phpdotenv
		$dotenv = new \Dotenv\Dotenv($this->root_dir);
		$dotenv->load();
		
		$dotenv->required([
			'DB_HOST',
		    'DB_NAME',
		    'DB_USER',
		    'DB_PASS'
		])->notEmpty();
		
		$dotenv->required('MODE')->allowedValues([
			'local',
			'development',
			'production',
		]);

		//if we are running local, spoof the PID value that would be returned from Single Sign-On
		if ( $_ENV['MODE'] === 'local' ){
			$dotenv->required('DEV_PID')->isInteger();
			$_SERVER['pid'] = $_ENV['DEV_PID'];
		}
	}//dotenv()

}//class FrameworkInit
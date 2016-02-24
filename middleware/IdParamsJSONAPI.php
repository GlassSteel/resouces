<?php
namespace glasteel;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class IdParamsJSONAPI extends middlewareBase
{
    protected $db;
    protected $jsonapi;

    public function __construct( RedBeanWrapper $db, JSONApiController $jsonapi){
        $this->db = $db;
        $this->jsonapi = $jsonapi;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->trace($response);

        $route = $request->getAttribute('route');
        $route_args = $route->getArguments();

        $db_tables = $this->db->inspect();
        foreach ( $this->filterAttrs($route_args) as $key => $value) {
            $param_table = str_ireplace('_id', '', $key);
            if ( !in_array($param_table, $db_tables) ){
                continue;
            }
            $resource = $this->jsonapi->getResource($param_table,$value);
            $route->setArgument($param_table . '_jsonapi',$resource);
            $request = $request->withAttribute('route',$route);
        }
        return $next($request, $response);
    }//__invoke
}//class IdParamsJSONAPI
<?php
namespace glasteel;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class IdParamsExist extends middlewareBase
{
    protected $db;

    public function __construct(RedBeanWrapper $db){
        $this->db = $db;
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $this->trace($response);

        $route = $request->getAttribute('route');
        $route_args = $route->getArguments();

        $db_tables = $this->db->inspect();
        foreach ( $this->filterAttrs($route_args)  as $key => $value) {
            $param_table = str_ireplace('_id', '', $key);

            if ( !in_array($param_table, $db_tables) ){
                continue;
            }
            
            $bean = $this->db->load($param_table,$value);
            if ( $bean->id == 0 && $this->okToOverwrite($response,404) ){
                $bean = null;
                $route->setArgument($key,null);
                $response = $response->withStatus(404);
            }
            
            $obj_class = __NAMESPACE__ . '\\' . ucfirst($param_table);
            $obj = new $obj_class($bean);
            $route->setArgument($param_table,$obj);
            $request = $request->withAttribute('route',$route);
        
        }
        return $next($request, $response);
    }//__invoke
}//class IdParamsExist
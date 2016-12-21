<?php 

namespace Krbv\Minifyit\Middleware;

use Closure;

use Illuminate\Http\Request;

use Illuminate\Contracts\Container\Container;

use Krbv\Minifyit\CacheClass as CacheClass;

class MinifyitMiddleware
{

   
    public function handle($request, Closure $next)
    {
    
        try {
            /** @var \Illuminate\Http\Response $response */
            $response = $next($request);
        } catch (Exception $e) {
                    new Exception(
                        'Cannot get $request ' . $e->getMessage(), $e->getCode(), $e
                    );
        } 
        
       // Modify the response to add the JS and CSS
        \App::make('minifyit')->injectHtml($response);

        return $response;
    }


}

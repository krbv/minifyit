<?php

namespace Krbv\Minifyit;


use Illuminate\Support\ServiceProvider;

class MinifyitServiceProvider extends ServiceProvider
{
    

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        
        if(!$this->app->routesAreCached()){
            require __DIR__.'/routes.php';
        }

        $this->registerMiddleware('Krbv\Minifyit\Middleware\MinifyitMiddleware');
        
        /* публикация настроек */
          $this->publishes([
            __DIR__.'/config/minifyit.php' => config_path('minifyit.php'),
            __DIR__.'/Resources/templates/css.html' => resource_path('views/vendor/minifyit/css.html'),  
            __DIR__.'/Resources/templates/js.html' => resource_path('views/vendor/minifyit/js.html'), 
          ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
         __DIR__.'/config/minifyit.php', 'minifyit'
        ); 
        
        $this->app->singleton('minifyit', function ($app) {

                foreach(['css','js'] as $type){
                    $paths[$type] = file_exists(resource_path("views/minifyit/$type.html")) ?
                                resource_path("views/minifyit/$type.html") :
                                __DIR__."/Resources/templates/$type.html";
                }
            
                $debugbar = new \Krbv\Minifyit\MinifyitClass($paths);
 
                return $debugbar;
            }
        );  
        
    }
    
     /**
     * Register the Minifyit Middleware
     *
     * @param  string $middleware
     */
    protected function registerMiddleware($middleware)
    {
        $kernel = $this->app['Illuminate\Contracts\Http\Kernel'];
        $kernel->pushMiddleware($middleware);
        

    }
    
}

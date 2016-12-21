<?php

namespace Krbv\Minifyit\Controllers;

use Illuminate\Routing\Controller;



use Config;


class CreateFileController extends Controller
{  
    
    public function __construct() {

        $this->cache = \App::make('minifyit');

    }
    
    public function css($generatedURL){
        $code = $this->cache->getSource($generatedURL, 'css');
        return response($code, 200)
                  ->header('Content-Type', 'text/css');
    }
    
    public function js($generatedURL){
         $code = $this->cache->getSource($generatedURL, 'js');
         return response($code, 200)
                  ->header('Content-Type', 'application/javascript');
    }
    
    public function __destruct() {
        if(Config::get('CSSReducer.font.active') == true){
            if(Config::get('CSSReducer.font.gzip') == true){
                $this->cache->gzipFonts(Config::get('CSSReducer.font.dir'));
            }
        } 
        
        if(Config::get('CSSReducer.htaccess.copy') == true){
            $this->cache->createHtaccess();
        }
    }
    
}

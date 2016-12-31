<?php

namespace Krbv\Minifyit\Controllers;

use Illuminate\Routing\Controller;



use Config;


class CreateFileController extends Controller
{  
    
    public function __construct() {
       
        $this->minifyit = \App::make('minifyit');

    }
    
    public function css($generatedURL){
        $code = $this->minifyit->getSource($generatedURL, 'css');
        return response($code, 200)
                  ->header('Content-Type', 'text/css');
    }
    
    public function js($generatedURL){
         $code = $this->minifyit->getSource($generatedURL, 'js');
         return response($code, 200)
                  ->header('Content-Type', 'application/javascript');
    }
    
    public function __destruct() {
         
        if(Config::get('minifyit.font.active') == true){
            if(Config::get('minifyit.font.gzip') == true){
                $this->minifyit->gzipFonts(Config::get('minifyit.font.dir'));
            }
        } 
        
        if(Config::get('minifyit.htaccess.copy') == true){
            $this->minifyit->createHtaccess();
        }
    }
    
}

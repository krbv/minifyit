<?php

Route::group(['namespace' => 'Krbv\Minifyit\Controllers'], function () {
    
    //css
    Route::get(Config::get('minifyit.css.folder.cache').'/{fname}.css', ['uses'=>"CreateFileController@css"])
           ->where([
                'fname'=>"[A-Za-z0-9_]+",
            ]);
    
   //js 
   Route::get(Config::get('minifyit.js.folder.cache').'/{fname}.js', ['uses'=>"CreateFileController@js"])
           ->where([
                'fname'=>"[A-Za-z0-9_]+",
            ]); 
   
   
   
});

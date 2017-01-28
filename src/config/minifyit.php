<?php

return [

        'css' => [
                'active'          => true,
                'save'            => false,
                'less'            => true,
                'gzip'            => true,
                'CSScompressor'   => true,
                'delete_previous' => true,
                'folder' => [
                    'cache'         => "/cache/css",
                    'source'         => public_path()."/css",
                    'destination'    => public_path()."/cache/css",
                ]
         ],
    
    
         'js' => [
                'active'          => true,
                'save'            => true,
                'gzip'            => true,
                'JScompressor'    => false, //SLOW send code to http://closure-compiler.appspot.com/home
                'delete_previous' => true,
                'folder' => [
                    'cache'         => "/cache/js",
                    'source'         => public_path()."/js",
                    'destination'    => public_path()."/cache/js",
                ]
         ],   
           
          'pages' => [
                'autosave'        => false,
                'folder' => [
                    'cache'         => "/cache/pages",
                    'destination'    => public_path()."/cache/pages",
                ]
         ], 
    
         "font" =>[
             'active' => true,
             'gzip'   => true,
             'dir' => public_path()."/cache/font"
         ],

    
        'htaccess' => [
            'copy' =>  false,
            'destination' => public_path()."/cache/",
        ],    
    
];
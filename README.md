# Minifyit


## Install

``` bash
composer require krbv/minifyit
```
or
``` php
"require": {
    "krbv/minifyit": "0.1.*"
}
```


config/app.php
``` php
'providers' => [
    Krbv\Minifyit\MinifyitServiceProvider::class
];
```


## Usage

``` php
        $this->minifyit = \App::make('minifyit');
                 
        $this->minifyit->setCSS( 

            ['/css/style.css','/css/form.css'], // will be merged in one new file.

            '/css/single.css'

         );

        $this->minifyit->setJS( \* the same *\ );
```

CSS is going to appear before "</head>"
JS before "</body>"

## For personal settings
``` bash
php artisan vendor:publish
```
Config: /config/minifyit.php
Views:
        /view/vendor/minifyit/css.html
        /view/vendor/minifyit/js.html


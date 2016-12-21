<?php

namespace Krbv\Minifyit;

use Illuminate\Database\Eloquent\Model;

use File;
use Config;
use Exception;
use Less_Parser;
/*
 * 1 step: find file
 * 2 step: open and LESS to CSS
 * 3 step: compile CSS
 * 4 step safe file
 * 5 step: make and safe gzip file
 */

class MinifyitClass
{

    private $output;
    
    private $config;
    
    private $currentFiles;
    
    private $css = [];
    
    private $js = [];
    
    private $templates;

    public function __construct($paths) {
        $this->templates = $paths;
    }
    
    private function addItem($items, &$holder){
        
        if(empty($items)){
           throw new Exception("items are empty");
        }
            
        $add = [];
        for( $i=0; $i<count($items); $i++ ){
             if(is_array($items[$i])){
                  if($add){array_push($holder, $add);}
                  $add = [];
                  array_push($holder, $items[$i]); 
             }else{
                   $add[] = $items[$i];
             }
         }
         if($add){array_push($holder, $add);}
             

    }
    
    public function setCSS($items){
        $this->addItem((array) $items, $this->css);
    }
    
    public function setJS($items){
       $this->addItem((array) $items, $this->js);
    }
    
    public function getCSS(){
        return $this->pathsToHash($this->css, 'css');
    } 
     
    public function getJS(){
        return $this->pathsToHash($this->js, 'js');
    }    
    
    public function cleanCSS(){
        $this->css = [];
    } 
     
    public function cleanJS(){
        $this->js = [];
    }      

    private function getInjectionContent(){
        $ouput = [
            'css' => '',
            'js' => '',
        ];
       //css 
        if(!empty($this->getCSS())){
            
            $template = file_get_contents($this->templates['css']);
            foreach($this->getCSS() as $one){
                $ouput['css'] .= str_replace('{path}', $one, $template);
            }
        }
       //js
       if(!empty($this->getJS())){
            $template = file_get_contents($this->templates['js']);
            foreach($this->getJS() as $one){
                $ouput['js'] .= str_replace('{path}', $one, $template);
            }
        }      
        return $ouput;
    }
    
    public function injectHtml($response){
                
       $renderedContent = $this->getInjectionContent();
        
       if(empty($renderedContent)){return false;}
       
        //get current content of the page
        $content = $response->getContent();
        
        if(!empty($renderedContent['css'])){
            $pos = strpos($content, '</head>');
            if($pos!==false){
                $content = substr_replace($content, $renderedContent['css'], $pos, 0);
            }
        }
        
        if(!empty($renderedContent['js'])){
            $pos = strripos($content, '</body>');
            if($pos!==false){
                $content = substr_replace($content, $renderedContent['js'], $pos, 0);
            }
        }      
        
        // Update the new content and reset the content length
        $response->setContent($content);
        $response->headers->remove('Content-Length');
                    
    }


    
    public function getSource($generatedURL, $type){
 
        if(empty($generatedURL)){throw new Exception("url is empty");}
        
        $this->config = Config::get('minifyit.'.$type);
        
        $sourceFolder = $this->config['folder']['source'];
         
        if(!file_exists($sourceFolder)){throw new Exception("Source Folder does not exists");}
   
        $this->currentFiles = $this->hashToPaths($generatedURL, $sourceFolder);
 //die('213sf');
        //SPECIFIC
        switch($type){
            case "css":
                $this->css();
            break;
            case "js":
                $this->js();
            break;
            default:throw new Exception($type." is not set");
        }

        //save FILE
        if($this->config['save'] == true){
            $destinationFolder = $this->config['folder']['destination'];
            if(!file_exists($destinationFolder)){throw new Exception("Destination Folder does not exists");} 
            $path = $destinationFolder."/".$generatedURL.".$type";
            $this->saveFile($path, $this->config['gzip']);
        } 
        
         //delete previous files
        if($this->config['delete_previous'] == true){   
            $this->deletePrevios();
        }         
       
        return $this->output;
    }

    
    
    public function gzipFonts($fontsDirectory){
        $gzCounter = 0;
        $filesInIt = File::allFiles($fontsDirectory);
        foreach ($filesInIt as $file){
            $lookinForGz = $file.'.gz';
            //if not found file.gz => make it
            if(!file_exists($lookinForGz)){
                //find by pattern
                if (!preg_match('/(woff|woff2)$/', $file)){
                    continue;
                }
                $gzFont = gzencode(File::get($file), 9);                
                File::put($lookinForGz, $gzFont);
                $gzCounter++;
            }
        }
        return $gzCounter;
    }
    
    public function createHtaccess(){
        if(Config::get('minifyit.htaccess.copy')){
            $path = Config::get('minifyit.htaccess.destination').'.htaccess';
            if(!file_exists($path)){
                File::copy(__DIR__."/Resources/htaccess.txt", $path);
            }
        }
    }

/* PRIVATE functions */    
    
    
    public function pathsToHash($items, $type){     
        $result = [];
        $this->config = Config::get('minifyit.'.$type);
        if($this->config['active'] !== true){
            //if off
             foreach($items as $block){
                 if(is_array($block)){
                     $result = array_merge($result, $block);
                 }else{
                     $result[] = $block;
                 }
             }
            return $result;
        }
        
        $publicDir =  public_path();               
        
        foreach($items as $block){
            if(is_array($block)){
                $block = array_map(function($val) use ($publicDir) {return $publicDir.$val;}, $block);
                $hashes = implode("_", array_keys($this->makeHash($block)));
                $result[] = $hashes;
            }else{
                $result[] = array_keys($this->makeHash($publicDir.$block))[0];
            }
        }

        return array_map(function($val) use ($type){
            return Config::get('minifyit.'.$type.'.folder.cache')."/$val.$type";
        }, $result);
    }
    
    private function js(){
        
        $sources = array_map('file_get_contents', $this->currentFiles); 
        
        foreach($sources as $code){
            if($this->config['JScompressor'] == true){
                 $code = $this->compressJS($code);
             }
             $this->output .=$code;
        }
    }
    
    private function css(){
        
         //get source of all files
         $this->output = implode('',  array_map('file_get_contents', $this->currentFiles));       
         //less
         if($this->config['less']==true){$this->lessToCss();}
         //compress CSS
         if($this->config['CSScompressor']==true){$this->compressCSS();}     
         
    }

     private function deletePrevios(){
        
      
        $allowToDelete = ['css','js','gz',];
        
        $original = File::allFiles($this->config['folder']['source']);
        $maked = File::allFiles($this->config['folder']['destination']);
        
        $existsHashes  = $this->makeHash($original);

        
        foreach($maked as $path){
            //отделяем от расширения и переводим в массив
            $hsString = explode('.', $path->getFilename())[0];
            $currentHashes = explode('_', $hsString);

            //сравниваем со всеми файлами в базе
            foreach($currentHashes as $single ){
                if(empty($existsHashes[$single])){
                    if(in_array($path->getExtension(), $allowToDelete)){
                        unlink($path);
                    }
                    break;
                }
            }
           
        }
        
    }
    
    private function saveFile($path, $gz = true){
        
             if(file_exists($path)){
                 throw new Exception("CSS file $path is exist");
             }
            if(File::put($path, $this->output)===false){
                throw new Exception("Can't write: $path");
            } 
            //make GZIP 
            if($gz==true){
                $gzdata = gzencode($this->output, 9);
                if(File::put($path.".gz", $gzdata)===false){
                    throw new Exception("Can't write: $path.".gz);
                }                 
            }            
            return true;
    }
    
    private function lessToCss(){
        $lessParser = new Less_Parser();
        $lessParser->parse( $this->output );
        $this->output = $lessParser->getCss(); 
        return true;
    }   
        
    private function hashToPaths($hashString, $dir){

        $queryHashes = explode("_", $hashString);
        $allFiles = File::allFiles($dir);
        
        //get hashes for files
        $allFilesHashes = $this->makeHash($allFiles);
        
        //looking for path for hash            
        foreach($queryHashes as $hash){
            if(empty($allFilesHashes[$hash])){
                abort(404);
                return false;
            }
            $output[] = $allFilesHashes[$hash];
        }        
        return $output;
    }
    
    private function compressCSS() {
            $text = $this->output;
            /* удалить табуляции, пробелы, символы новой строки */
            $text = str_replace(["\r", "\n", "\t"], '', $text);
            /* удалить комментарии */
            $text = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $text);
            /* удалить двойные пробелы */
            $text = preg_replace('/[\s]{2,}/', ' ', $text);
            //массив замен
            $replace = [
                    chr( 194 ) => ' ',
                    chr( 160 ) => ' ',                
                    '; ' => ';',
                    ' ;' => ';',
                    '} ' => '}',
                    ' }' => '}',
                    '{ ' => '{',
                    ' {' => '{',               
                    ': ' => ':',
                    ' :' => ':',
                    ', ' => ',',
                    ' ,' => ',',
                    '> ' => '>',
                    ' >' => '>',  
                
                    ' 0px' => '0',
                    ' 0em' => '0',
                    ' 0%'  => '0',
                
                    ";}" => '}',
                
                    ' !important' => '!important'
                
                ];
            //instead writing 0.5em you can use .5em, 0.5px is equal to .5px
             $text = preg_replace('/0\.(\d)/','.$1',$text);
             //#ffccaa => #fca

             $text = preg_replace_callback('/#((.)\2){3}([)|;|\}])/i', function($matches){ 
                  $final = '';
                  $triplets = str_split(substr($matches[0],1,-1), 2);
                  // Go over each triplet separately
                  foreach ($triplets as $t){
                    // Get the decimal equivalent of triplet
                    $dec = base_convert($t, 16, 10);
                    // Find the remainder
                    $remainder = $dec % 17;
                    // Go to the nearest decimal that will yield a double nibble
                    $new = ($dec%17 > 7) ? 17+($dec-$remainder) : $dec-$remainder;
                    // Convert decimal into HEX
                    $hex = base_convert($new, 10, 16);
                    // Add one of the two identical nibbles
                    $final .= $hex[0];
                  }              

                 return "#$final".$matches[3];
            }, $text);

        
            $this->output = str_replace(array_keys($replace), array_values($replace), $text);
            return true;
     }
     
     
     private function compressJS($code) {  

                $params = array('http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query(                                  [
                                          'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
                                          'output_format' => 'text',
                                          'output_info' => 'compiled_code',
                                          'js_code' => $code,
                                        ])
                    )
                );
                $gottenText = file_get_contents("http://closure-compiler.appspot.com/compile", false, stream_context_create($params));

               if(!empty($gottenText) && strlen($gottenText)<strlen($code)){
                    $code  = $gottenText;
               } 
           
             return $code;
    }
    
  /*
     * array
     * [hash] => path
     */
   private function makeHash($inpFiles){

          foreach((array)$inpFiles as $filePath){
             if(!file_exists($filePath)){ 
                   throw new Exception("$filePath is not found");
             } 
             
             //windows bag fix (str_replace)
             $filePath = str_replace("\\","/", (string)$filePath);  
             
             $hash = hash("crc32", serialize($this->config).$filePath.filemtime($filePath));
             $output[$hash] = $filePath;
          }
           
         return $output;         
   }    
    
    
    
 
    

}

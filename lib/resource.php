<?php
class Resource {
    protected $rawData;
    protected $parsedData       = array();
      
    protected $parameters       = array();  
    protected $config           = array();

    protected $name;
    protected $type;
    protected $url;
    protected $options          = array();

    protected $optional         = array();
      
    public function __construct($config) {
        $this->config   = json_decode($config);

        $this->name     = $this->config->name;
        $this->type     = $this->config->type;
        $this->url      = $this->config->url;
    }

    public function setOptions() {
        $this->parameters = func_get_args();
        
        foreach($this->config->required as $option) {
            $this->options[$option] = array_shift($this->parameters);
        }

        if(isset($this->optional)) {
            foreach($this->optional as $option=>$defaultValue) {
                $value = array_shift($this->parameters);
                $this->options[$option] =  $value ?
                    $value : $defaultValue;
            }
        }

        $this->fetchRawData();
        $this->parseData();
    }
       
    protected function fetchRawData() {
        $this->curlRequest();
    }
        
    protected function parseData() { }
    
    // allow force
    public function getParsedData() {
        if(!isset($rawData)) {
            $this->fetchRawData();
        }
        if(empty($parsedData)) {
            $this->parseData();
        }

        return $this->validateParsedData();
    }

    protected function validateParsedData() { }
        
    protected function parsedUrl() {
        $url = $this->url;
            
        foreach($this->options as $option=>$value) {
            $url = preg_replace("/\{$option\}/","$value",$url);
        }
            
        return $url;
    }

    protected function curlRequest() {
        $this->curl = curl_init();
        
        curl_setopt($this->curl, CURLOPT_URL, $this->parsedUrl());
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        
        $this->rawData = curl_exec($this->curl);
        
        curl_close($this->curl);
    }

    protected function urlify($string) {
        $pattern ="{\\b((https?|telnet|gopher|file|wais|ftp) : [\\w/\\#~:.?+=&%@!\\-]+?) (?= [.:?\\-]* (?:[^\\w/\\#~:.?+=&%@!\\-] |$) ) }x"; 
        
        return preg_replace($pattern,"<a href=\"$1\">$1</a>", $string); 
    }
        
    public function debugInformation() {
        echo '<hr/>';
        echo '<h1 style="margin:0">Debug Information</h1><hr/>';
        echo '<h2 style="margin:0">Name</h2>';
        echo $this->name . '<hr/>';
        echo '<h2 style="margin:0">Type</h2>';
        echo $this->type . '<hr/>';
        echo '<h2 style="margin:0">URL</h2>';
        echo $this->url . '<br/>';
        echo $this->parsedUrl() . '<hr/>';
        echo '<h2 style="margin:0">Options</h2>';
        foreach($this->options as $option=>$value) {
            echo $option . ': ' . $value . '<br/>';
        }
        echo '<hr/>';
        echo '<h2 style="margin:0">Raw Data</h2><pre>';
        print_r($this->rawData);
        echo '</pre><hr/>';
        echo '<h2 style="margin:0">Parsed Data Data</h2><pre>';
        print_r($this->getParsedData());
        echo '</pre><hr/>';

    }
}
?>
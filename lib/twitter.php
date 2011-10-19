<?php
class Twitter extends Resource {
    public function __construct($config) {
        $this->parameters = func_get_args();
          
        $this->optional = array(
            'count'                 => 1,
            'include_entities'      => 'true',
            'include_rts'           => 'true',
        );

        parent::__construct($config);
    }

	protected function parseData() {
		$jsonData = json_decode($this->rawData);

	    $this->parsedData = array();
	    foreach($jsonData as $tweet) {
	        $this->parsedData[] = array(
	            'tweet'     => $this->urlify($tweet->text),
	            'time'      => strtotime($tweet->created_at),
	        );
	    }

	    if(empty($this->parsedData)) {
	        $this->parsedData[] = array(
	            'tweet'     => 'The latest tweet is not available.',
	            'time'      => 0,
	        );
	    }
	}

    protected function validateParsedData() {
    	if(!is_array($this->parsedData)) {
    		return false;
    	}
    	foreach($this->parsedData as $tweet) {
    		if(!array_key_exists('tweet',$tweet) || empty($tweet['tweet'])) {
    			return false;
    		}
    		if(!array_key_exists('time',$tweet) || !is_int($tweet['time'])) {
    			return false;
    		}
    	}
        
        return $this->parsedData;
    }
}
?>
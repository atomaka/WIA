<?php
class Sc2Ranks extends Resource {
	protected function parseData() {
		$jsonData = json_decode($this->rawData);

	    $this->parsedData = array();
	    
        foreach($jsonData->teams as $team) {
            $this->parsedData[$team->bracket][] = array(
                'league'        => $team->league,
                'division'      => $team->division,
                'rank'          => $team->division_rank,
                'points'        => $team->points,
                'wins'          => $team->wins,
            );
        }
	}


    protected function validateParsedData() {    
        if(!is_array($this->parsedData)) {
            return false;
        }

        foreach($this->parsedData as $bracket) {
            if(!is_array($bracket)) {
                return false;
            }

            foreach($bracket as $team) {
                if(!array_key_exists('league',$team) || empty($team['league'])) {
                    return false;
                }
                if(!array_key_exists('division',$team) || empty($team['division'])) {
                    return false;
                }
                if(!array_key_exists('rank',$team) || !is_int($team['rank'])) {
                    return false;
                }
                if(!array_key_exists('points',$team) || !is_int($team['points'])) {
                    return false;
                }
                if(!array_key_exists('wins',$team) || !is_int($team['wins'])) {
                    return false;
                }
            }
        }
      
        return $this->parsedData;
    }
}
?>
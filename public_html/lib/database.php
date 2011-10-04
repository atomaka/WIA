<?php
class database extends mysqli
{
	public function __construct($host,$username,$password,$database)
	{
		parent::init();
		
		if(!parent::options(MYSQLI_INIT_COMMAND,'SET AUTOCOMMIT = 0'))
			die('Setting MYSQLI_INIT_COMMAND failed');
		
		if(!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT,5))
			die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
			
		if(!parent::real_connect($host,$username,$password,$database))
			die('Connect Error (' . mysqli_connect_errono() . ') ' . mysqli_connect_error());
	}
}
?>
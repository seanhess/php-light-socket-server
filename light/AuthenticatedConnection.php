<?  
	require_once("light/Connection.php");

	class AuthenticatedConnection extends Connection
	{       
		var $authenticated = false;
		var $rid;	// random id
		var $target; // the connection you are a proxy too
		
		public function AuthenticatedConnection($socket)
		{
			parent::__construct($socket);
			$this->rid = rand();      
		}     
		
		public function proxy($connection)
		{
			$this->target = $connection;
			$this->channel = $this->target->channel;
		}
	}
?>
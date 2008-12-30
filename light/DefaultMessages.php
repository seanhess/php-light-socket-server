<?           
	require_once("light/Message.php");

	class DefaultMessages
	{
		var $master;
		
		public function DefaultMessages($master)
		{                   
			$this->master = $master;
		}                        
		
		public function messageFactory()
		{                      
			$message = new Message();
			$message->from = $this->master;
			return $message;
		}
	}

?>
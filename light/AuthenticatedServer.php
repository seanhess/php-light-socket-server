<?                                          
	require_once("light/Server.php");  
	require_once("light/AuthenticatedConnection.php");

	class AuthenticatedServer extends Server
	{                             
		const SHARED_KEY = "b17f0b7b73af6d58a698e8f6f2cee879";
		
		var $authenticationFreeMessages; 
		var $proxies;   
		 
		function AuthenticatedServer($ip, $port)
		{              
			parent::__construct($ip, $port);   
			
			$this->authenticationFreeMessages = new stdClass;
			$this->proxies = array();  
			$this->passMessage("authenticate"); 	// we want to allow people to try to grant passes
		}
		
		// This does not authenticate them. For that, they need to send the correct shared key as parameter [3]
		protected function proxy($proxyid, &$connection)
		{              
			if (empty($this->proxies[$proxyid]))
			{
				$this->error('Invalid Proxy ID '.$proxyid); 
				$this->remove($connection->socket);
				return false;
			}                                                          
			
			$target = $this->proxies[$proxyid];  

			$this->log('Proxy Accepted'); 
			$connection->proxy($target);
			return true;
		}
		              
		// Call this function to allow certain messages to bypass authentication checking
		protected function passMessage($type)
		{             
			$this->log("Passing Message $type");
			$this->authenticationFreeMessages->$type = true;
		}    
		
		protected function authenticate($connection)
		{             
			$connection->authenticated = true;
			$this->proxies[$connection->rid] = $connection;
			$this->send($connection, $this->createMessage('authenticated', array('hash' => $connection->rid)));
		}    
		         
		
		// any time the message is sent with a proxy, change the from to be from somebody else!
		protected function verifyMessage($connection, $message)
		{                    
			if (isset($message->data->proxy))
			{      
				if (!$this->proxy($message->data->proxy, $connection))   
					return false;
			}
			
			$type = strval($message->type);  
			if (($connection->authenticated !== true) && ($this->authenticationFreeMessages->$type !== true))
			{                 
				// unless it is an authentication message 
				$this->log("Not Authenticated ".$connection);
				return false;
			}
			
			return true;
		}       
		
		protected function onAuthenticate($message)
		{                    
			// just checks for it in the message itself
			if (isset($message->data->authKey) && $message->data->authKey == self::SHARED_KEY)
			{
				$this->log("Shared key accepted");
				$message->from->authenticated = true;
			}   
			else
			{
				$this->error("Incorrect key");   
				$message->from->authenticated = false;
			}
		}

		protected function connectionFactory($socket)
		{
			return new AuthenticatedConnection($socket);
		}             
		 
		
		
		
		protected function handleMessage($message)
		{
			switch ($message->type)
			{            
				case 'authenticate':    $this->onAuthenticate($message); break; 
				default:				parent::handleMessage($message); break;
			}
		}



		
		
		
		// if (isset($params[3]))
		// {
		// 	if ($params[3] == self::SHARED_KEY)   
		// 	{
			// 		$this->log("Shared key accepted");
			// 		$connection->authenticated = true;
		// 	}
		// 	else
		// 		$this->log("Shared key denied");
		// }
		// 
		// if (isset($params[4]))
		// {      
		// 	if (!$this->proxy($params[4], $connection))   
		// 		return;
		// }
	}

?>
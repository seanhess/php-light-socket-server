<?

require_once("light/Connection.php");
require_once("light/Message.php"); // defines constants
require_once("light/ChannelManager.php");   
require_once("light/DefaultMessages.php");   
require_once("light/EventDispatcher.php");   

class Server extends EventDispatcher
{
	var $ip;
	var $port;       
	
	const NULL_BYTE = "\0";  
	const DEFAULT_CHANNEL = "default_channel";
	           
	// The server's connection, this is used to receive connections
	public $master;
	public $sockets;   
	public $connections; 
	public $channels;  
	public $defaultMessages;
	                                                
	public $clientport;
	public $clientip;      
	
	function Server($ip, $port)
	{                 
		$this->log("Started Server on $ip:$port");
	    
		$this->ip = $ip;
		$this->port = $port;    
	}    
	
	public function start()
	{
	    $this->initSocket();

		$this->connections = array();   
		$this->channels = new ChannelManager();  
		$this->createDefaultMessages();

	    $this->listenToSockets();   // don't put anything after this!
	}
	
	public function log($message, $newline=false)
	{               
        // $message = substr($message, 0, 100);
	        
	    if ($newline)
	        echo "\n";
	        
        echo "[SocketServer] $message \n";
	}   
	
	public function error($message)
	{
	    echo "[SocketServer Error] $message \n";
	}
	
	public function send($connections, $message)
	{                           
		if (empty($message))
		{
			$this->error("Message not defined");
			return;
		}
		
		if ($connections instanceof Channel)
	        $connections = $connections->members();
		
		if(!is_array($connections))
			$connections = array($connections);

		foreach($connections as $connection)
		{
			if($connection === NULL)
				continue; 

			$raw = $message->encode();         
			$this->sendString($connection, $raw);
		}
	}       
	
	protected function sendString($connection, $raw)
	{
		$this->log("(  )>> ".str_pad($connection->id, 3)." - $raw");
		$bytes = socket_write($connection->socket, $raw.self::NULL_BYTE);

		if ($bytes === false)
			$this->error(socket_strerror(socket_last_error()));
	}
	
	public function add($socket)
	{
		$connection = $this->connectionFactory($socket);
		
		$this->sockets[$connection->id] = $socket;  
		$this->connections[$connection->id] = $connection;
		$this->log("OOOOO  ".$connection->id, true);
		$this->joinDefaultChannel($connection);
		$this->sendConnectConfirmation($connection);     
	}    
	
	public function sendConnectConfirmation($connection)
	{
		$this->send($connection, $this->createMessage("connected"));
	}            
	
	public function joinDefaultChannel($connection)
	{
		$this->channels->join($connection, self::DEFAULT_CHANNEL);
	}   
	
	protected function close($socket)
	{          
		socket_close($socket);                   
	}     
	
	protected function clean($connection)
	{
        $this->channels->leave($connection); // leave the current channel
	}      
	
	protected function removeFromLists($connection)
	{
		unset($this->connections[$connection->id]); // remove from connections list
	    unset($this->sockets[$connection->id]); // remove from sockets list
	}
	
	protected function remove($socket)
	{               
		$this->close($socket);
		               
		$connection = $this->connection($socket);
		$connection->connected = false;     
		
		$this->fire("removing", $connection);
		         
		$this->clean($connection);
		$this->removeFromLists($connection);
		$this->log("~~~~~  ".$connection->id); 
		
		$this->fire("removed", $connection);
	}      
	
	protected function connection($socket)
	{                      
	    $index = Connection::index($socket);
	    return $this->connection_from_index($index);
	}
	
	protected function connection_from_index($index)
	{   
		$index = intval($index);                      
		
	    if ($index > 0 && isset($this->connections[$index]))
	        return $this->connections[$index];
        else
            return null;
	}   
	
	protected function connection_from_string($index)
	{                            
		if ($index instanceof Connection)
			return $index;   
			
	    else if ($index == Message::ALL)
			return null; // means everybody :)
			
		else if ($index == Message::SERVER)
			return $this->master;
			
		else
			return $this->connection_from_index($index);
	}
	
	protected function initSocket()
	{           
		// Begin
		if (($master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) 
			$this->error("socket_create() failed, reason: " . socket_strerror($this->master));
		 
		// Allows the port to be opened again even if we control-c
		socket_set_option($master, SOL_SOCKET,SO_REUSEADDR, 1);
						                                          
		// Bind the socket // 
		if (($ret = socket_bind($master, $this->ip, $this->port)) < 0)
			$this->error("socket_bind() failed, reason: " . socket_strerror($ret));

		// listen doesn't actually listen.  See socket_accept in listenToSockets
		if (($ret = socket_listen($master, 5)) < 0)
			$this->error("socket_listen() failed, reason: " . socket_strerror($ret));	
			                   
        $this->master = $this->connectionFactory($master);
        $this->sockets = array($this->master->id => $master);  

        // don't add to the connections array, because it isn't a connection // 
	}   
	                             

	
	protected function listenToSockets()
	{
		//---- Create Persistent Loop to continuously handle incoming socket ees ---------------------
		while (true) {
			
			$changed_sockets = $this->sockets;
			$num_changed_sockets = socket_select($changed_sockets, $write = NULL, $except = NULL, NULL);
			foreach($changed_sockets as $socket) 
				$this->onSocketUpdated($socket);
		}
	}
	
	protected function onSocketUpdated($socket)
	{                   
		$this->checkForNewConnections($socket);
		$this->readDataFromSocket($socket);
	}
	
	protected function checkForNewConnections($socket)
	{                
		if ($socket == $this->master->socket) 
		{
			if (($client = socket_accept($this->master->socket)) < 0) 
			{
				$this->error("socket_accept() failed: reason: " . socket_strerror($socket));
				continue;
			} 
			else 
			    $this->add($client);
		}
	}
	
	protected function readDataFromSocket($socket)
	{
		if ($socket != $this->master->socket) 
		{
			list($bytes, $buffer) = $this->read($socket);
			$this->checkForGoners($socket, $bytes);
			
			if ($bytes > 0)
				$this->handleData($socket, $buffer);
		}
	}
	
	protected function read($socket)
	{
		$bytes = socket_recv($socket, $buffer, 4096, 0);
		return array($bytes, $buffer);
	}
	
	protected function checkForGoners($socket, $bytes)
	{
		// The client has disconnected 
		if ($bytes == 0) 
		    $this->remove($socket);
	}
	
	protected function handleData($socket, $buffer)
	{                         
	    $messages = explode(self::NULL_BYTE, rtrim($buffer));
	                                     
	    foreach ($messages as $message)
            $this->parseRequest($this->connection($socket), $message);
	}   
	
	function parseRequest($connection, $origdata)
	{                 
		$skip = $this->respondToPing($connection, $origdata); 
		
		$this->checkForHTTPTraffic($connection, $origdata);
        
		if (empty($connection) || $connection->connected == false)
		{
			$this->log("not connected, ignoring message");
			return;         
		}         
		
		if ($skip)
			return;
			
		$this->log("(<<) - ".str_pad($connection->id, 3)." - $origdata", true);
			
		$message = $this->parseIncomingMessage($connection, $origdata);	
		
		if (empty($message))
		{
			$this->log("invalid message format");
			return;         
		}  
			
		if (!$this->verifyMessage($connection, $message))
		{
			$this->log("message not verified");
			return;         
		}
                      
		$this->handleMessage($message);
		return $message;   
	}
	        
	
	protected function respondToPing($connection, $origdata)
	{
		if ($origdata == "ping")                            
		{               
			$this->log("(<<)       - ping");           
			$this->sendString($connection, "pong");
			return true;
		}
	}
	
	protected function checkForHTTPTraffic($connection, $origdata)
	{
		if (preg_match(':(GET|POST).*HTTP:i', $origdata))
	    {                 
	        $this->log("Removing HTTP Client");
	        $this->remove($connection->socket);
	    }                                           
	} 
	
	protected function verifyMessage($connection, $message)
	{
	   	// you can override this if you want
		return true;
	}      
	
	protected function handleMessage($message)
	{
		$sender = $message->from;
		
		if (isset($sender) && isset($sender->channel))
			$this->send($sender->channel, $message);
	}
	    
	
	
	
	    
	// Message stuff!
	// you can override this to completely change the message
	public function createMessage($type, $data = array('nodata'), $from = Message::SERVER, $to = Message::ALL )
	{                           
		$message = $this->messageFactory();
		$message->type = $type;
		$message->data = $data;
		$message->to = $to;
		$message->from = $this->connection_from_string($from);
		
		return $message;
	}     
	
	public function parseIncomingMessage($connection, $origdata)
	{
		$message = $this->messageFactory();       
		$parsed = $message->decode($origdata); 
		                    
		if ($parsed)            
		{
			$message->from = $connection;
			$message->to = $this->connection_from_string($message->to);
		}
		
		return ($parsed) ? $message : null;
	}        
	
	protected function createDefaultMessages()
	{
		$this->defaultMessages = new DefaultMessages($this->master);
	}
	
	protected function messageFactory()
	{
		return new Message();
	}
	   
	protected function connectionFactory($socket)
	{
		return new Connection($socket);
	}  
	
} 
?>
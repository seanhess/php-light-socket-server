<?    

require_once("light/Channel.php");

class ChannelManager
{        
	var $channels;
 
    public function ChannelManager()
    {                   
		$this->channels = array();
    }                              

	public function getChannel($name)
	{                     
	    if (empty($this->channels[$name]))
	        $this->channels[$name] = new Channel($name);
    
        return $this->channels[$name];
	}    
	
	// join and leave channels // The server does not specify a format for this, do it with an extension
	public function join($connection, $name)
	{   
	    $channel = $this->getChannel($name);                              
	    $connection->join($channel);
	}
	
	// leave your current channel //
	public function leave($connection)
	{                     
	    $connection->leave();
	}
}               

?>
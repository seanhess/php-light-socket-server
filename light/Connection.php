<?

class Connection
{           
    public $id; // id# from socket resource
    public $channel;  // channel object
    public $socket; // socket resource  
    public $connected; 
    
    public function Connection($socket)
    {
        $this->socket = $socket;
        $this->id = $this->index($socket);
        $this->connected = true;  
    }  
    
    public function index($socket)
    {
        return preg_replace('/^Resource id #(\d+)$/i',"$1", ''.$socket);
    }    
    
    public function leave()
    {
        if (isset($this->channel))
            if ($this->channel instanceof Channel)
                $this->channel->leave($this);
    }                    
    
    public function join($channel)
    {
        $this->channel = $channel;
	    $channel->join($this);
    } 

    public function getOtherChannelMembers()
	{
		return $this->channel->members($this);
	}
    
    public function __toString()
    {
        return $this->id;
    }
    
    
}

?>

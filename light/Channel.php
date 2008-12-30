<?
class Channel
{         
    public $name;
    private $connections;  
    private $data;
                     
    public function Channel($name)
    {                   
        $this->name = $name;
        $this->connections = array(); 
        $this->data = array();
    }
    
    public function join($connection)
    {
        $this->connections[$connection->id] = $connection;
    }   
    
    public function leave($connection)
    {         
        if (isset($this->connections[$connection->id]))
            unset($this->connections[$connection->id]);
    }   
    
    public function members($exclude=null)
    {       
        $connections = $this->connections;                             
        
        if (isset($exclude))
		{                
			$id = $exclude->id;
			
			if (isset($exclude->proxy))
				$id = $exclude->proxy->id;
			
            unset($connections[$id]);
		}
        
        return $connections;
    }
    
    public function length()
    {
        return count($this->connections);
    } 
    
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }                               
    
    public function __get($name)
    {
        return $this->data[$name];
    }
}
?>
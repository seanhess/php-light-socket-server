<?
    class EventDispatcher
    {           
        private $events = array();
        
        /*public function __set($name, $callback)
        {                                                    
            $this->addToEvents($name, $callback);
        }*/    
        
        public function listen($name, $callback)
        {
            $this->addToEvents($name, $callback);
        }
        
        private function addToEvents($name, $callback)
        {
            if (empty($this->events[$name]))
                $this->events[$name] = array();
                
            $this->events[$name][] = $callback;
        }
                                  
        // This can return multiple parameters.. Just sent it an array of things to pass back
        public function fire($name, $data=null)
        {           
            if (empty($this->events[$name]))
                return;
                                 
            if (empty($data))
                $data = array();         
                
            if (!is_array($data)) 
                $data = array($data);

            foreach ($this->events[$name] as $callback)
                call_user_func_array($callback, $data);
            
            return false; // so you can use this to return early for errors
        }
    }    
?>
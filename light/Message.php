<?
	require_once("light/StringEncoder.php");
	
	class Message
	{              
		const SERVER = 'SERVER';
		const ALL = 'ALL';   
		
		public static $encoder;
		
		var $type = 'notype';
		var $data = array('nodata');       
		var $to = self::ALL;
		var $from = self::SERVER; 
		var $xml; 
		
		public function Message()
		{
			if (empty(self::$encoder))
			 	self::$encoder = new StringEncoder();
		}  
		
		public function encode()
		{
			return "<message to=\"$this->to\" from=\"$this->from\" type=\"$this->type\"><data><![CDATA[".$this->encodeData()."]]></data></message>";
		}   
		
		public function decode($raw)
		{               
			if (substr($raw, 0, 8) != "<message")
				return false;

			try {
				$xml = new SimpleXMLElement($raw);      
			}
			catch (Exception $e)
			{
				return false;
			}
			
			if (empty($xml))
				return false;
				
		    $this->xml = $xml;
			
			$this->type = $xml['type'];
			$this->to = $xml['to'];
			$this->from = $xml['from'];
			$this->data = $this->decodeData($xml->data);    
			
			if (empty($this->type) || $this->type == '')
				return false;
			
			return true;
		}   
		
		public function duplicate()
		{
			$message = new Message();
			$message->type = $this->type;
			$message->to = $this->to;
			$message->data = $this->data;
			$message->from = $this->from;   
			
			return $message;
		}
		
		public function encodeData()
		{
			return self::$encoder->encode($this->data);
		}   
		
		public function decodeData($data)
		{                      
			return self::$encoder->decode($data);
		}
	}
?>
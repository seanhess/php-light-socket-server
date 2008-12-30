<?

require_once("light/Server.php");
                     
// This requires a file: crossdomain.xml in the same folder as the launching script!
class PolicyServer extends Server
{                       
	const CROSS_DOMAIN_PATH = "crossdomain.xml";
	const NULL_BYTE = "\0";
	const INCOMING_REQUEST = "<policy-file-request/>";
	
	function PolicyServer($ip)
	{                 
		parent::__construct($ip, 843);
	}    
	
	protected function sendPolicyFile($socket)
	{             
	    $policy = file_get_contents(self::CROSS_DOMAIN_PATH).self::NULL_BYTE;                                           
		$success = socket_write($socket, $policy, strlen($policy)); 
		$this->log("Wrote Policy File: ".$policy);   
	}
	
	public function sendConnectConfirmation($connection)
	{
		// do nothing
	}
	
	protected function handleData($socket, $buffer)
	{
		if(substr($buffer, 0, 22) == self::INCOMING_REQUEST)
			$this->sendPolicyFile($socket);
		else
			$this->error("Non policy request :: $buffer");
	}
} 
?>
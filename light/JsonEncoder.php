<?                
	require_once("light/JSON.php");

	class JsonEncoder
	{
		public function encode($data)
		{
			return json_encode($data);
		}         
		
		public function decode($data)
		{
			return json_decode($data);
		}
	}   
	
?>
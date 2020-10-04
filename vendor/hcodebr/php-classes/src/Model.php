<?php
	
namespace TH;

class Model{
	private $values = [];

	public function __call($name, $args){

		$method = substr($name, 0, 3);
		$fieldname = substr($name, 3, strlen($name));

		switch ($method) {
			case "get":
				return (isset($this->values[$fieldname])) ? $this->values[$fieldname] : NULL;	//Nessa linha de codigo, foi feito esse isset para poder verificar se o array foi definido ou não, se ele foi definido, irá retornar ele mesmo, se não, irá retornar NULL 
				break;

			case "set":
				$this->values[$fieldname] = $args[0];
				break;
		}

	}
	public function setData($data = array()){

		foreach ($data as $key => $value) {
			$this->{"set".$key}($value);
		}

	}
	public function getValues(){
		return $this->values;
	}
}


?>
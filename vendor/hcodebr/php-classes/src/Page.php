<?php

Namespace TH;

use Rain\Tpl;

class Page{
	private $tpl;
	private $options = [];
	private $defaults = [
		"header"=>true,
		"footer"=>true,
		"data"=>[]
	];

	public function __construct($opts = array(), $tpl_dir = "/views/")   //Foi adicionado essa variável $tpl_dir por parametro para receber o diretorio da pasta views para que não ahaja conflito com o diretorio da classe PageAdmin 
	{
		$this->options = array_merge($this->defaults, $opts);
		$config = array(
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,      //Aqui, essa variável $tpl_dir concatenada é so o valor do diretorio que ela recebeu la em cima
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
			"debug"         => false
			);

	Tpl::configure( $config );
	
	$this->tpl = new Tpl();
	$this->setData($this->options["data"]);	
	if($this->options["header"] === true)$this->tpl->draw("header");
	}
	private function setData($data = array())
	{
		foreach ($data as $key => $value){
			$this->tpl->assign($key,$value);
		}
	}
	public function setTpl($name, $data = array(), $returnHTML = false)
	{
		$this->setData($data);
		return $this->tpl->draw($name, $returnHTML);
	}
	public function __destruct()
	{
		if($this->options["footer"] === true)$this->tpl->draw("footer");
	}
}
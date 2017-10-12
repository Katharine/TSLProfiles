<?php
class Flickr
{
	private $login;
	
	
	public function __construct(Login $login)
	{
		$this->login = $login;
	}
}
?>
<?php
class Gallery
{
	private $link;
	private $user;
	
	public function __construct(MySQL $link, Profile $user)
	{
		$this->link = $link;
		$this->user = $user;
	}
	
	public function getimages($start = 0, $limit = 100)
	{
		$this->link->query("
			SELECT `id`
			FROM `images`
			WHERE `owner` = '".$this->user->getid()."'
			ORDER BY `uploaded` DESC
			LIMIT ".(int)$start.", ".($limit?(int)$limit:'9999999999999999')
		);
		$results = array();
		while($row = $this->link->fetchrow())
		{
			$results[] = $row['id'];
		}
		foreach($results as &$result)
		{
			$result = new GalleryImage($this->link, $result);
		}
		return $results;
	}
}
?>
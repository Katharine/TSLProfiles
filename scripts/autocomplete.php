<?php
$query = urldecode(stripslashes($_POST['q']));
$data = unserialize(file_get_contents("http://api.tslprofiles.com/serialize/FindPeople?search=".urlencode($query)."&limit=10"));
print '<ul>';
if($data['success'])
{
	foreach($data['response']['names'] as $name)
	{
		print "<li>".htmlentities($name['name'])."</li>";
	}
}
print '</ul>';
?>
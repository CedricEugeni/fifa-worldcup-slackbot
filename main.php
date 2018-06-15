<?php

$TIME_ALERT = 5;

$data = json_decode(file_get_contents("https://api.fifa.com/api/v1/calendar/matches?idCompetition=17&idSeason=254645&idStage=275073&language=fr-FR&count=500"));

$current_date = (new DateTime())->getTimestamp();
$dbFile = "WC2018DB.json";
$dbPath = __DIR__ . "/" . $dbFile;

if (!file_exists($dbPath))
{
	$dbMatches = [];
}
else
{
	$dbMatches = json_decode(file_get_contents($dbPath));
}

$messages = [];
foreach ($data->Results as $match)
{
	$date = (new DateTime($match->Date))->getTimestamp();
	$idMatch = $match->IdMatch;
	
	if (!in_array($idMatch, $dbMatches) && ($date - $current_date) > 0 && ($date - $current_date) <= (60 * $TIME_ALERT))
	{
		$team1 = $match->Home->TeamName[0]->Description;
		$team2 = $match->Away->TeamName[0]->Description;
		if ($match->StageName[0]->Description == "Phase de groupes")
		{
			$group_name = $match->GroupName[0]->Description;
			$messages[] = "le match du $group_name : $team1 - $team2 commence.";
		}

		$dbMatches[] = $idMatch;
	}
}

$fp = fopen($dbPath, "w+");
fwrite($fp, json_encode($dbMatches)); 
fclose($fp);

?>
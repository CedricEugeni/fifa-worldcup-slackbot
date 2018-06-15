<?php

const SLACK_TOKEN      = '';
const SLACK_CHANNEL    = '#worldcup';
const SLACK_BOT_NAME   = 'WorldCup Bot';
const SLACK_BOT_AVATAR = 'http://i.imgur.com/LWw0Yoo.png';

function postToSlack($text, $attachments_text = '')
{
  $slackUrl = 'https://slack.com/api/chat.postMessage?token='.SLACK_TOKEN.
    '&channel='.urlencode(SLACK_CHANNEL).
    '&username='.urlencode(SLACK_BOT_NAME).
    '&icon_url='.SLACK_BOT_AVATAR.
    '&unfurl_links=1&parse=full&pretty=1'.
    '&text='.urlencode($text);
  if ($attachments_text)
  {
    $slackUrl .= '&attachments='.urlencode('[{"text": "'.$attachments_text.'"}]');
  }

  $ch = curl_init($slackUrl);

  curl_exec($ch);
  curl_close($ch);
}

postToSlack('hello form slack bot');

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
<?php

function env($key, $default)
{
	$result = $default;
	if (file_exists(__DIR__ . "/.env"))
	{
		$content = file_get_contents(__DIR__ . "/.env");
		$lines = explode("\n", $content);
		foreach($lines as $line)
		{
			$values = explode('=', $line);
			if ($values > 1)
			{
				if ($key == trim($values[0]))
				{
					$result = trim(implode("=", array_slice($values, 1)));
				}
			}
		}
	}
	
	return $result;
}

define('SLACK_TOKEN', env('SLACK_TOKEN', ''));
define('SLACK_CHANNEL', '#worldcup');
define('SLACK_BOT_NAME', 'WorldCup Bot');
define('SLACK_BOT_AVATAR', 'http://i.imgur.com/LWw0Yoo.png');
define('TIME_ALERT', env('TIME_ALERT', 5));

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

foreach ($data->Results as $match)
{
	$date = (new DateTime($match->Date))->getTimestamp();
	$idMatch = $match->IdMatch;
	
	if (!in_array($idMatch, $dbMatches) && ($date - $current_date) > 0 && ($date - $current_date) <= (60 * TIME_ALERT))
	{
		$team1 = $match->Home->TeamName[0]->Description;
		$team2 = $match->Away->TeamName[0]->Description;
		if ($match->StageName[0]->Description == "Phase de groupes")
		{
			$group_name = $match->GroupName[0]->Description;
            $message = "le match du $group_name : $team1 - $team2 commence.";
			postToSlack($message);
		}

		$dbMatches[] = $idMatch;
	}
}

$fp = fopen($dbPath, "w+");
fwrite($fp, json_encode($dbMatches)); 
fclose($fp);


?>
<?

require 'TPerformant.php';

$access_token  = 'JJ6YPxF9H0ZsjWj5EUZ3';
$access_secret = 'tLdD0JRlZctvKimULyNUXJjirQ2zxieF0ioUlnwH';
$token = '0zQ9aNAA6TR3WFKamaog';
$secret = 'u5w8v5n4EhMI3v5sIYKzjuAqnOFtT44YtHvq7d6i';

$consumer = new HTTP_OAuth_Consumer($token, $secret, $access_token, $access_secret);
$session = new Tperformant("oauth", $consumer, "http://localhost:3000");

$commission = $session->commission_show(475996);

$commission->status = 'rejected';
$session->commission_update($commission->id, $commission);
?>

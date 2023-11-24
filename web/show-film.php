<html>
<body>
<form>

<?php

require_once "./vendor/autoload.php";
use Elastic\Elasticsearch\ClientBuilder;

$film_id = array_key_exists("id", $_REQUEST)?$_REQUEST['id']:"";

$ini_array = parse_ini_file("php-demo.ini");
$server = $ini_array['server'];
$es_index = $ini_array['index'];
$user = $ini_array['user'];
$password = $ini_array['password']??getenv('ES_PW');
$cert =  $ini_array['cert'];


$client = ClientBuilder::create()
    ->setHosts([$server])
    ->setBasicAuthentication($user, $password)
    ->setCABundle($cert)
    ->build();

// Info API

$params = [
    'index' => $es_index,
    'id' => $film_id
];

$response = $client->get($params);
$doc =  $response['_source'];

?>
<a href="javascript:history.back()">Go Back</a>

<h1><?= $doc['title'] ?></h1>

<h3>Director(s): </h3>
<?= join("<br>",$doc['director']) ?>

<h3>Genre: </h3>
<?= join(", ", $doc['genre']) ?>

<h3>Release date: </h3>
<?= $doc['release-date'] ?>

<h3>Overview: </h3>
<?= $doc['overview'] ?>

<h3>Actors: </h3>
<ul>
<li>
<?= join("<li>", $doc['actors']) ?>
</ul>
</body>
</html>

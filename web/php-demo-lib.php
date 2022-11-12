<html>
  <header>
    <title>WPF "Information Retrieval": Elasticsearch/PHP Demo Application</title>
  </header>
<h1>Elasticsearch/PHP Demo Application</h1>
<?php


include 'Utils.php';

require_once "./vendor/autoload.php";
use Elastic\Elasticsearch\ClientBuilder;

$query_string = array_key_exists("q", $_REQUEST)?$_REQUEST['q']:"";
$query_string_form = preg_replace('/"/', '&quot;', $query_string);
$start_from = array_key_exists("start", $_REQUEST)?$_REQUEST['start']:0;
$sort_order = array_key_exists("sort_order", $_REQUEST)?$_REQUEST['sort_order']:"_score:desc";
$mode = array_key_exists("mode", $_REQUEST)?$_REQUEST['mode']:"or";

if (preg_match('/^(.*):(.*)$/', $sort_order, $m)) {
    $sort[$m[1]] = $m[2];
} else {
    die("Error: ".print_r($sort_order,1));
}

$or_semantic = ($mode=="or")?"CHECKED":"";
$and_semantic = ! $or_semantic?"CHECKED":"";

$ini_array = parse_ini_file("php-demo.ini");
$server = $ini_array['server'];
$results_per_page = $ini_array['results_per_page'];
$es_index = $ini_array['index'];
$user = $ini_array['user'];
$password = $ini_array['password'];

$sort_variants = ["_score:desc" => "score (desc)",
                  "_score:asc" => "score (asc)",                
                  "release-date:asc" => "release-date (asc)",
                  "release-date:desc" => "release-date (desc)",
                 ];

?>
  <body>
    <form>
      Use <a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-simple-query-string-query.html#simple-query-string-syntax"><em>Simple query string query</em></a>-syntax to formulate your query<p>
      <input type="text" name="q" value="<?= $query_string_form ?>" size=30>
 Sort:
      <select name="sort_order" onchange="this.form.submit()">
            <?php foreach ($sort_variants as $k => $v) { 
                      $sel = $k==$sort_order?"SELECTED":""; 
            ?>
               <option <?= $sel ?> value="<?= $k ?>"><?=$v ?></option>
            <?php } ?>
      </select>
      <input type="radio" <?= $and_semantic ?> name="mode" value="and"> And-
      <input type="radio" <?= $or_semantic ?> name="mode" value="or"> Or-Semantic
      <p>
      <input type="submit" value="query">
    </form>

<?php
if ($query_string) {
    $client = ClientBuilder::create()
                 ->setHosts([$server])
                 ->setBasicAuthentication($user, $password)
#                 ->setCABundle('c:/software/elasticsearch-8.4.3/config/certs/http_ca.crt')
                 ->build();


    $params = [
      "body" => [
        "query" => [
           "simple_query_string" => [
                "fields" => ["title", "overview","actors","director"],
                "query" => $query_string,
                "default_operator" => $mode
            ]
        ],
        "sort" => [$sort],
        "highlight" => [
          "fields" => [
              "title" =>      ["number_of_fragments" => 3,
		   	       "fragment_size" => 120 ],
	      "overview" =>   ["number_of_fragments" => 2, 
			       "fragment_size" => 120],
	      "actors"=>      ["number_of_fragments" => 2,
		   	       "fragment_size" => 60],
	      "director"=>    ["number_of_fragments" => 2,
		               "fragment_size" => 60]
          ]
        ]

        ],
        'from' => $start_from,
        'explain' => true,
        'size' => $results_per_page,
    ];

    $response = $client->search($params);

    $query_encoded= urlencode(json_encode($params['body']['query']));

    print showHideQueryDSL($params['body']['query']);

    $i = $start_from + 1;
    $total_results = $response['hits']['total']['value'];
    printf("server     : %s<br>", $server);
    printf("# results  : %d<br>", $total_results);
    printf("high score : %.4f<br>", $response['hits']['max_score']);
    printf("time       : %d ms\n<p>", $response['took']);

    print "<table align='center' width='90%'>";
    foreach ($response['hits']['hits'] as $f) {
	print "<td>[$i] <b><a href='show-film.php?id={$f['_id']}'>".($f['_source']['title']."</a></b> [{$f['_source']['release-date']}] ".score_info($f, $i, $query_string)."<br>");
	if (array_key_exists('highlight', $f)) {
            foreach ($f['highlight'] as $k=>$v) {
		foreach ($v as $h) 
                    print preg_replace('/<em>(.*?)<\/em>/','<font color=red>\1</font>', $h)." ($k) ... ";
            }
        }
        print "<p></td></tr>";
        $i++;
    }

    print "</table>";
    if (isset($response)) {
        print pageNavigation($query_string, $start_from, $results_per_page, $total_results);
        print showHideResponseHits($response['hits']);
    }
}
?>
    <script>
        function showHideToggle(name) {
           var x = document.getElementById(name);
           if (x.style.display === "none") {
               x.style.display = "block";
           } else {
               x.style.display = "none";
           }
        }
     </script>
  </body>
</html>


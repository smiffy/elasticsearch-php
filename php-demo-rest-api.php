<html>
  <header>
    <title>WPF "Information Retrieval": PHP/Elasticsearch Demo Application</title>
  </header>
<h1>WPF <em>Information Retrieval</em> - PHP/Elasticsearch Demo Application</h1>
<?php

include 'web/Utils.php';

require_once "./vendor/autoload.php";
# use Elastic\Elasticsearch\ClientBuilder;

$query_string = array_key_exists("q", $_REQUEST)?$_REQUEST['q']:"";
$start_from = array_key_exists("start", $_REQUEST)?$_REQUEST['start']:0;
$sort_order = array_key_exists("sort_order", $_REQUEST)?$_REQUEST['sort_order']:"_score:desc";
$sort= [];
if (preg_match('/^(.*):(.*)$/', $sort_order, $m)) {
    $sort[$m[1]] = $m[2];
} else {
    die("fehler: ".print_r($sort_order,1));
}

$ini_array = parse_ini_file("php-demo.ini");
$results_per_page = 15; //  $ini_array['results_per_page'];
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
      <input type="text" name="q" value="<?= $query_string ?>">
      <input type="submit" value="query">
      <select name="sort_order" onchange="this.form.submit()">
            <?php foreach ($sort_variants as $k => $v) { 
                      $sel = $k==$sort_order?"SELECTED":""; 
            ?>
               <option <?= $sel ?> value="<?= $k ?>"><?=$v ?></option>
            <?php } ?>
      </select>
    </form>

<?php
if ($query_string) {
#    $client = ClientBuilder::create()
#                 ->setHosts(['http://localhost:9200'])
#                 ->setBasicAuthentication($user, $password)
#                 ->setCABundle('c:/software/elasticsearch-8.4.3/config/certs/http_ca.crt')
#                 ->build();


$params = [
        "query" => [
           "simple_query_string" => [
                "fields" => ["title", "overview","actors","director"],
                "query" => $query_string,
                "default_operator" => "and"
           ]
        ],
        'from' => $start_from,
        'explain' => true,
        'size' => $results_per_page,
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
];



    $client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:9200']);
    $response = $client->request('POST',"/$es_index/_search", 
                 ['headers'=>['Content-Type' => 'application/json'], 
                  'body'=>json_encode($params), 
                 ]);

    $response = json_decode($response->getBody()->getContents(), JSON_PRETTY_PRINT );
    $query_encoded= urlencode(json_encode($params['query']));

    print "<a href='javascript:showHideToggle(\"query\");'>JSON DSL query</a><div id='query' style='display:none'><pre>".json_encode(["query"=>$params['query']], JSON_PRETTY_PRINT)."</pre></div><p>\n";

    $i = $start_from + 1;
    $total_results = $response['hits']['total']['value'];
    printf("# results: %d<br>", $total_results);
    printf("Max score : %.4f<br>", $response['hits']['max_score']);
    printf("Time      : %d ms\n<p>", $response['took']);

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
    if ($start_from > 0) { ?>
        &lt;&lt; <a href="?q=<?= urlencode($query_string) ?>&start=<?= $start_from - $results_per_page ?>">previous</a> &nbsp;
    <?php } ?>
<?php 
    if (isset($response)) {
        if ($start_from + $results_per_page <= $total_results) { ?>
<a href="?q=<?= urlencode($query_string) ?>&start=<?= $start_from + $results_per_page ?>">more</a> &gt;&gt; 

<?php   }
        print "<hr><a href='javascript:showHideToggle(\"print_r\");'>show/hide response[hits]</a>";
        print "<div style='display:none' id='print_r'><pre>";
        print_r($response['hits']); // documents
        print "</pre></div>";
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


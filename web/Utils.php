 <?php 

function score_info($f, $i, $query) {
    $terms = preg_match_all('/[A-Za-z\'0-9]+/', $query, $m);
    $description = print_r($f['_explanation'],1);
    foreach ($m[0] as $t) 
        $description = preg_replace("/\b($t)/",'<font color=red>\1</font>', $description);
    $score = $f['_score'] ? $f['_score']:"-irrelevant-";
    return " (score: <a href='javascript:showHideToggle(\"explanation_$i\");'>$score</a>)<div style='display:none' id='explanation_$i'><pre>".$description."</pre></div>";
}

function showHideQueryDSL($q) {
    return "<a href='javascript:showHideToggle(\"query\");'>JSON DSL query</a><div id='query' style='display:none'><pre>".json_encode(["query"=>$q], JSON_PRETTY_PRINT)."</pre></div><p>\n";
}

function showHideResponseHits($response_hits) {
    $str = "<hr><a href='javascript:showHideToggle(\"print_r\");'>show/hide response[hits]</a>";
    $str .= "<div style='display:none' id='print_r'><pre>";
    $str .= print_r($response_hits,1); // documents
    $str .= "</pre></div>";
    return $str;
}

function pageNavigation($query_string, $start_from, $results_per_page, $total_results) {
    $str = "";
    if ($start_from > 0) { 
        $str = "&lt;&lt; <a href='?q=".urlencode($query_string)."&start=".($start_from - $results_per_page)."'>previous</a> &nbsp;\n";
    } 
    if ($start_from + $results_per_page <= $total_results) { 
       $str.= "<a href='?q=".urlencode($query_string)."&start=".($start_from + $results_per_page)."'>more</a> &gt;&gt;\n";
    }
    return $str;
}
<?php
header('Content-Type:text/html; charset=utf-8');
/*include 'SpellCorrector.php';*/

$limit = 10;
$query = isset($_REQUEST['q'])? $_REQUEST['q']:false;
$results = false;

$file_csv = file('NYD Map.csv');
 
foreach($file_csv as $values)
{
    $values = explode(",",$values);
    $arr[$values[0]] = trim($values[1]);
}

if($query)
{
	require_once('SpellCorrector.php');
	require_once('Apache/Solr/Service.php');
	ini_set('memory_limit', '2048M');
	$correctwords = array();
	$qArray = explode(" ",$query);
	foreach($qArray as $q){
        array_push($correctwords,SpellCorrector::correct($q));
    }
    
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr/nydailyindex');
	$additionalParameters = array(
		'fq'=>'a filtering query',
		'sort'=>'true',
		'sort.field'=>array('pageRankFile desc')
	);
	$query_final="";
	$same_elements = array_udiff($qArray, $correctwords, 'strcasecmp');
	if(sizeof($same_elements) != 0){
		foreach($correctwords as $words){
			$query_final.=$words." ";
		}
	}
	try
	{
		if(isset($_GET['pageRank'])){
			if(sizeof($same_elements) != 0){
				$results=$solr->search($query_final, 0, $limit, array('sort' => 'pageRankFile desc',));
			} else {
				$results=$solr->search($query, 0, $limit, array('sort' => 'pageRankFile desc',));
			}
		} else {
			if(sizeof($same_elements) != 0){
				$results = $solr->search($query_final, 0, $limit);
			} else {
				$results = $solr->search($query, 0, $limit);
			}
		}
		
	}
	catch(Exception $e)
	{
		die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
	}

}
?>
<html>
	<head>
		<title>PHP Solr</title>
		<script
			  src="https://code.jquery.com/jquery-3.2.1.js"
			  integrity="sha256-DZAnKJ/6XZ9si04Hgrsxu/8s717jcIzLy3oi35EouyE="
			  crossorigin="anonymous"></script>
			    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  				<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  				<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  				
		 <script type="text/javascript" src="source.js"></script>
	</head>
	<body>
		<form accept-charset="utf-8" method="get">
			<label style="font-family: arial,sans-serif;font-style: normal;font-size: 14px;" for="q">Search:</label>
			<input id="q" type="text" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
			<input type="checkbox" name="pageRank" value="true"> <label for="pageRank" style="font-family: arial,sans-serif;font-style: normal;font-size: 14px;">Page Rank results</label>
			<input style="font-family: arial,sans-serif;font-style: normal;font-size: 14px;" type="submit"/>
			<div id="div"></div>
		</form>
<?php
	if($results){
		$total = (int) $results->response->numFound;
		$start = min(1, $total);
		$end = min($limit, $total);
		if(sizeof($same_elements) != 0){
			$query_original = $query_final;
			if(isset($_GET['pageRank'])){
        		$query_final = $query_final."&pagerank=page";
    		}
    ?>
        <p>Did you mean: <a href="http://localhost/IR4/solr-php-client/ir_source_5.php?q=<?php echo $query_final; ?>"><?php echo $query_original; ?></a></p>
        <p>Showing results for: <?php echo $query_original; ?></p>
    <?php 
		}
?>
		<div style="font-family: arial,sans-serif;"> Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
		<ul style="font-family: arial,sans-serif;font-style: normal;font-size: 14px;list-style-type: none;">
			<?php
				foreach($results->response->docs as $doc){
					$id = $doc->id;
				    $fileName = basename($id);
				    $fileName = basename($fileName, ".html");
				    $fileName = $fileName.".txt";
				    $snippet = "";
				    if(sizeof($same_elements) != 0){
				    	$queryItems = explode(" ",$query_final);
				    } else {
				    	$queryItems = explode(" ",$query);
				    }
				    
				    $fileHandle = fopen("/home/ganesh/snippet/snippet2/".$fileName, "r");
				    $found_item = false;
				    $finalSnippet = "";

				    while (($line = fgets($fileHandle)) !== false) {
				    	if(stripos($line, $query) !==false){
				    		$index = stripos($line,$query);
				                if($index > 158){
				                	$mid_length = (int) (159-strlen($query))/2;
				                    $start = $index - $mid_length;
				                    $end = $index + min(strlen($line),$mid_length);
				                    $snippet = "..".substr($line,$start,$end-$start)."...";
				                }else{
				                    $snippet = $line;
				                 }  
				                    $snippet = str_ireplace($query, "<b>".$query."</b>", $snippet);
				                    $finalSnippet = $finalSnippet.$snippet;
				                    $found_item = true;
				                } else {
				                	foreach($queryItems as $item){
						            if (stripos($line, $item) !== false) {
						                $index = stripos($line,$item);
						                if($index > 158){
						                	$mid_length = (int) (159-strlen($query))/2;
						                    $start = $index - $mid_length;
						                    $end = $index + min(strlen($line),$mid_length);
						                    $snippet = "..".substr($line,$start,$end-$start)."...";
						                }else{
						                    $snippet = $line;
						                 }  
						                    $snippet = str_ireplace($item, "<b>".$item."</b>", $snippet);
						                    $finalSnippet = $finalSnippet.$snippet;
						                    $found_item = true;
						            }    
						        }
				                }
				        
				       
				        if($found_item == true){
				            break;
				        }
				       
				    }
				    fclose($fileHandle);
				     $url_link = urldecode($doc->og_url);
				      if($url_link==""){
					        $id_url = $id;
					        $lastIndex = strripos($id_url, '/');
					        $index_id = substr($id_url,$lastIndex+1);
					        $index_id = urldecode($index_id);
					        $url_link =  $arr[$index_id];
					    }
			?>
				<li>
					<table style="text-align: left">
					<?php
									$title_name = htmlspecialchars($doc->title, ENT_NOQUOTES, 'utf-8');
									$id_name = htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8');
									$description = htmlspecialchars($doc->description, ENT_NOQUOTES, 'utf-8');
					?>
					<hr/>
						<tr>
							<td><a style="color: #1a0dab;font-size: 18px;font-weight: normal;font-family: arial,sans-serif;" href="<?php echo isset($url_link)? $url_link: "N/A" ?>"><?php echo isset($title_name)? $title_name: "N/A" ?></a></td>
						</tr>
						<tr>
							<td ><a style="color: #006621;font-style: normal;font-size: 14px;font-family: arial,sans-serif;" href="<?php echo isset($url_link)? $url_link: "N/A" ?>"><?php echo isset($url_link)? $url_link: "N/A" ?></a></td>
						</tr>
						<tr>
							<td style="font-size: small;font-family: arial,sans-serif;"><?php echo $finalSnippet; ?></td>
						</tr>
						<tr>
							<td style="font-size: small;font-family: arial,sans-serif;"><?php echo isset($id_name)? $id_name: "N/A" ?></td>
						</tr>
						<tr>
							<td style="font-size: small;font-family: arial,sans-serif;"><?php echo isset($description)? $description: "N/A" ?></td>

						</tr>
					</table>

				</li>
			<?php	
				}
			?>
		</ul>
<?php
	}
?>
		<script>
		$(function() {
            $("#q").autocomplete({
                    source : function(request, response) {
                        $.ajax({
                            url : "http://localhost:8983/solr/nydailyindex/suggest?q=" + request.term.toLowerCase().trim().split(" ").pop(-1) + "&wt=json",
                            success : function(data, status, xhr) {
                            	var queryTerm = $("#q").val();
                            	queryTerm = queryTerm.toLowerCase();
	                            var last = queryTerm.trim().split(" ").pop(-1);
	                            var suggestion_list = data.suggest.suggest[last].suggestions;    

	                            suggestion_list = $.map(suggestion_list, function (value, key) {
	                            	if (/^[a-zA-Z0-9]+$/.test(value.term) === false) {
	                                    return null;
	                                }
	                                var query = $("#q").val();
	                                var nextquery = "";
	                                var queries = query.split(" ");
	                               	
	                                if (queries.length != 1) {
	                                    var lIndex = query.lastIndexOf(" ")+1;
	                                    nextquery = query.substring(0, lIndex);
	                                }

	                               	var nextTerm = nextquery + value.term;

	                                return nextTerm;
	                            });
                            response(suggestion_list.slice(0, 10));
                        },
                        dataType : 'jsonp',
                        jsonp : 'json.wrf',
                        minLength: 1
                        });
                    },
                });
        });
       
		</script>
	</body>
</html>

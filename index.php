<?php
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_GET['query']) ? $_GET['query'] : false;
$rank = array('sort'=>'pageRankFile desc');
$results = false;
$arr = array();
$f = fopen("/Users/parinita/Downloads/URLtoHTML_latimes_news.csv", "r");
if($f!==false){
while($line = fgetcsv($f,0,","))
{

	$key = $line['0'];
	$value = $line['1'];
	$arr[$key] = $value;
}
fclose($f);
}
if ($query)
{
 require_once('solr-php-client/Apache/Solr/Service.php');
 $solr = new Apache_Solr_Service('localhost', 8983,'solr/myexample');
 if (get_magic_quotes_gpc() == 1)
 {
	 $query = stripslashes($query);
 }
 try {
		if($_GET['searchOption']=='solr')
			$results = $solr->search($query, 0, $limit);
		else
			$results = $solr->search($query, 0, $limit,$rank);
  }
  catch (Exception $e) {
		die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}
?>

<html> <head> <title>PHP Solr Client Example</title> 
	<style type="text/css">
		#searchForm{
			text-align: center;
			width: 800px;
			margin: 0 auto;
			border-style: double;
		}

		table{
			margin: 0 auto;
		}

		#resultTable{
			width: 1200px;
			margin: 0 auto;
		}
		#message{
			padding: 20px;
			text-align: center;
		}
		
	</style>
</head>
<body>

<div id="searchForm"> 
<h2>Search News</h2>
	<form accept-charset="utf-8" method="get"> <br><label for="q">Enter a query</label>
<input id="q" name="query" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
<br>
<br>
<table style="align:center">
<tr>
<td><input type="radio" name="searchOption" value="solr" <?php if(!isset($_GET['searchOption']) || $_GET[ 'searchOption']=='solr' ) echo "checked"; ?>></td>
<td>Default Solr Search</td>
</tr>
<br>
<tr>
<td><input type="radio" name="searchOption" value="pageRank" <?php if(isset($_GET['searchOption']) && $_GET['searchOption']=='pageRank' ) echo "checked"; ?>></td>
<td>PageRank Search</td>
</tr>
</table>
            <br>
            <br>
<input type="submit" value="Search" />
</form>

</div>
<?php
if ($results) {
	$total = (int) $results->response->numFound;
	$start = min(1, $total);
	$end = min($limit, $total);
?>
<div id="message">
	<b>Total Results:</b> <?php echo $total;?><br>
	Displaying top 10 results.
</div>
<div id="resultTable">
<ol>
	<?php
		foreach ($results->response->docs as $doc) {
	?>
	<li>
		
		 <table>
		<?php

		$id = "N/A";
 		$link="N/A";
		$description = "N/A";
		$title = "N/A";
		foreach ($doc as $field => $value) {
			if($field== "id" ){
				$id=$value;
			}
			if($field == "title"){
				$title=$value;
			}
			if($field == "og_description"){
				$description=$value;
			}
		

		  }
			echo "<tr>";
			
			$link = $arr[trim(substr($id, 34))];

			echo "<b>Title: </b>".'<a href='.$link.' target="_blank">'.$title.'</a>'."<br>";
			echo "<b>Link: </b>".'<a href='.$link.' target="_blank">'.$link.'</a>';
			echo "<br>";
			echo "<b>ID: </b> ".$id."<br>";
			echo "<b>Description: </b>".$description."<br><br><br>";
			echo "</tr>";
		 ?>


		</table>
	
	</li>
	<?php } ?>
</ol>
</div>
<?php } ?>
</body>
</html>

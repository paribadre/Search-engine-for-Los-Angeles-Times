<?php
ini_set('memory_limit','-1');
ini_set('max_execution_time', 300);

include 'SpellCorrector.php';

header('Content-Type: text/html; charset=utf-8');
$div=false;
$correct_word = "";
$output = "";

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$og_query = $query;
$results = false;

if ($query)
{
  $words = explode(" ",$query);
  foreach($words as $word){
    $correct_word = $correct_word.trim(SpellCorrector::correct($word))." ";
  }
  $correct_word = rtrim($correct_word);
  $div=false;
  if($correct_word != $query) {
	   $div =true;
     $output = "Did you mean: <a href='http://localhost:8080/WebServer.php?q=$correct_word'>$correct_word</a>";
     $query = $correct_word;
	}

 require_once('solr-php-client/Apache/Solr/Service.php');
 $solr = new Apache_Solr_Service('localhost', 8983,'solr/myexample');
 if (get_magic_quotes_gpc() == 1)
 {
	 $query = stripslashes($query);
 }
 try {
		if($_REQUEST['method']=='lucene')
			//$additionalParams = array('sort'=>'');
      $results = $solr->search($query, 0, $limit);
		else{
      $additionalParams = array('sort'=>'pageRankFile desc');
      $results = $solr->search($query, 0, $limit, $additionalParameters);
    }
  }
  catch (Exception $e) {
		die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}
?>

<html>
<head>
  <title>PHP Solr Client Example</title>
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
  <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
  <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
</head>

<body style="background-color:#fff2d4;">
  <form accept-charset="utf-8" method="get">
    <center>
      <h1><i> <p style="font-family:Optima">My Search Engine</p></i></h1>
    <br><label for="q">Search Query:</label>
    <input id="q" name="q" type="text" list="searchresults" style="border: 1px solid #ccc; border-radius: 4px; height:35px; width: 200px;" placeholder="Search your query..." value="<?php echo htmlspecialchars($og_query, ENT_QUOTES, 'utf-8'); ?>" autocomplete="off"/>
    <datalist id="searchresults"></datalist>
    <input type="hidden" name="spellcheck" id="spellcheck" value="false"> <br>
    <br>

    <table>
      <tr>
        <td><input type="radio" name="method" value="lucene" <?php if(!isset($_REQUEST[ 'method']) || $_REQUEST[ 'method']=='lucene' ) echo "checked"; ?>></td>
        <td>Solr - Lucene</td>
      </tr>
      <br>
      <tr>
        <td><input type="radio" name="method" value="pageRank" <?php if($_REQUEST[ 'method']=='pageRank' ) echo "checked"; ?>></td>
        <td>External PageRank</td>
      </tr>
    </table
    <br>
    <input type="submit" style="border: 1px solid #ccc; border-radius: 4px; height:30px;"/>
  </center>
  </form>

  <script>
 $(function() {
   var count=0;
   var suggestion_list = [];
   $("#q").autocomplete({
     source : function(request, response) {
       var suggest="",before="";
       var query = $("#q").val().toLowerCase();
       var s =  query.lastIndexOf(' ');
       if(query.length-1>s && s!=-1){
        suggest=query.substr(s+1);
        before = query.substr(0,s);
      }
      else{
        suggest=query.substr(0);
      }
      var URL = "http://localhost:8983/solr/myexample/suggest?q=" + suggest;
      $.ajax({
       url : URL,
       success : function(data) {
        var js =data.suggest.suggest;
        var docs = JSON.stringify(js);
        var jsonData = JSON.parse(docs);
        var result =jsonData[suggest].suggestions;
        var j=0;
        var stem =[];
        for(var i=0;i<5 && j<result.length;i++,j++){
          if(result[j].term==suggest)
          {
            i--;
            continue;
          }
          var suggestion =result[j].term;
          if(stem.length == 5)
            break;
          if(stem.indexOf(suggestion) == -1)
          {
            stem.push(suggestion);
            if(before==""){
              suggestion_list[i]=suggestion;
            }
            else
            {
              suggestion_list[i] = before+" ";
              suggestion_list[i]+=suggestion;
            }
          }
        }
        response(suggestion_list);
      },
      dataType : 'jsonp',
      jsonp : 'json.wrf'
    });
    },
    minLength : 1
  })
 });
</script>
<?php
  if ($div){
    echo $output;
  }

  if ($results) {
  	$total = (int) $results->response->numFound;
  	$start = min(1, $total);
  	$end = min($limit, $total);
  ?>
  <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
  <ol>
  	<?php
  		foreach ($results->response->docs as $doc)
      {
  	?>
  	<li>
      <table>
  		<?php

      $title = "";
      $url="NA";
  		$id = "NA";

      foreach ($doc as $field => $value) {

  			if($field == "title"){
          // if(sizeof($value) == 1){
            $title = $value;
          // }
          // else{
            // $title = $value[0];
          // }
  			}
        else if($field == "og_url"){
          // if(sizeof($value) == 1){
            $url = $value;
          }
          // else{
            // $url = $value[0];
          // }
        // }
        else if($field== "id" ){
  				$id=$value;
  			}

        $str = file_get_contents($id);
        $str = preg_replace(
        array(
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<title[^>]*?>.*?</title>@siu',
            '@<svg[^>]*?>.*?<xml:space="preserve" >@siu',
            '@<meta name[^>]*?>.*?@siu',
            '@<header[^>]*?>.*?</header>@siu',
            ), "", $str);

        $str = str_replace('Advertisement', '',$str);
        $str = str_replace('Be the first to comment','',$str);
        $str = str_replace('Hide', '',$str);
        $str = str_replace('Comments', '',$str);
        $str = str_replace('Like', '',$str);
        $str = str_replace('Post', '',$str);
        $str = str_replace('Share', '',$str);

        $str = strip_tags($str);
        $words = explode(" ", $query);
        $snippet = "";

        $snippet_len = 160;
        $pos = 0;
        $sent = strtolower($str);
        $q = strtolower($query);
        if(strpos($sent,$q,$pos+1)){
          while(strlen($snippet)<160){
            $pos = strpos($sent,$q,$pos+1);
            $sub = substr($sent,0,$pos-20);
            $p = strrpos($sub," ");
            $snippet =$snippet.substr($str, $p,$snippet_len);
            $snippet_len = $snippet_len - strlen($snippet);
          }
        }

        if($snippet==""){
          foreach ($words as $word) {
            $sent = strtolower($str);
            $q = strtolower($word);
            if(strpos($sent,$q,$pos+1)){
              while(strlen($snippet)<160){
                $pos = strpos($sent,$q,$pos+1);
                $sub = substr($sent,0,$pos-20);
                $p = strrpos($sub," ");
                $snippet =$snippet.substr($str, $p,$snippet_len);
                $snippet_len = $snippet_len - strlen($snippet);
              }
            }
          }
        }

        $last_pos = strrpos($snippet," ");
        $snippet = substr($snippet,0, $last_pos);
        foreach($words as $item)
    	    $snippet = str_ireplace($item, "<strong>".ucfirst($item)." </strong>",$snippet);

      }
  		echo "<tr>";

  		echo '<b>'."Title:".'<a href='.$url.'>'.$title.'</a></b>'."<br>";
  		echo "URL: ".'<a href='.$url.' target="_blank">'.$url.'</a>';
  		echo "<br>";
  		echo "ID: ".$id."<br>";
      echo "Snippet: ...".$snippet."...<br><br>";
  		echo "</tr>";
  	  ?>
  		</table>
  	</li>
	  <?php } ?>
  </ol>
  <?php } ?>
  </body>
</html>

<?
require_once('vendor/autoload.php');
use Scriptotek\Sru\Client as SruClient;
use Scriptotek\Marc\Collection;
use Scriptotek\Marc\Record;

function sru_records($input_query) {

	
	$response = file_get_contents('YOUR_SRU_SERVER_HERE' . http_build_query([
	     
    	'operation'      => 'searchRetrieve',
    	//'operation'      => 'explain',
    	'schema' 		=> 'marcxml',
    	'version'        => '1.2',
    	'maximumRecords' => '3',
    	'alma.mms_material_type' => 'Book',
    	//example queries for testing
    	//'query'          => 'alma.title="the perks of being a wallflower"%20and%20alma.mms_tagSuppressed=false%20and%20alma.author="chbosky"'
    	'query'          => $input_query
		//'query' 		 => 'alma.isbn="9780671027346"%20and%20alma.mms_tagSuppressed=false'
	]));


	$records = Collection::fromString($response);
	
	//debug - check response
	//return($response);
	
	$author = "";
	$title = "";
	
	//get the title
	foreach ($records as $record) {
		$title = $record->getField('245')->getSubfield('a')->getData();		
		$author = $record->query('100$a')->text();
        
	
		//for each 852 return institutional holdings

		foreach ($record->query('852') as $field) {
   		
   			$inst_symbol = $field->getSubfield('a')->getData();
   			$inst_format = $field->getSubfield('9')->getData();
   			$inst_mmsid = $field->getSubfield('6')->getData();
   			$inst_holding[] = array("inst_symbol"=>$inst_symbol,"inst_format"=>$inst_format, "inst_mmsid" =>$inst_mmsid);
		}
		
		//for each electronic availability get institutional availability
		foreach ($record->query('AVE') as $field) {
		 
		    $estatus = $field->getSubfield('e')->getData();
		    $collection = $field->getSubfield('m')->getData();

		   
		    $eholding[] = array("estatus"=>$estatus,"collection"=>$collection);
		    
		}
	
		return array($title,$author,$inst_holding,$eholding);
	
	
	}
	
  }
  

$file = fopen('booklist.csv', 'r');
  while (($line = fgetcsv($file)) !== FALSE) {

       if (($line[5]) !== "No text required") {
       		if (!empty($line[8])) {
         		$input_query = 'alma.isbn="' . $line[8] . "\"";
         		$primo_url = "https://YOUR-PRIMO.hosted.exlibrisgroup.com/primo-explore/search?query=isbn,contains," . $line[8] .",AND&tab=everything&search_scope=EVERYTHING&sortby=rank&vid=YOURVIEWHERE&lang=en_US&mode=advanced&offset=0";

       		} elseif (!empty($line[5])) {
         		$input_query = 'alma.title="' . $line[5] . "\"%20and%20alma.creator=\"" . $line[4] . "\"";
         		$primo_url = "https://YOUR-PRIMO.hosted.exlibrisgroup.com/primo-explore/search?query=title,contains," . $line[5] .",AND&query=creator,contains," . $line[4] . ",AND&tab=everything&search_scope=EVERYTHING&sortby=rank&vid=YOURVIEWHERE&lang=en_US&mode=advanced&offset=0";

       		}
       //Debug Input Query
       //print_r($input_query);
       //$output = "";
      
       		$output = sru_records($input_query);
       		
       		$isbn = $line[8];
       		$title_orig = $line[5];
       		$author_orig = $line[4];
       		echo "looking up " . $title_orig . "\n";
       		//Debug Output
       		print_r($output);
       		
       		
       	 if (is_array($output)) {
       
         		$fp = fopen('booklist_output.csv', 'a');
     	 		$title = $output[0];
         		$author = $output[1];
         		$holdings = $output[2];
         		$eholdings = $output[3];
         		
         
         
         		$holding_string = array();
         		$eholding_string = array();
         		$heldby = "";
         		    if (is_array($holdings)) {
         			foreach($holdings as $holding) {
          				$institution_holding = $holding[inst_symbol];
          				$institution_format = $holding[inst_format];
         				$institution_mmsid = $holding[inst_mmsid];
          				$holding_string[] = $institution_holding . "|" . $institution_format . "|" . $institution_mmsid;
          				$holding_strings = implode(";",$holding_string);
          						
         			} } else {
         			$holding_strings = "";
         			}
         			//Check if Array contains institutional ID			
         			if (strpos($holding_strings, 'YOUR INSTITUTION') !== false) {
            						$heldby = "held by MY INSTITUTION";
          							} else {
            						$heldby = "held by NZ";
          							}
          					    //DEBUG holdings
          						//print_r($holding_string);
         			
         			  if (is_array($eholdings)) {
         			    foreach($eholdings as $eholding) {
         			      $estatus = $eholding[estatus];
         			      $collection = $eholding[collection];
         			      $eholding_string[] = $estatus . "|" . $collection;
         			      $eholding_strings = implode(";",$eholding_string);
         			  } } else {
         			  $eholding_strings = "";
         			  }

         			
         			print_r($eholding_strings);

         						$result = array($line[0],$line[1],$line[2],$line[3],$line[4],$line[5],$line[6],$line[7],$line[8],$line[9],$line[10],$line[11],$line[12],$line[13],$line[14],$heldby, $holding_strings, $eholding_strings,$primo_url);
         						//Debug SRU Result
         						//print_r($result);
         						fputcsv($fp, $result);
         
         						
         
       		} else {
         		//print("not found");
         		$result = array($line[0],$line[1],$line[2],$line[3],$line[4],$line[5],$line[6],$line[7],$line[8],$line[9],$line[10],$line[11],$line[12],$line[13],$line[14],"not found","","",$primo_url);
         		//print_r($result);
         	fputcsv($fp, $result);
       		}
       //debug - check input query string
       //print_r($input_query);
	    } else {
	      $result = array($line[0],$line[1],$line[2],$line[3],$line[4],$line[5],$line[6],$line[7],$line[8],$line[9],$line[10],$line[11],$line[12],$line[13],$line[14],"No text required");
	      fputcsv($fp, $result);
	      }
	   }
fclose($file);
fclose($fp);

?>
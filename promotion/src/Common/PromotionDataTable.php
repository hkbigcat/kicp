<?php

namespace Drupal\promotion\Common;
use Drupal\common\CommonUtil;


class PromotionDataTable {
	
	public static function pcPermute($items, $perms = array( )) {
		
		$output="";
		if (empty($items)) { 
			$output .=  " StaffName REGEXP '".join('.+', $perms) . ".*' or ";
		}  else {
			for ($i = count($items) - 1; $i >= 0; --$i) {
				 $newitems = $items;
				 $newperms = $perms;
				 list($foo) = array_splice($newitems, $i, 1);
				 array_unshift($newperms, $foo);
				 $output .= self::pcPermute($newitems, $newperms);
			 }
		}
		
		return $output;
	}

	public static function tokenSQL($str) {

		
		$str2 = preg_replace('/\s+/', ' ', str_replace(array("-", ",", "\'", "\"")," ",addslashes(trim($str))));
		$pattern = "/[^a-z\d\s]/i";
		$str3 = preg_replace($pattern, "non_English_characters", $str2);
		$toka =  explode(" ",$str3);
		$a = self::pcPermute($toka);
		
		$search_result = substr($a, 0, -4);
		
		return $search_result;		

	}
	
	
	public function getSuggestionName($search_str) {
		$sql = "SELECT DISTINCT PromotionRefID, StaffName, MATCH (StaffName) AGAINST ('".$search_str."') as score FROM kicp_promotion_staff  WHERE MATCH (StaffName) AGAINST ('".$search_str."') > 5 order by score desc limit 5";
		
        $database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

		$output="";
		foreach ($result as $record) {
			$output .= '<li><a href="promotion?refID='.$record['PromotionRefID'].'"><font color="#E77D1F">'.$record['StaffName'].'</font></a></li>';				
		}
		if ($output != "") {
			$output = '<ul>'.$output.'</ul>';
		}
		
	
		return $output;
	}
	
		public function getDisplayTable($refID, $page, $search_str) {

			$output_table_header = "";
			$output = array();
		
			$search_sql = ($search_str!="") ? " where ".self::tokenSQL($search_str): "";
			$search_page = ($search_str!="") ? "&search_str=".$search_str : "";

			
			$pagem1=(int)$page-1;
			$pagem2=(int)$page-2;
			$pagea1=($page=="")?2:(int)$page+1;
			$pagea2=($page=="")?3:(int)$page+2;
			$sql2 = "SELECT COUNT(DISTINCT(PromotionRefID)) as totalrow FROM kicp_promotion_staff ".$search_sql;				
			
			$database = \Drupal::database();
			$this_record  = $database-> query($sql2)->fetchObject();
			$total_rows = $this_record->totalrow;			
	
			$total_pages = ceil($total_rows / 15 );
			$start = ($page=="")?0:$pagem1 * 15;
			
			
			$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT PromotionRefID, StaffName FROM kicp_promotion_staff".$search_sql." order by StaffName ";
			
			$sql .= ' limit '.$start.', 15 ';
			
			// prepare SQL statement [End]

			$database = \Drupal::database();
			$result  = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
			
			$i = 0;
			$header=0;
			
			foreach ($result as $record) {
			
				$output[$i] = "";
				$j = $i + 1;

				$StaffName2 = strtolower(str_replace(array(", ",",","-"), " ", $record['StaffName']));
				$search_str2= strtolower(str_replace(array(", ",",","-"), " ", $search_str));
				
				if ($search_str != "") {
					if ($header == 0) {
						
						//if (preg_match("/".str_replace(array(", ",",","-"), "[ |,|-]", $StaffName)."/i", $search_str)) {
						if ($StaffName2==$search_str2) {
							$header=1;
							$header_title="Staff Name";
						} else {
							$header=2;
							$header_title="Similar match";
						}
						
						$output_table_header .= '<table width="100%" cellpadding="10" border="0" class="staffnametable">';
						$output_table_header .= '<tr class="staff_header"><td class="head_title"><strong>'.$header_title.'</strong></td></tr>';
					} else if ($header == 1) {
						//if (!preg_match("/".str_replace(array(", ",",","-"), "[ |,|-]", $StaffName)."/i", $search_str)) {
						if ($StaffName2!=$search_str2) {
							$output[$i] = '<tr><td height="25" bgcolor="#ffffff"></td></tr><tr class="staff_header"><td class="head_title"><strong>Similar match</strong></td></tr>';
							$header=2;
						}
					} 
				} else {
					if ($i==0) {
						$output_table_header .= '<table width="100%" cellpadding="10" border="0" class="staffnametable">';
						$output_table_header .= '<tr class="staff_header"><td class="head_title"><strong>Staff Name</strong></td></tr>';
					}						
				}
				
				$selectedname = ($refID == $record['PromotionRefID'] ) ? ' style="border: 2px solid orange;" ':''; 
				$pageurl = ($page!="") ? "&page=".$page:"";
				$output[$i] .= '<tr class="staffrow"><td'.$selectedname.'><a href="collections_promotion?refID='.$record['PromotionRefID'].$search_page.$pageurl.'">'.$record['StaffName'].'</a></td></tr>'; // End of "row_{x}"
				$i++;
			}

			if ($total_pages>0)
			  $output[$i] = '</table>';
			
			if ($total_pages >1) {
								
				$output[$i] .= '<ul class="pager__items js-pager__items">';
				$output[$i] .= '  <li class="pager__item"><a href="?page=1'.$search_page.'">First</a></li>';

			
				if ($page==$total_pages && $page >=3)
						$output[$i] .= '  <li class="pager__item"><a href="?page='.$pagem1.$search_page.'">'.$pagem2.'</a></li>';

				if ($page>1) 
					$output[$i] .= '  <li class="pager__item"><a href="?page='.$pagem1.$search_page.'">'.$pagem1.'</a></li>';
				
				if ($page=="") 
					$output[$i] .= '  <li class="pager__item is-active">1</li>';
				else 
					$output[$i] .= '  <li class="pager__item is-active">'.$page.'</li>';

				if ($page < $total_pages)
					$output[$i] .= '  <li class="pager__item"><a href="?page='.$pagea1.$search_page.'">'.$pagea1.'</a></li>';
				
				if ($page <=1 && $total_pages > 2 ) {
					$output[$i] .= '  <li class="pager__item"><a href="?page=3'.$search_page.'">3</a></li>';
				}

				$output[$i] .= '  <li class="pager__item"><a href="?page='.$total_pages.$search_page.'">Last</a></li>';
				
				
				$output[$i] .= '</ul>';
			}
			
			$output_final = $output_table_header;
			
			
			for($k=0; $k<=$i; $k++) {
                if(isset($output[$k]) && $output[$k] != "") {
                    $output_final .= $output[$k];
                }
            }
			
			return $output_final;
			
		}
	
		public function getPromotionDetail() {
						
		$refID = (isset($_REQUEST['refID']) && $_REQUEST['refID'] != "") ? $_REQUEST['refID'] : ""; 
		$page = (isset($_REQUEST['page']) && $_REQUEST['page'] != "") ? $_REQUEST['page'] : "";
        $search_str = (isset($_REQUEST['search_str']) && $_REQUEST['search_str'] != "") ? $_REQUEST['search_str'] : ""; 			
		$rank = (isset($_REQUEST['rank']) && $_REQUEST['rank'] != "") ? $_REQUEST['rank'] : "";						
		
		$i = 0;
		$output = "";
		
		if ($refID == "") {
			$sql = "SELECT DISTINCT(PromotionRefID), StaffName FROM kicp_promotion_staff";
			
			if ($search_str != "") {
				$sql .=  " where ".self::tokenSQL($search_str);
			}
			
			$sql .= " order by StaffName ";
			
			if ( $search_str == "") {
				$start = ($page=="")?0:((int)$page-1) * 15;				
				$sql .= "limit ".$start.", 1";
			}
			else
				$sql .= "limit 1";			
			
			
					
			$database = \Drupal::database();
			$this_record = $database-> query($sql)->fetchObject();	
			$refID = $this_record->PromotionRefID;
			
		}
		

		$sql = "SELECT * FROM kicp_promotion_staff s LEFT JOIN kicp_promotion_image i ON s.PromotionID = i.LinkID where PromotionRefID='".$refID."' order by Promotion_Date desc";
		//$sql = "SELECT * FROM kicp_promotion_staff where PromotionRefID='".$refID."' order by Promotion_Date desc";
				
		$database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);

		$k=0;
		$lastPublish_Date = "";
		$slide=0;
		foreach ($result as $record) {

			$j = $i + 1;


			if ($j==1) $output .= '<h1 class="staff_head">'.$record['StaffName'].'</h1>';
			
			
			$newsletterSlider = "";
			if ($lastPublish_Date != $record['Publish_Date']) {
				
				if ($i!=0) $output .= '<hr style="margin-top: 40px;">';
				
				$dateNum = date( 'Ym', strtotime($record['Publish_Date']));

				$yrstr = date( 'Y', strtotime($record['Promotion_Date']));				
				$sql_samerank = "SELECT count(1) as total FROM kicp_promotion_staff  where ToPost='".$record['ToPost']."' and PromotionYear=".$yrstr;
				$database = \Drupal::database();
				$count_samerank = $database-> query($sql_samerank)->fetchObject();

				$total_samerank = $count_samerank->total;
				$s_samerank = ((int)$total_samerank>1)? "s":"";
				
				if ($record['ToPost'] == $rank) {
					$output .= '<span id="RANK_HERE"></span>';
					$k = $j;
				}				

				$downloadpdf = ($record['newlink'] && $record['newlink']!=null)?$record['newlink']:'/kicp/sites/default/files/public/promotion/estaffnews/'.$dateNum.'.pdf';
	
				$output .= '<div class="newsletter"><a target="_top" href="'.$downloadpdf.'" title="Download eStaff Newsletter">E-Staff News: '.date( 'M, Y', strtotime($record['Publish_Date'])).'</a><div class="w20px"></div>';
				$output .= '<a href="javascript:void(0);" title="Open e-Staff News '.$dateNum.'" onclick="showSlides(1,'.$slide.', this);"><i id="newsimg-'.$slide.'" class="fa-regular fa-newspaper" style="color:#000"></i></a><div class="w20px"></div><a target="_top" href="'.$downloadpdf.'" title="Download e-Staff News '.$dateNum.'"><i class="fa-solid fa-download" style="color:#000"></i></a></div>';
				$output .= '<p>Promoted to the rank: '.$record['ToPost'].'</p>';
				$allyrpost = ' ( <a title="All staff who got promoted to '.$record['ToPost'].$s_samerank.' in '.$yrstr.'" class="detailslink" href="/kicp/collections_rank?rank='.$record['ToPost'].'&year='.$yrstr.'">'.$total_samerank.' '.$record['ToPost'].$s_samerank.' in '.$yrstr.'</a> )';
				$output .= '<p class="publish_date">Effective date: '.date( 'd M, Y', strtotime($record['Promotion_Date'])).$allyrpost.'</p>';
				
				//$files = glob('modules/custom/promotion/images/newsletter/*'.$dateNum.'*.png');
				$files = glob('sites/default/files/public/promotion/newsletter/*'.$dateNum.'*.png');
				$slideContent ="";
				$ni=0;
				$slide++;
				$arrow=$slide-1;
				foreach ($files as $file) {
					$ni++;
					$slideContent .= '<div class="mySlides'.$slide.'" style="display:none;"><img src="/kicp/'.$file.'" alt="e-Staff News '.substr($file,strrpos($file,"/")+1,-4).'" style="width:100%"></div>';
				}
					
				
				if ($ni>0) {
					$newsletterSlider = '<div class="slideshow-container" id="slide-'.$slide.'" style="display:none;">'.$slideContent;
					if ($ni>1) {
						$newsletterSlider .= ' <a class="prev" onclick="plusSlides(-1, '.$arrow.')">&#10094;</a>';
						$newsletterSlider .= ' <a class="next" onclick="plusSlides(1, '.$arrow.')">&#10095;</a>';
					}
					$newsletterSlider .= '</div>';
				} else {$slide--;}
				
				
			}	
			
			$output .= $newsletterSlider;

			
			//if ($Title!="") {
			if ($record["Title"]!="") {				
				$output .= '<div class="imgwrap"><img src="/kicp/sites/default/files/public/promotion/photo/'.strtolower($record["Title"]).'" alt="Photo '.substr($record["Title"],0,-4).'" border="0"></div>';
			}
		
			//$lastPublish_Date = $Publish_Date;
			$lastPublish_Date = $record['Publish_Date'];
			$i++;
		}
		
		
		if ($slide > 0) {
			$slidestr = "";
			$slidestr2 = "";
			$slidestr3 = "";
			for ($sii=1;$sii<=$slide;$sii++) {
				$slidestr .= '"mySlides'.$sii.'",';
				$sii2=$sii-1;
				$slidestr3 .= '1,';
			}
			$slidestr = substr($slidestr, 0, -1);
			$slidestr3 = substr($slidestr3, 0, -1);
			//$slidem1=$slide-1;
			
			$output .= '<script>var slideIndex = ['.$slidestr3.'];var slideId = ['.$slidestr.'];</script>';
		}	
		
		
		
		
		
		if ($rank!="" && $k>1) {
			$output .= '<script>setTimeout(() => {  scrollhash("RANK_HERE"); }, 100);</script>';
		}
	
		return $output;

	}
	
	
	public function countPromotionRank($rank, $year, $search_str) {
					

		$ranksql = ($rank !="")  ? "ToPost = '".$rank."' ":"";

		$andsql = ($ranksql != "") ? " and " : "";		
		$yearsql = ($year !="") ? $andsql."PromotionYear = '".$year."' ":"";
		
		if ($rank=="") {
			$andsql = ($ranksql != "" || $yearsql !="" ) ? " and " : "";
			$name_sql = ($search_str != "")? $andsql.self::tokenSQL($search_str):"";
		} else {
			$name_sql="";
		}
		
		$wheresql = ($ranksql !="" || $yearsql !="" || $name_sql !="") ? " where ":"";
		
		$sql = "SELECT COUNT(DISTINCT(PromotionRefID)) as total FROM kicp_promotion_staff".$wheresql.$ranksql.$yearsql.$name_sql;
		
		$database = \Drupal::database();
        $this_record = $database-> query($sql)->fetchObject();
		return $this_record->total;		


	}

	public function getPromotionRank($rank, $year, $search_str) {
					
		$i = 0;
		$output = "";

		$ranksql = ($rank !="")  ? "ToPost = '".$rank."' ":"";
		
		$andsql = ($ranksql != "") ? " and " : "";		
		$yearsql = ($year !="") ? $andsql."PromotionYear = '".$year."' ":"";
		
		//$andsql = ($ranksql != "" || $yearsql !="" ) ? " and " : "";
		//$name_sql = ($search_str != "")? $andsql.self::tokenSQL($search_str):"";
		$name_sql = "";
		
		$sql = "SELECT DISTINCT(PromotionRefID), StaffName FROM kicp_promotion_staff where ".$ranksql.$yearsql.$name_sql." order by StaffName";

		$database = \Drupal::database();
        $result = $database-> query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
		
			
		foreach ($result as $record) {

			$showstaff = (preg_match("/".str_replace(array(", ","-"," "), ".*", $search_str)."/i", $record['StaffName']))? '' : ' style="display:none" ';
			$j = $i + 1;
			$output .= '<a href="get_promotion_detail?refID='.$record['PromotionRefID'].'&rank='.$rank.'" rel="modal:open"><span class="staff_one"'.$showstaff.'>'.$record['StaffName'].'</span></a>';
			$i++;
		}

		
		return $output;

	}

	public function getLatestPublish() {
		$sql = "SELECT MAX(Publish_Date) as latest FROM kicp_promotion_staff";

        $database = \Drupal::database();
        $this_record = $database-> query($sql)->fetchObject();
		return $this_record->latest;
	}
	
}
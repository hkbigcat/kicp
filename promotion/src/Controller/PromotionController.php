<?php

/**
 * @file
 */

namespace Drupal\promotion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\promotion\Common\PromotionDataTable;
use Drupal\common\CommonUtil;
use Drupal\common\AccessControl;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class PromotionController extends ControllerBase {

    public $is_authen;
    public $module;    

    public function __construct() {
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;		
        $this->module = 'promotion';
    }
	
	
	public function PromotionContent() {

		$url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }

		
		$refID = \Drupal::request()->query->get('refID');
		$refID = $refID??""; 

		$page = \Drupal::request()->query->get('page');
		$page = $page??"";

		$search_str = \Drupal::request()->query->get('search_str');
        $search_str = $search_str??""; 

		$output = "";
		$content = "";
		$content2 = "";
		$content3 = "";	
       		
		$total = 0;
		$totalStr = new PromotionDataTable;


		//if ($search_str!="")
			$total = $totalStr->countPromotionRank("", "" ,  $search_str);

		
		$LatestDate = new PromotionDataTable;
		$latest = $LatestDate->getLatestPublish();
        
		
		if ($search_str!="") {
		
        // staff list
			$ContentTable = new PromotionDataTable;
			$content = $ContentTable->getDisplayTable($refID, $page, $search_str);
			if ($total==1) {
				$content = str_replace('<td>', '<td style="border: 2px solid orange;">', $content);
			}

		
		}		
        

		if ($search_str!="") {
			if ($refID != "" || $total == 1) {
					$ContentTable2 = new PromotionDataTable;
					$content2 = $ContentTable2->getPromotionDetail();
					if ($total == 1) {
						$content2 = str_replace('<td>', '<td style="border: 2px solid orange;">', $content2);
					} 
					
						
					
			} else if ($total > 1) {
					$output .= '<p class="plselect">Please select a staff to check the promotion details.</p> ';
			} else if ($total < 1)	{
				$output = "";
				$output .= '<p class="plselect">We cannot find <strong>'.$search_str.'</strong> or result that contains <strong>'.$search_str.'</strong></p>';
				
				if (preg_match('/\w{2,}(,|, )(\w{2,})$/', $search_str)) {
					$output .= '<p> You may try to search again without English name, e.g. instead of: "'.$search_str.'", try to search "<a title="Search: '.substr($search_str,0,strrpos($search_str,",")).'" href="promotion?search_str='.substr($search_str,0,strrpos($search_str,",")).'"><font color="#E77D1F">'.substr($search_str,0,strrpos($search_str,",")).'</font></a>".</p> ';
				} else {
					$output .= '<p>Please refine the name to search.</p> ';
				}
				$ContentTable3 = new PromotionDataTable;
				$content3 = $ContentTable3->getSuggestionName($search_str);
				
				if ($content3 != "") {
					$output .=  '<p>Similar result:'.$content3.' </p>';
				}

				
			}
		}

		
		return [
			'#theme' => 'promotion-byname',
			'#latest' => $latest,
			'#content' => $content,
			'#content2' => $content2,
			'#content3' => $content3,
			'#output' => $output,
			'#total' => $total,
			'#search_str' => $search_str,
		]; 

        
    }
	


	public function PromotionRank() {

		$url = Url::fromUri('base:/no_access');
        if (! $this->is_authen) {
            return new RedirectResponse($url->toString());
        }
	
        $code = 'promotion_maint';
		$output = "";
		$content = "";

        // Highlight the module name in top menu
        $output .= '<script> jQuery("a[href=\'/kicp/collections\']").addClass("is-active");</script>'; 

		$rank = \Drupal::request()->query->get('rank');
		$rank = $rank??""; 

		if ($rank=="") {
			$rank = \Drupal::request()->query->get('rank1');
			$rank = $rank??""; 
			if ($rank=="") {
				$rank = \Drupal::request()->query->get('rank2');
				$rank = $rank??""; 	
			}
			if ($rank=="") {
				$rank = \Drupal::request()->query->get('rank3');
				$rank = $rank??""; 	
			}

		}
		$year = \Drupal::request()->query->get('year');
		$year = $year??""; 

		$refID = \Drupal::request()->query->get('refID');
		$refID = $refID??""; 
		
		$search_str = \Drupal::request()->query->get('search_str');
        $search_str = $search_str??""; 

		$submit = \Drupal::request()->query->get('submit');


        $output .= '<div class="PromotionMainContainer">';
        
		$output .= '<a href="/kicp/collections_promotion" class="button1">Search By Name</a>';
		$output .= '<a href="/kicp/collections_rank" class="button1 btn1active">Search by Rank and Year</a>';
		
		$LatestDate = new PromotionDataTable;
		$latest = $LatestDate->getLatestPublish();
				
		$year_select = array();	
		for ($y=2024;$y>=2004;$y--) {
			$year_select[] = $y;
		}
				
				
		$items = "item";
		$total = 0;
		if ($rank !="" && $year !="") {
		 
		
			$totalStr = new PromotionDataTable;
			$total = $totalStr->countPromotionRank($rank, $year, $search_str);
			$items = ($total>1) ? 'items':'item';
		
        // staff list
			$ContentTable = new PromotionDataTable;
			$content = $ContentTable->getPromotionRank($rank, $year, $search_str);
			$num_nameshow = $total - substr_count($content, 'class="staff_one" style="display:none"');
		
        }

		
		return [
			'#theme' => 'promotion-byrank',
			'#latest' => $latest,
			'#content' => $content,
			'#total' => $total,
			'#items' => $items,
			'#rank' => $rank,
			'#year' => $year,
			'#submit' => $submit,
			'#search_str' => $search_str,
			'#year_select' => $year_select,
		]; 
        
    }
	
	public function getPromotionDetailPage() {					
       
        /*
         * module 
         * record_id
         */

		$ContentTable2 = new PromotionDataTable;
		$content2 = $ContentTable2->getPromotionDetail();			
		
        $response = array($content2);
        return new JsonResponse($response);
	}
	
	

	public static function Breadcrumb() {

		$breads = array();
		$routeName = \Drupal::routeMatch()->getRouteName();
		if ($routeName=="promotion.promotion_content") {
		  $breads[] = [
			  'name' => 'Promotion - Search by name',
		  ];
		} else if ($routeName=="promotion.promotion_rank") {
		  $breads[] = [
			  'name' => 'Promotion - Search by rank and year',
		  ];
		} 
	
		return $breads;
	
	  }

	
}

<?php

/**
 * @file
 */

namespace Drupal\profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\Common\ProfileDatatable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\common\CommonUtil;


class ProfileController extends ControllerBase {

    public function __construct() {
        $this->module = 'profile';
        $this->domain_name = CommonUtil::getSysValue('domain_name');
    }

    public function ProfileCop() {

        $joinedCopInfo = ProfileDatatable::getUserJoinedCopInfo();
        
        return [
            '#theme' => 'profile-cop',
            '#items' => $joinedCopInfo,
            '#empty' => t('No entries available.'),
        ];  

    }

    public function reloadCopJoinMemberTable() {

        $joinedCopInfo = ProfileDatatable::getUserJoinedCopInfo();
        
        $renderable = [
            '#theme' => 'profile-coptable',
            '#items' => $joinedCopInfo,
        ];
        $output = \Drupal::service('renderer')->renderPlain($renderable);
                  
        $response = new Response();
        $response->setContent($output);
        return $response;
    }    

    public function ProfileJoinCopMembership() {
        
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        
        $user_id = $authen->getUserId();
        $cop_id = $_REQUEST['cop_id'];
        $action = $_REQUEST['action'];
        
        $joinedMember = ProfileDatatable::checkUserJoinedCopMember($cop_id, $user_id) ? 'Y' : 'N';
        
        if($action == "add") {
            
            if($joinedMember == "N") {
                $entry = array('cop_id' => $cop_id, 'user_id' => $user_id, 'is_subscribed_forum' => 0, 'cop_join_date' => date('Y-m-d H:i:s') );
                $query = \Drupal::database()->insert('kicp_km_cop_membership')
                ->fields($entry);
                $return_value = $query->execute();
                $joinedMember = 'Y';
            }
            
        }
        
        $response = new Response();
        $response->setContent($joinedMember);
        return $response;
        
    }

    public function ProfileGroupContent() {

        $groups = ProfileDatatable::getGroups();
        $type = \Drupal::request()->query->get('type');
        $groups["type"] = $type;
        $search_str = \Drupal::request()->query->get('search_str');
        $groups['search_str'] =  $search_str;        

        return [
            '#theme' => 'profile-groups',
            '#items' => $groups,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],            
        ];  


    }

    public function ProfileGroupDelete($type="", $group_id="") {

        $err_code = 0;
        try {
            $database = \Drupal::database();

            if ($type=="P") {
                $query = $database->update('kicp_public_group')->fields([
                'is_deleted' => 1,
                ])
                ->condition('pub_group_id', $group_id)
                ->execute();
            } else {
                $query = $database->update('kicp_buddy_group')->fields([
                    'is_deleted' => 1,
                    ])
                    ->condition('buddy_group_id', $group_id)
                    ->execute();
            }

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('Group has been deleted'));
        
                $err_code = 1;
    

    
        }
        catch (\Exception $e) {
            \Drupal::messenger()->addStatus(
                t('Unable to delete group at this time due to datbase error. Please try again. ' )
                );
            }

        $response = array('result' => $err_code);
        return new JsonResponse($response);  


    }


}
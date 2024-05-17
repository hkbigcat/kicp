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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\common\CommonUtil;
use Drupal\Core\Utility\Error;

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
                try {
                    $query = \Drupal::database()->insert('kicp_km_cop_membership')
                    ->fields($entry);
                    $return_value = $query->execute();
                    $joinedMember = 'Y';
                    \Drupal::logger('profile')->info('member added cop id: %id, user_id: %user_id.',   
                    array(
                        '%id' => $cop_id,
                        '%user_id' => $user_id,
                    ));                      
                }  catch (\Exception $e) {
                    $variables = Error::decodeException($e);
                    \Drupal::logger('profile')->error('member is not added '  . $variables);   
                    \Drupal::messenger()->addStatus(
                        t('Unable to add member at this time due to datbase error. Please try again. ' )
                        );
                    $transaction->rollback();    
                }
                unset($transaction);
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
        $database = \Drupal::database();
        $transaction =  $database->startTransaction();         
        try {
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
            \Drupal::logger('profile')->info('group deleted id: %id, type: %type.',   
            array(
                '%id' => $group_id,
                '%type' => $type=='B'?'Buddy':'Public',
            ));  
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Group has been deleted'));
            $err_code = 1;    
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('profile')->error('gorup is not deleted '  . $variables);   
            \Drupal::messenger()->addStatus(
                t('Unable to delete group at this time due to datbase error. Please try again. ' )
                );
            $transaction->rollback();    
            }
        unset($transaction);
        $response = array('result' => $err_code);
        return new JsonResponse($response);  

    }

    public function ProfileGroupMemberContent($type="",$group_id="") {

        if($type == "P") {
            $field_text = "Personal Public Group Name";
            $groupInfo = ProfileDatatable::getPublicGroupByGroupId($group_id);
        } else  {
            $field_text = "Personal Group Name";
            $groupInfo = ProfileDatatable::getBuddyGroupByGroupId($group_id);
        } 

        $members = ProfileDatatable::getMembersGroupId($type,$group_id);
        $members["type"] = $type;
        $members["group_name"] = $groupInfo->group_name;
        $members["group_id"] = $group_id;
        $search_str = \Drupal::request()->query->get('search_str');
        $users = "";
        if ($search_str && $search_str!="") {
            $users = ProfileDatatable::getUsers();
        }
        $members['search_str'] =  $search_str;        

        return [
            '#theme' => 'profile-members',
            '#items' => $members,
            '#search_users' => $users,
            '#empty' => t('No entries available.'),
            '#pager' => ['#type' => 'pager',
            ],            
        ];  

    }

    public function ProfileGroupMemberAddAction($type="", $group_id="", $user_id="") {

        $userInGroup = ProfileDatatable::checkUserInGroup($type, $group_id, $user_id);
        $search_str = \Drupal::request()->query->get('search_str');
        
        if(!$userInGroup) {
            $userInfo = ProfileDatatable::getUserInfoByUserId($user_id);
            
            $database = \Drupal::database();
            $transaction =  $database->startTransaction();   
            try {
                if ($type === "B") {
                    $entry = array(
                        'buddy_group_id' => $group_id,
                        'buddy_user_id' => $user_id,
                        'buddy_user_name' => $userInfo,
                        'is_deleted' => 0,
                        );

                        $query = \Drupal::database()->insert('kicp_buddy_user_list')
                        ->fields($entry);
            
                } else if($type == 'P') {
                    $entry = array(
                        'pub_group_id' => $group_id,
                        'pub_user_id' => $user_id,
                        'pub_user_name' => $userInfo,
                        'is_deleted' => 0,
                        );

                        $query = \Drupal::database()->insert('kicp_public_user_list')
                        ->fields($entry);    
                }
                $return_value = $query->execute();
                \Drupal::logger('profile')->info('added member in group id: %id, type: %type, user_id: %user_id',   
                array(
                    '%id' => $group_id,
                    '%type' => $type=='B'?'Buddy':'Public',
                    '%user_id' => $user_id,
                ));   

                $messenger = \Drupal::messenger(); 
                $messenger->addMessage( t('User has been added.'.$return_value));
            } catch (\Exception $e) {
                $variables = Error::decodeException($e);
                \Drupal::logger('profile')->error('member is not added '  . $variables);   
                \Drupal::messenger()->addStatus(
                    t('Unable to add member at this time due to datbase error. Please try again. ' )
                    );
                $transaction->rollback();    
                }
            unset($transaction);

            $url = Url::fromUserInput('/profile_group_member/'.$type.'/'.$group_id.'?search_str='.$search_str);
            return new RedirectResponse($url->toString());  

        }

    }

    public function ProfileGroupMemberDelete($type="",$group_id="", $user_id="") {

        $err_code = 0;
        $database = \Drupal::database();
        $transaction =  $database->startTransaction();   
        try {
            if ($type=="P") {
                $query = $database->update('kicp_public_user_list')->fields([
                    'is_deleted' => 1,
                    ])
                    ->condition('pub_group_id', $group_id)
                    ->condition('pub_user_id', $user_id)
                    ->execute();
            } else {
                    $query = $database->update('kicp_buddy_user_list')->fields([
                        'is_deleted' => 1,
                        ])
                    ->condition('buddy_group_id', $group_id)
                    ->condition('buddy_user_id', $user_id)
                    ->execute();
            }
            \Drupal::logger('profile')->info('member deleted in group id: %id, type: %type, user_id: %user_id',   
            array(
                '%id' => $group_id,
                '%type' => $type=='B'?'Buddy':'Public',
                '%user_id' => $user_id,
            ));              
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Member has been deleted'));
            $err_code = 1;
    
        }
        catch (\Exception $e) {
            $variables = Error::decodeException($e);
            \Drupal::logger('profile')->error('member is not deleted '  . $variables);   
            \Drupal::messenger()->addStatus(
                t('Unable to delete member at this time due to datbase error. Please try again. ' )
                );
            $transaction->rollback();    
            }
        unset($transaction);            

        $response = array('result' => $err_code);
        return new JsonResponse($response);  

    }



}
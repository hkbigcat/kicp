<?php

//using by Henry
/**
 * @file
 */

namespace Drupal\mainpage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal;
use Drupal\common\LikeItem;
use Drupal\common\TagList;
use Drupal\common\Controller\CommonController;
use Drupal\common\CommonUtil;
use Drupal\common\Follow;
use Drupal\mainpage\Common\MainpageDatatable;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MainpageController extends ControllerBase {

    public function __construct() {
        $AuthClass = "\Drupal\common\Authentication";
        $authen = new $AuthClass();
        $this->is_authen = $authen->isAuthenticated;
        $this->my_user_id = $authen->getUserId();        
        $this->module = 'mainpage';
        $this->record_in_mainpage = 12;
        $this->tag_usage = 3;
        $this->showRating = array('bookmark', 'video', 'fileshare');
        $this->showLike = array('blog', 'activities', 'ppcactivities', 'survey', 'vote', 'forum', 'wiki');
        $this->showFollowIcon = array('bookmark', 'blog', 'video', 'fileshare', 'activities', 'ppcactivities', 'survey',' vote', 'forum');
    }

    public function contentByView() {

        $url = Url::fromRoute('<front>')->toString();
        if (! $this->is_authen) {
            return new RedirectResponse($url.'no_access');
        }
       
        $myRecordOnly = \Drupal::request()->query->get('my');
        $myfollowed = \Drupal::request()->query->get('my_follow');
        $taglist = new TagList();
        $cop_tags = $taglist->getCOPTagList();
        $other_tags = $taglist->getOtherTagList();
        $editorChoice = MainpageDatatable::getEditorChoiceRecord();           
        $latest = MainpageDatatable::getLatest($this->my_user_id);
        $myFollower = Follow::getMyFollower($this->my_user_id);
        $myFollowing = Follow::getMyFollowering($this->my_user_id);

        return [
            '#theme' => 'mainpage-home',
            '#editor_choice' => $editorChoice,
            '#items' => $latest,
            '#cop_tags' => $cop_tags,
            '#other_tags' => $other_tags,
            '#my_user_id' => $this->my_user_id,
            '#followers'=> $myFollower,
            '#myRecordOnly' => $myRecordOnly,
            '#myfollowed' =>  $myfollowed,
            '#myFollowing'=>  $myFollowing,
        ];  
    }


    public function GeneralTagContent() {

        $url = Url::fromRoute('<front>')->toString();
        if (! $this->is_authen) {
            return new RedirectResponse($url.'no_access');
        }

        $tags = array();  
        $tagsUrl = \Drupal::request()->query->get('tags');
    
        if ($tagsUrl) {
          $tags = json_decode($tagsUrl);
          if ($tags && count($tags) > 0 ) {
            $tmp = $tags;
          }
        }

        $latest = MainpageDatatable::getLatest($this->my_user_id,$tags);


        return [
            '#theme' => 'mainpage-tags',
            '#items' => $latest,
            '#tags' => $tags,
            '#tagsUrl' => $tmp,
            '#pager' => ['#type' => 'pager',
                        ],
        ];
        
    }

    public function getFollow() {

        $request = \Drupal::request();   // Request from ajax call
        $content = $request->getContent();
        $params = array();
        if (!empty($content)) {
            $params = json_decode($content, TRUE);  // Decode json input
        }
        $choices = $params['choices'];

        if ($choices=="following") {
            $follower = Follow::getMyFollowerList($this->my_user_id);
        } else {
            $follower = Follow::getFolloweringList($this->my_user_id);
        }
        $renderable = [
            '#theme' => 'mainpage-follow-table',                
            '#items' => $follower,
            '#my_user_id' => $this->my_user_id,
            '#choices' => $choices,
        ];
        $content = \Drupal::service('renderer')->renderPlain($renderable);        
        $response = array($content);
        return new JsonResponse($response);   
    }

    public function getFollowNo() {
        $myFollowing = Follow::getMyFollowering($this->my_user_id);
        $response = array($myFollowing);
        return new JsonResponse($response);                  

    }

    public function getDisclaimerMessage() {
        
        $output = "<div id='disclaimer'>
            <p>Owner of Knowledge & Information Collaborative Platform (the <span class='disclaimer_subtitle'>\"KICP\"</span>) has created this Acceptable Use Policy (the <span class='disclaimer_subtitle'>\"AUP\"</span>) so you (the <span class='disclaimer_subtitle'>\"User\"</span>) will understand when and under what circumstances we may suspend or terminate your use of our service (the <span class='disclaimer_subtitle'>\"Service\"</span>) and access to KICP containing information and data available. By using our Service or accessing the KICP, you consent to the acceptable use practices described in this AUP, as modified from time to time by us.</p>

<p><span class='disclaimer_title'>Content prohibitions</span>

<p>KICP's goal is for all of you to have an enjoyable experience every time you access the KICP. Therefore, you may only use the Service for lawful purposes and in a manner that does not interfere with other users of the Service. To assure this, we reserve the right, but are not obligated, to suspend or terminate your access to the KICP and/or your use of the Service at any time, if we determine in our sole discretion that your conduct or behavior on the KICP or our Service involves or causes but not limited to:</p>

<p>
<ul>
<li><span class='disclaimer_subtitle'>Content Harmful or Offensive to Third-Parties:</span> User is prohibited to upload, download, post, distribute, publish, or otherwise transmit (collectively, <span class='disclaimer_subtitle'>\"Disclose\"</span>) any message, data, information, image, text or other material (collectively, <span class='disclaimer_subtitle'>\"Content\"</span>) that is unlawful, defamatory, obscene, indecent, harassing, threatening, harmful, invasive of privacy or publicity rights, abusive, inflammatory or otherwise harmful or offensive to third parties;</li>

<li><span class='disclaimer_subtitle'>Infringing Content:</span> User is prohibited to Disclose any Content that may infringe any patent, trademark, trade secret, copyright or other intellectual or proprietary right of any party. Infringement may result from the unauthorized copying and posting or distributing ringtones, graphics, pictures, photographs, logos, software, articles, music, or videos. By posting any Content, you represent and warrant that you have the lawful right to distribute and reproduce such Content; </li>

<li><span class='disclaimer_subtitle'>Impersonation:</span> User is prohibited to impersonate any person or entity or otherwise misrepresent your affiliation with a person or entity; </li>

<li><span class='disclaimer_subtitle'>Interference:</span> User is prohibited to do anything to interfere with other users of the Service; </li>

<li><span class='disclaimer_subtitle'>Deceptive Content or Spam:</span> When using email or chat services, User is prohibited to Disclose any deceptive Content -- such as communications offering or disseminating fraudulent goods, services, schemes or promotions -- or any form of unsolicited commercial email or \"spam\" (electronic messages sent to multiple email addresses with nearly identical contents). </li>

<li><span class='disclaimer_subtitle'>Unapproved Promotions or Advertising of Goods or Services:</span> Without our prior written permission, User is prohibited to Disclose on the Sites any unsolicited promotions of goods or services, commercial solicitation or any advertising, promotional materials or any other solicitation of other users for goods or services except in those areas (e.g., a classified bulletin board) that are designated for such purpose; </li>

<li><span class='disclaimer_subtitle'>Off Topic Content:</span> User is prohibited to Disclose any Content that is off-topic according to the description of a group or list or send unsolicited mass emailing if such Content could reasonably be expected to provoke complaints from its recipients; </li>
</ul>

</p>

<p><span class='disclaimer_title'>Owner of KICP limited responsibility</span></p>

<p>Owner of KICP takes no responsibility and assumes no liability for any Content uploaded, transmitted, or downloaded by User or any third-party, or for any mistakes, defamation, omissions, falsehoods, obscenity, pornography or profanity you may encounter. As KICP is only a platform, owner of KICP is not liable for any statements, representations, or Content provided by our users. It is not our intent to discourage you from taking controversial positions or expressing vigorously what may be unpopular views; however, we reserve the right to take such action as we deem appropriate in cases where the KICP or Service is used to disseminate statements which are offensive or harmful. </p>

<p><span class='disclaimer_title'>Enforcement of this AUP</span></p>

<p>Owner of KICP reserves the right, but does not assume the obligation, to strictly enforce this AUP by, but not limited to, issuing warnings, suspending or terminating Service, refusing to transmit, removing, screening or editing Content or actively investigating violations and prosecuting them in appropriate venue. </p>

<p>Owner of KICP reserves the right to replace the inappropriate words with the words we believed to be appropriate and not harmful to other users.</p>

<p>We may access, use and disclose transaction information about your use of our Service, and any Content transmitted by User via the KICP or through the Service, to the extent permitted by law, in order to comply with the law; to protect our rights or property, or to protect users of our Services from fraudulent, abusive, or unlawful use of, or subscription to, our Service. </p>

<p><span class='disclaimer_title'>Unsolicited email</span></p>

<p>We will send you emails occasionally to notify you of any updates in KICP.  While we do believe the updates are beneficial and useful to all User(s), you may not think so.  If you do not wish to receive further promotional email messages from us, please reply an email (email account can be found in paragraph \"Contact us\") to us with either one of the following statements:</p>

<p>
<ul>
<li>\"Please do not send any further promotional email messages from OGCIO to the electronic address which sends out this message.\"</li>
<li>\"Please do not send any further email messages from OGCIO to the electronic address which sends out this message on matters related to the subject heading of this email.\"</li>
</ul> 
</p>

<p><span class='disclaimer_title'>Security and privacy policy</span></p>

<p>All of the personal information collected from you in KICP is stored in an intranet environment behind a \"firewall\" and defenses have been erected to seek to enhance the protection of your information from outside attack by hackers and curious visitors.</p>

<p>KICP is committed to ensuring that all personal data provided under various circumstances are handled in accordance with the relevant provisions of the Personal Data (Privacy) Ordinance. All electronic storage and transmission of the personal data provided are secured with appropriate security technologies.</p>

<p><span class='disclaimer_title'>User responsibility</span></p>

<p>As KICP is only a platform, User shall not Disclose classified information, including restricted and confidential information, through KICP, even though security measures have been in place to protect the information from attempts of attack by hackers and curious visitors.</p>

<p>User is also liable for any Content you Disclose. You agree to indemnify and hold us harmless from any claim, action, demand, loss, or damage (including professional legal fees) made or incurred by any third-party arising out of or relating to your violation of this AUP. </p>

<p><span class='disclaimer_title'>Updating this AUP </span></p>

<p>We will revise and update this AUP if our practices change, as technology changes, or as we add new services or change existing ones. If we make any material changes to this AUP, we will change the date of the AUP such that user could refer to the latest information. </p>

<p><span class='disclaimer_title'>Contact us </span></p>

<p>If you have any questions, comments or concerns about this AUP, please write to us at: <br>
KICP Administrator/OGCIO/HKSARG for Lotus Notes users or <br>
kicpadm@ogcio.gov.hk for Internet mail users.</p>

</div>

";
        
        return $output;
        
    }    

    public function askDisclaimerAccept() {
        
            
        // can skip this code
        global $skipDisclaimerCheck, $_SESSION;
        $skipDisclaimerCheck = true;
        
        //$AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        //$authen = new $AuthClass();
        //$my_user_id = $authen->getUserId();
        
        $target = (isset($_REQUEST['target']) && $_REQUEST['target']  != "") ? $_REQUEST['target'] : "";
        
        
        $outputMessage = self::getDisclaimerMessage();
        
        $outputMessage .= "<div style='width:100%;text-align:center;'><form method='post' action='go_accept_disclaimer'><input type='submit' value='Accept'> <input type='button' value='Decline' onClick=self.location.href=\"/\"> <input type='hidden' name='target' value='".$target."'></form></div>";
        
        

        
        return array(
          '#type' => 'markup',
          '#markup' => $this->t($outputMessage),
        );
    }

    public function getDisclaimer() {
        
        $AuthClass = CommonUtil::getSysValue('AuthClass'); // get the Authentication class name from database
        $authen = new $AuthClass();
        
        $outputMessage = self::getDisclaimerMessage();
        
        $outputMessage .= "<div style='width:100%;text-align:center;'><form><input type='button' value='Close' onClick='self.close();'></form></div>";
        
        return array(
          '#type' => 'markup',
          '#markup' => $this->t($outputMessage),
        );
    }    

    public function goAcceptDisclaimer() {
        
        // can skip this code
        global $skipDisclaimerCheck, $_SESSION;
        $skipDisclaimerCheck = true;
                        
        $result = MainpageDatatable::acceptDisclaimer($this->my_user_id);
       
        $target = (isset($_REQUEST['target']) && $_REQUEST['target'] != "") ? $_REQUEST['target'] : "main";
              
        $response = new RedirectResponse(urldecode($target));
        $response->send();
        return true;

        //CommonUtil::goToUrl($target);
    }


    public static function Breadcrumb() {

        $breads = array();
        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($routeName=="mainpage.mainpage_tag") {
            $breads[] = [
                'name' => 'Tags', 
            ];
        } else if ($routeName=="mainpage.mainpage_tag") {
            $breads[] = [
                'name' => 'Tags', 
            ];
        } else if ($routeName=="mainpage.mainpage_disclaimer") {
            $breads[] = [
                'name' => 'Acceptable Use Policy', 
            ];
        } 

        return $breads;

    }


}
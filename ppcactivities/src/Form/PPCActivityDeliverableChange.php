<?php

/**
 * @file
 */

namespace Drupal\ppcactivities\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal;
use Drupal\Component\Utility\UrlHelper;
use Drupal\common\RatingData;
use Drupal\common\Controller\TagList;
use Drupal\common\Controller\TagStorage;
use Drupal\common\CommonUtil;
use Drupal\ppcactivities\Common\PPCActivitiesDatatable;


class PPCActivityDeliverableChange extends FormBase {

    public function __construct() {
        $this->module = 'ppcactivities';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'ppc_km_activities_deliverable_change_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $evt_deliverable_id="") {

        // display the form

        
        $form['#attributes']['enctype'] = 'multipart/form-data';
        
        $deliverableInfo = PPCActivitiesDatatable::getEventDeliverableInfo($evt_deliverable_id);
        
        $form['evt_deliverable_url'] = array(
            '#title' => t('Deliverable Name.'),
            '#type' => 'textfield',
            '#default_value' => $deliverableInfo->evt_deliverable_url,
            '#attributes' => array('disabled'=>'disabled'),
        );
        
        $form['evt_deliverable_name'] = array(
            '#title' => t('Description'),
            '#type' => 'textfield',
            '#default_value' => $deliverableInfo->evt_deliverable_name,
            '#size' => 40,
            '#maxlength' => 250,
        );
        
        $form['evt_deliverable_name_prev'] = array(
          '#type' => 'hidden',
          '#value' => $deliverableInfo->evt_deliverable_name,
        );


        $TagList = new TagList();
        $tags = $TagList->getTagListByRecordId('ppcactivities_deliverable', $evt_deliverable_id);

        $form['tags'] = array(
            '#title' => t('Tags'),
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => 'Use semi-colon (;) as separator',
            '#default_value' => implode(";", $tags),
        );

        $form['tags_prev'] = array(
            '#type' => 'hidden',
            '#value' =>  implode(";", $tags),
          );

        
        $form['evt_deliverable_id'] = array(
            '#type' => 'hidden',
            '#value' => $evt_deliverable_id,
        );

        $form['evt_id'] = array(
            '#type' => 'hidden',
            '#value' => $deliverableInfo->evt_id,
        );
       

        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );

        $form['actions']['cancel'] = array(
            '#type' => 'button',
            '#value' => t('Cancel'),
            '#prefix' => '&nbsp;',
            '#attributes' => array('onClick' => 'window.open(\'../ppcactivities_deliverable/' . $deliverableInfo->evt_id . '\', \'_self\'); return false;'),
            '#limit_validation_errors' => array(),
        );

        $taglist = $TagList->getListCopTagForModule();
        $form['t3'] = array(
            '#title' => t('COP Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
            '#attributes' => array('style'=>'border: 1px solid #7A7A7A;background: #FCFCE6; margin-top:40px;'),
        );

          
        $taglist = $TagList->getList($this->module);
        $form['t1'] = array(
            '#title' => t('PPC Activities Tags'),
            '#type' => 'details',
            '#open' => true,
            '#description' =>  $taglist,
        );

        $taglist = $TagList->getList('ALL');
        $form['t2'] = array(
            '#title' => t('All Tags'),
            '#type' => 'details',
            '#open' => false,
            '#description' => $taglist,
        );     

        return $form;
    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    //----------------------------------------------------------------------------------------------------
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            $$key = $value;
        }


        $deliverableInfo = PPCActivitiesDatatable::getEventDeliverableInfo($evt_deliverable_id);
        $database = \Drupal::database();
        $transaction = $database->startTransaction();           
        try {
            // if changes in content, update timestamp
            if($evt_deliverable_name != $evt_deliverable_name_prev) {
                $query = $database->update('kicp_ppc_event_deliverable')->fields([
                  'evt_deliverable_name' => $evt_deliverable_name, 
                  'modify_datetime' =>  date('Y-m-d H:i:s'),
                ])
                ->condition('evt_deliverable_id', $evt_deliverable_id)
                ->execute();
            }

            if ($tags != $tags_prev) {
                // rewrite tags
                if ($tags_prev != '') {
                    $query = $database->update('kicp_tags')->fields([
                        'is_deleted'=>1 , 
                        ])
                        ->condition('fid', $evt_deliverable_id)
                        ->condition('module', 'ppcactivities_deliverable')
                        ->execute();                
                }
                if ($tags != '') {
                    $entry1 = array(
                        'module' => 'ppcactivities_deliverable',
                        'module_entry_id' => intval($evt_deliverable_id),
                        'tags' => $tags,
                        );
                        $return1 = TagStorage::insert($entry1);                
                }
                }

            \Drupal::logger('ppcactivities')->info('Event ID: '.$evt_id.' Event deliverable updated id: '.$evt_deliverable_id);
            $url = Url::fromUri('base://ppcactivities_deliverable/'.$evt_id);
            $form_state->setRedirectUrl($url);
    
            $messenger = \Drupal::messenger(); 
            $messenger->addMessage( t('Deliverable has been updated.'));
            

        } catch (Exception $ex) {
            $variables = Error::decodeException($e);
            \Drupal::messenger()->addError(
                t('PPC Activity Event deliverables information is not updated.' )
                );
            \Drupal::logger('ppcactivities')->error('Activity Event deliverables information is not updated.: '.$variables);                    
            $transaction->rollBack();   
        }
        unset($transaction); 
    }

}

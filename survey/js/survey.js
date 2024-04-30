/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function getFolderTags(val) {
    // "Nil" folder is selected
    if (val == 0) {

        jQuery("#edit-tags").val("");

    } else {

        jQuery.ajax({
            type: "POST",
            url: 'survey_folder_used_tag',
            data: {
                folder_id: val
            },
            success: function (data)
            {
                jQuery("#edit-tags").val(data);
            }
        });

    }
}

function getAllEventItem(val) {

    jQuery.ajax({
        type: "POST",
        url: 'video_get_event_select',
        data: {
            evt_type: val
        },
        success: function (option_data)
        {
            //jQuery("#div_evt_cop_id").html(data);
            var select = jQuery('#edit-eid');
            select.empty().append(option_data);
        },
        error: function ()
        {
            var select = jQuery('#edit-eid');
            select.empty();
        }
    });
}



function showDiv(val) {


    var RadioCheckbox = document.getElementById("RadioCheckbox");
    var Rate = document.getElementById("Rate");
    if (val == '4' || val == '5') {
        RadioCheckbox.style.display = "block";
        Rate.style.display = "none";
    } else if ( val == '6' ){
     RadioCheckbox.style.display = "block";
     Rate.style.display = "block";
 }
    else {
        RadioCheckbox.style.display = "none";
         Rate.style.display = "none";
    }
}



function sequence(oldVal, newVal, objID) {

    var seqList = document.getElementsByTagName('select');

    for (var i = 1; i <= seqList.length; i++) {
        if (seqList[i].value == newVal) {
            seqList[i].value = oldVal;
            jQuery("#hiddenorder" + i).val(oldVal);
            break;
        }
    }
}
function changeSequence(objId){
    var previousSeq = jQuery("#hiddenseq").val();
    var newSeq = jQuery("#"+objId).val();
    var seqList = document.getElementsByTagName('select');

    for (var i = 0; i < seqList.length; i++) {
        if (seqList[i].value == newSeq && seqList[i].id != objId) {
            seqList[i].value = previousSeq;
            jQuery("#hiddenseq").val(newSeq);
            break;
        }
    }
}
function setPreviousSequence(seqSelect){
    jQuery("#hiddenseq").val(seqSelect.value);
}

function disableEditorFormat(){
    var editorList = jQuery('select.editor');
    for (var i = 0; i < editorList.length; i++) {
        var editorOptions = jQuery(editorList[i]).find('option');
        for(var j =0; j < editorOptions.length; j++){
            if (editorOptions[j].value != "full_html_survey") {
               jQuery(editorOptions[j]).attr('disabled', 'disabled');
           }           
        }
    }
}

function resetSurvey(){
    if( !confirm('Are you sure that you want to RESET survey?') ){
        event.preventDefault();
    } else{
        jQuery('input[type="radio"]').each(function(){
            jQuery(this).removeAttr("checked");
        });
        jQuery('input[type="checkbox"]').each(function(){
            jQuery(this).removeAttr("checked");
        });
        jQuery('input[type="text"]').each(function(){
            jQuery(this).val("");
        });
        if( typeof(CKEDITOR) !== "undefined" ){
            for ( instance in CKEDITOR.instances ){
                CKEDITOR.instances[instance].setData('');
            }
        }
        jQuery('select.form-select').each(function(){
            jQuery(this).find("option").removeAttr("selected");
        });
        event.preventDefault();     
    }
}

function addRateLine(tableName){
    var totalRow = jQuery("#" + tableName + " tbody tr").length;
    var newIndex = totalRow+1;
    //var lastRow = jQuery("#" + divName + " tbody tr:last").innerHTML;
    var newRow = '<tr><td align=\"center\"><div class=\"js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-ratescale'+ newIndex +' form-item-ratescale'+ newIndex +' form-no-label\">\n'+
            '<input name=\"rateScale'+ newIndex +'\" class=\"form-text\" id=\"edit-ratescale'+ newIndex +'\" type=\"text\" size=\"50\" maxlength=\"128\" value=\"\" data-drupal-selector=\"edit-ratescale'+ newIndex +'\" onchange="updateRate(this,'+ newIndex +')">\n\n     </div>\n</td>'+   //'+ newIndex +'
            '<td align=\"center\"><div class=\"js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-ratelegend'+ newIndex +' form-item-ratelegend'+ newIndex +' form-no-label\">\n    '+    
            '<input name=\"rateLegend'+ newIndex +'\" class=\"form-text\" id=\"edit-ratelegend'+ newIndex +'\" type=\"text\" size=\"50\" maxlength=\"128\" value=\"\" data-drupal-selector=\"edit-ratelegend'+ newIndex +'\" onchange="updateRate(this,'+ newIndex +')">\n\n        </div>\n</td>'+
            '<td align=\"center\"><div class=\"js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-rateposition'+ newIndex +' form-item-rateposition'+ newIndex +' form-no-label\">\n    '+    
            '<input name=\"ratePosition'+ newIndex +'\" class=\"form-text\" id=\"edit-rateposition'+ newIndex +'\" type=\"text\" size=\"50\" maxlength=\"128\" value=\"\" data-drupal-selector=\"edit-rateposition'+ newIndex +'\" onchange="updateRate(this,'+ newIndex +')">\n\n        </div>\n</td>'+
            '</td></tr>';
    jQuery("#" + tableName + " tbody").append(newRow);
    event.preventDefault(); 
}

var newRatesValue = [];
function updateRate(txtbox, newIndex){
    var txtScale = jQuery.trim(jQuery("#edit-ratescale"+newIndex).val());
    var txtLegend = jQuery.trim(jQuery("#edit-ratelegend"+newIndex).val());
    var txtPosition = jQuery.trim(jQuery("#edit-rateposition"+newIndex).val());
    
    newRatesValue[newIndex] = {scale:txtScale, legend:txtLegend, position:txtPosition};    
    jQuery("#hiddenRates").val(JSON.stringify(newRatesValue));    
}

function addAnswerLine(divName){
    var totalRow = jQuery("#" + divName + " div.js-form-item.form-item.js-form-type-textfield.form-type-textfield").length
    var newIndex = totalRow+1;
    var newRow = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-title'+ newIndex +' form-item-title'+ newIndex +'">'+
                '\n      <label for=\"edit-title1\">'+ newIndex +'.</label>\n        '+
                '<input name=\"title'+ newIndex +'\" class=\"form-text\" id=\"edit-title'+ newIndex +'\" type=\"text\" size=\"90\" maxlength=\"500\" value=\"\" data-drupal-selector=\"edit-title'+ newIndex +'\"  onchange="updateAnswer(this,'+ newIndex +')">\n\n        '+
                '</div>';
    var totalRow = jQuery("#" + divName + " div.div_inline_column").prev().append(newRow);
    event.preventDefault();  
}
var newAnswerValue = [];
function updateAnswer(txtbox, newIndex){
    var txtAns = jQuery.trim(jQuery(txtbox).val());
    newAnswerValue[newIndex] = txtAns;   
    jQuery("#hiddenChoices").val(JSON.stringify(newAnswerValue));    
}

function clearAnswerLine(divName){
    jQuery("#"+divName+' input[type="text"].form-text').each(function(){
        jQuery(this).val("");
    });
    event.preventDefault();  
}

/* Ben 2023-06-29*/
/*
function clearRadio(radioName){
    jQuery('input:radio[name="' + radioName +'"]').each(function(){
        jQuery(this).removeAttr("checked");
    });
}
*/


function clearRadio(radioName){
   let ans = document.getElementsByName(radioName);
   for(var i=0;i<ans.length;i++)
        ans[i].checked = false;
   return true;
}


/* Ben 2023-06-29*/
/*
function clearRadioOther(radioName){
    jQuery("#"+radioName).val("");
}
*/

function clearRadioOther(radioName){
    var other = document.getElementsByName(radioName);
    other[0].value='';
}


function submitSurvey(){
    if( !confirm('Are you sure that you want to SUBMIT survey?') ){
        event.preventDefault();
    } else{
        jQuery("#hiddenSubmit").val(1);
    }               
}

function saveSurvey() {
    jQuery("#hiddenSubmit").val(2);
}

function EnterSurvey(name,post,dept){
    var msg ="The following information will be collected in this survey\n";
      if (name == 1) {msg=msg+"\n Voter's name";}
        if (post == 1) {msg=msg+"\n Post unit";}
        if (dept== 1) {msg=msg+"\n Department";}
        msg= msg+"\n\nDo you want to continue?";
    if( !confirm(msg) ){
        window.location.href="survey";
    }
    else
    {
    event.preventDefault();
    }
}
function printDiv(divName){
    //var printContents = document.getElementById(divName).innerHTML;
    //var originalContents = document.body.innerHTML;
     //document.body.innerHTML = printContents;
     document.title='result';
     window.print();
     //document.body.innerHTML = originalContents;
     event.preventDefault();  
}

jQuery(document).ready(function ($) {
    jQuery("li.menu-item.menu-item-level-1:nth-child(9)").addClass("active");
  })
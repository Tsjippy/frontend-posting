<?php
namespace TSJIPPY\FRONTENDPOSTING;
use TSJIPPY;
use TSJIPPY\ADMIN;

use function TSJIPPY\addElement;
use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        global $wp_roles;

        //Get all available roles
        $userRoles	= $wp_roles->role_names;
        
        //Sort the roles
        asort($userRoles);

        if(!isset($this->settings['content-manager-roles'])){
            $this->settings['content-manager-roles']	= [];
        }

        if(!isset($this->settings['pending-channels']) || !is_array($this->settings['pending-channels'])){
            $this->settings['pending-channels']	= [];
        }

        addElement('label', $parent, [], 'Content manager role(s)');

        $select = addElement('select', $parent, ['name' => 'content-manager-roles[]', 'multiple' => 'multiple']);

        foreach($userRoles as $key => $role){

            $attributes = ['value' => $key];
            if(in_array($key, $this->settings['content-manager-roles'])){
                $attributes['selected']	= 'selected';
            }

            addElement('option', $select, $attributes, $role);
        }

        addElement('br', $parent);

        $this->recurrenceSelector("page-age-reminder", $this->settings['page-age-reminder'], 'How often should people be reminded of content which should be updated?', $parent);

        addElement('br', $parent);

        addElement('label', $parent, [], 'What should be the max time in months for a page without any changes?');

        $select = addElement('select', $parent, ['name' => 'max-page-age']);

        for ($x = 0; $x <= 12; $x++) {
            $attributes = ['value' => $x];
            if($this->settings['max-page-age'] === strval($x)){
                $attributes['selected'] = "selected";
            }

            addElement('option', $select, $attributes, $x);
        }

        addElement('br', $parent);

        $label = addElement('label', $parent, [], 'Post status after expiry');

        addElement('br', $label);

        $label1 = addElement('label', $label, [], 'Trashed');

        $attributes = [
            'type'  => 'radio',
            'name'  => 'expired-post-type',
            'id'    => 'expired-post-type',
            'value' => 'trash'
        ];
        if($this->settings['expired-post-type'] == 'trash'){
            $attributes['checked'] = "checked";   
        }

        addElement('input', $label1, $attributes, '', 'afterBegin');

        addElement('br', $parent);
        addElement('br', $label);

        $label2 = addElement('label', $label, [], 'Archived');
        $attributes['value']    = 'archived';
        
        if($this->settings['expired-post-type'] == 'archived'){
            $attributes['checked'] = "checked";
        }

        addElement('input', $label2, $attributes, '', 'afterBegin');

        return true;
    }

    public function emails($parent){
        $tab      = 'post-out-of-date-email';
        if(isset($_GET['second-tab'])){
            $tab  = sanitize_key($_GET['second-tab']);
        }

        ob_start();
        ?>
        <div class="tablink-wrapper">
            <button type="button" class="tablink <?php echo $tab == 'post-out-of-date-email' ? 'active' : '';?>" id="show-post-out-of-date-email" data-target="post-out-of-date-email">
                Post out of date e-mail
            </button>
            <button type="button" class="tablink <?php echo $tab == 'post-out-of-date-emails' ? 'active' : '';?>" id="show-post-out-of-date-emails" data-target="post-out-of-date-emails">
                Multiple posts out of date e-mail
            </button>
            <button type="button" class="tablink <?php echo $tab == 'pending-post-email' ? 'active' : '';?>" id="show-pending-post-email" data-target="pending-post-email">
                Pending post e-mail
            </button>
            <button type="button" class="tablink <?php echo $tab == 'post-approved-email' ? 'active' : '';?>" id="show-post-approved-email" data-target="post-approved-email">
                Post Approved e-mail
            </button>
        </div>

        <div id="post-out-of-date-email" class="tabcontent <?php echo $tab != 'post-out-of-date-email' ? 'hidden' : '';?>">        
            <h4>E-mail send to people when a page is out of date</h4>
            <label>
                Define the e-mail people get when they are responsible for a page which is out of date.<br>
                You can use placeholders in your inputs.<br>
                These ones are available (click on any of them to copy):
            </label>
            <?php
            $email    = new PostOutOfDateEmail(wp_get_current_user());
            $email->printPlaceholders();
            $email->printInputs();
            ?>
        </div>

        <div id="post-out-of-date-emails" class="tabcontent <?php echo $tab != 'post-out-of-date-emails' ? 'hidden' : '';?>">  
            <label>
                Define the e-mail people get when they are responsible for multiple pages which is out of date.<br>
                You can use placeholders in your inputs.<br>
                These ones are available (click on any of them to copy):
            </label>
            <?php
            $email    = new PostOutOfDateEmails(wp_get_current_user());
            $email->printPlaceholders();
            $email->printInputs();
            ?>
        </div>

        <div id="pending-post-email" class="tabcontent <?php echo $tab != 'pending-post-email' ? 'hidden' : '';?>">  
            <h4>E-mail send to content managers when a post is pending</h4>
            <label>
                Define the e-mail content managers get when someone has submitted a post or post update for review<br>
            </label>
            <?php
            $email    = new PendingPostEmail(wp_get_current_user());
            $email->printPlaceholders();
            $email->printInputs();
            ?>
        </div>

        <div id="post-approved-email" class="tabcontent <?php echo $tab != 'post-approved-email' ? 'hidden' : '';?>"> 
            <h4>E-mail send to authors when their content is approved</h4>
            <label>
                Define the e-mail authors when their post is approved<br>
            </label>
            <?php
            $email    = new ApprovedPostMail(wp_get_current_user());
            $email->printPlaceholders();
            $email->printInputs();
            ?>
        </div>
        <?php

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){
        

        return false;
    }

    public function functions($parent){
        

        return false;
    }

    public function postActions(){
        

        return '';
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();

        return true;
    }
}
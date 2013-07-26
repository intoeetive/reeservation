<?php

/*
=====================================================
 rEEservation
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011 Yuriy Salimovskiy
=====================================================
 This software is based upon and derived from
 ExpressionEngine software protected under
 copyright dated 2004 - 2010. Please see
 http://expressionengine.com/docs/license.html
=====================================================
 File: upd.reeservation.php
-----------------------------------------------------
 Purpose: Booking / reservation engine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}



class Reeservation_upd {

    var $version = '2.5';
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        $this->EE->lang->loadfile('reeservation');  
    } 
    
    function install() { 
        
        $this->EE->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}
        
        if ($this->EE->db->field_exists('cp_notification', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('cp_notification' => array('type' => 'VARCHAR(10)') ) );
		}
        
        $sql[] = "CREATE TABLE IF NOT EXISTS `exp_reeservation` (
            `booking_id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `entry_id` INT( 10 ) NOT NULL ,
            `owner_id` INT( 10 ) NOT NULL ,
            `member_id` INT( 10 ) NOT NULL ,
            `places` INT( 10 ) NOT NULL,
            `name` VARCHAR( 100 ) NOT NULL ,
            `email` VARCHAR( 100 ) NOT NULL ,
            `phone` VARCHAR( 50 ) NULL ,
            `contact` TINYTEXT NULL ,
            `comment` TEXT NULL ,
            `date_from` INT( 10 ) NOT NULL ,
            `date_to` INT( 10 ) NOT NULL ,
            `range_id` VARCHAR(10) NOT NULL ,
            `status` VARCHAR( 50 ) NOT NULL ,
            `booking_date` INT( 10 ) NOT NULL ,
            `moderation_date` INT( 10 ) NULL ,
            `moderator_id` INT( 10 ) NULL ,
            `moderation_comment` TEXT NULL,
            INDEX ( `entry_id` , `member_id` )
            )";
            
        $sql[] = "CREATE TABLE `exp_reeservation_range_dates` (
                `range_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `range_title` VARCHAR(255),
                `date_from` VARCHAR(10),
                `date_to` VARCHAR(10),
                `dow_from` VARCHAR(10),
                `dow_to` VARCHAR(10),
                `time_from` VARCHAR(10),
                `time_to` VARCHAR(10)
                )"; 
                
        $sql[] = "CREATE TABLE `exp_reeservation_range_entries` (
            `entry_id` INT NULL ,
            `range_id` INT NULL ,
            `price` VARCHAR(10),
                INDEX (  `entry_id` ),
                INDEX (  `range_id` )
            )";
                
        foreach ($sql as $qstr)
        {
            $this->EE->db->query($qstr);
        }
        
        $settings = array();
        $settings['default_status']='open';
        $settings['booking_channels']='';
        $settings['booking_entries']='';
        $settings['allow_multiple_bookings']='n';
        $settings['bookings_limit']='1';
        $settings['limit_field']='';
        
        $settings['lock_timeout']='0';//15
        $settings['standard_checkin_time']='06:00 AM';
        $settings['use_time']='n';
        $settings['use_captcha']='n';
        $settings['redirect_timeout']='0';
        $settings['notification_email']=$this->EE->config->item('webmaster_email');
        $settings['send_user_notification']='y';
        $settings['send_owner_notification']='y';
        $settings['send_admin_notification']='y';

        $data = array( 'module_name' => 'Reeservation' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'y', 'settings'=> serialize($settings) ); 
        $this->EE->db->insert('modules', $data); 
        
        $data = array( 'class' => 'Reeservation' , 'method' => 'submit' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Reeservation' , 'method' => 'save_booking' ); 
        $this->EE->db->insert('actions', $data); 
        
        $this->EE->db->select('template_id');
        $this->EE->db->from('specialty_templates');
        $this->EE->db->where('template_name', 'reeservation_created_admin_notification');   
        $query = $this->EE->db->get();
        if ($query->num_rows()==0)
        { 	 	 	 	
            $data = array( 'site_id' => $this->EE->config->item('site_id') , 'enable_template' => 'y', 'template_name' => 'reeservation_created_admin_notification', 'data_title'=> $this->EE->lang->line('subject_created_admin_notification'), 'template_data'=> $this->EE->lang->line('message_created_admin_notification') ); 
            $this->EE->db->insert('specialty_templates', $data); 
        }
        
        $this->EE->db->select('template_id');
        $this->EE->db->from('specialty_templates');
        $this->EE->db->where('template_name', 'reeservation_created_owner_notification');   
        $query = $this->EE->db->get();
        if ($query->num_rows()==0)
        { 	 	 	 	
            $data = array( 'site_id' => $this->EE->config->item('site_id') , 'enable_template' => 'y', 'template_name' => 'reeservation_created_owner_notification', 'data_title'=> $this->EE->lang->line('subject_created_owner_notification'), 'template_data'=> $this->EE->lang->line('message_created_owner_notification') ); 
            $this->EE->db->insert('specialty_templates', $data); 
        }
        
        $this->EE->db->select('template_id');
        $this->EE->db->from('specialty_templates');
        $this->EE->db->where('template_name', 'reeservation_created_user_notification');   
        $query = $this->EE->db->get();
        if ($query->num_rows()==0)
        { 	 	 	 	
            $data = array( 'site_id' => $this->EE->config->item('site_id') , 'enable_template' => 'y', 'template_name' => 'reeservation_created_user_notification', 'data_title'=> $this->EE->lang->line('subject_created_user_notification'), 'template_data'=> $this->EE->lang->line('message_created_user_notification') ); 
            $this->EE->db->insert('specialty_templates', $data); 
        }
        
        $this->EE->db->select('template_id');
        $this->EE->db->from('specialty_templates');
        $this->EE->db->where('template_name', 'reeservation_edited_user_notification');   
        $query = $this->EE->db->get();
        if ($query->num_rows()==0)
        { 	 	 	 	
            $data = array( 'site_id' => $this->EE->config->item('site_id') , 'enable_template' => 'y', 'template_name' => 'reeservation_edited_user_notification', 'data_title'=> $this->EE->lang->line('subject_edited_user_notification'), 'template_data'=> $this->EE->lang->line('message_edited_user_notification') ); 
            $this->EE->db->insert('specialty_templates', $data); 
        }
        
        
        
        return TRUE; 
        
    } 
    
    function uninstall() { 
        
        $this->EE->load->dbforge(); 
        
        $this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Reeservation')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Reeservation'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Reeservation'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->db->where('template_name', 'reeservation_created_user_notification');
        $this->EE->db->or_where('template_name', 'reeservation_created_admin_notification');   
        $this->EE->db->or_where('template_name', 'reeservation_edited_user_notification');    
        $this->EE->db->or_where('template_name', 'reeservation_created_owner_notification');      
        $this->EE->db->delete('specialty_templates');
        
        $this->EE->db->query("DROP TABLE exp_reeservation");
        
        return TRUE; 
    } 
    
    function update($current='') { 
        $this->EE->load->dbforge(); 
        if ($current < 2.2) 
        { 
            // add number of places
            if ($this->EE->db->field_exists('places', 'reeservation') == FALSE)
    		{
    			$this->EE->dbforge->add_column('reeservation', array('places' => array('type' => 'INT (10)') ) );
    		}
            $this->EE->db->update('reeservation', array('places' => 1));
        } 
        
        if ($current < 2.3) 
        { 
            // add property owner
            if ($this->EE->db->field_exists('owner_id', 'reeservation') == FALSE)
    		{
    			$this->EE->dbforge->add_column('reeservation', array('owner_id' => array('type' => 'INT (10)') ) );
    		}
            $q = $this->EE->db->query("SELECT exp_reeservation.entry_id, exp_channel_titles.author_id FROM exp_reeservation LEFT JOIN exp_channel_titles ON exp_channel_titles.entry_id=exp_reeservation.entry_id");
            if ($q->num_rows()>0)
            {
                foreach ($q->result_array() as $row)
                {
                    $this->EE->db->where('entry_id', $row['entry_id']);
                    $this->EE->db->update('reeservation', array('owner_id' => $row['author_id']));
                }
            }
            
            //add owner notification template
            $this->EE->db->select('template_id');
            $this->EE->db->from('specialty_templates');
            $this->EE->db->where('template_name', 'reeservation_created_owner_notification');   
            $query = $this->EE->db->get();
            if ($query->num_rows()==0)
            { 	 	 	 	
                $data = array( 'site_id' => $this->EE->config->item('site_id') , 'enable_template' => 'y', 'template_name' => 'reeservation_created_owner_notification', 'data_title'=> $this->EE->lang->line('subject_created_owner_notification'), 'template_data'=> $this->EE->lang->line('message_created_owner_notification') ); 
                $this->EE->db->insert('specialty_templates', $data); 
            }
        } 
        
        if ($current < 2.4) { 
            $data = array( 'class' => 'Reeservation' , 'method' => 'save_booking' ); 
            $this->EE->db->insert('actions', $data); 
        }
        
        if ($current < 2.5)
        {
            if ($this->EE->db->field_exists('cp_notification', 'modules') == FALSE)
    		{
    			$this->EE->dbforge->add_column('modules', array('cp_notification' => array('type' => 'VARCHAR(10)') ) );
    		}
            
            $data = array('name' => 'reeservation', 'version' => '2.5', 'settings' => 'YTowOnt9'); 
            $this->EE->db->insert('fieldtypes', $data); 
            
            $data = array('has_publish_fields' => 'y', 'cp_notification' => '2.5'); 
            $this->EE->db->where('module_name', 'Reeservation'); 
            $this->EE->db->update('modules', $data); 
            
            $sql[] = "CREATE TABLE `exp_reeservation_range_dates` (
                `range_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                `range_title` VARCHAR(255),
                `date_from` VARCHAR(10),
                `date_to` VARCHAR(10),
                `dow_from` VARCHAR(10),
                `dow_to` VARCHAR(10),
                `time_from` VARCHAR(10),
                `time_to` VARCHAR(10)
                )"; 
                
            $sql[] = "CREATE TABLE `exp_reeservation_range_entries` (
                `entry_id` INT NULL ,
                `range_id` INT NULL ,
                `price` VARCHAR(10),
                    INDEX (  `entry_id` ),
                    INDEX (  `range_id` )
                )";
                
            foreach ($sql as $qstr)
            {
                $this->EE->db->query($qstr);
            }
            
            if ($this->EE->db->field_exists('range_id', 'reeservation') == FALSE)
    		{
    			$this->EE->dbforge->add_column('reeservation', array('range_id' => array('type' => 'VARCHAR(10) NOT NULL ') ) );
    		}
            
        }
        
        
        if ($current < 3.0) { 
            // Do your 3.0 v. update queries 
        } 
        return TRUE; 
    } 
	

}
/* END */
?>
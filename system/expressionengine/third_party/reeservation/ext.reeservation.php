<?php

/*
=====================================================
 rEEservation
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2013 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.reeservation.php
-----------------------------------------------------
 Purpose: Booking / reservation engine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'reeservation/config.php';

class Reeservation_ext {

	var $name	     	= REESERVATION_ADDON_NAME;
	var $version 		= REESERVATION_ADDON_VERSION;
	var $description	= 'Extension for rEEservation booking module';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/reeservation.html';
    var $settings       = array();
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
        
        $this->settings = $settings;

	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'reeservation_booking_status_change',
    			'method'	=> 'notify_status_change',
    			'priority'	=> 10
    		)
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
    }
    
    function settings()
    {
        $settings = array();
        
        $settings['status_change_admin_notification'] = array('r', array('y' => 'Yes', 'n' => 'No'), 'n');
        $settings['status_change_owner_notification'] = array('r', array('y' => 'Yes', 'n' => 'No'), 'n');
        $settings['status_change_customer_notification'] = array('r', array('y' => 'Yes', 'n' => 'No'), 'y');
        
        return $settings;
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	if ($current < '2.0')
    	{
    		// Update to version 1.0
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    

    
    function notify_status_change($booking_id, $newstatus='open', $send=TRUE, $includecomment=FALSE) 
    {
        if (intval($booking_id)==0)
        {
            return FALSE;
        }
        
        if ($send!=TRUE)
        {
            return FALSE;
        }
        
        $this->EE->db->where('booking_id', $booking_id);
        $booking = $this->EE->db->get('reeservation');
        
        $this->EE->db->select('t.title, t.url_title, c.channel_name, c.channel_title, c.channel_url, c.comment_url');
        $this->EE->db->from('channel_titles AS t');
        $this->EE->db->join('channels AS c', 't.channel_id = c.channel_id', 'left');
        $this->EE->db->where('t.entry_id', $booking->row('entry_id'));
        $entry = $this->EE->db->get();
        
        $this->EE->db->select('username, screen_name, email');
        $this->EE->db->from('members');
        $this->EE->db->where('member_id', $booking->row('owner_id'));
        $owner = $this->EE->db->get();
        
        $customer_email = $booking->row('email');
        $owner_email = $owner->row('email');
        
        $this->EE->load->library('email');
        $this->EE->load->helper('string');
        $this->EE->load->helper('text');
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $mod_settings = unserialize($query->row('settings')); 
        
        $admin_notification_email = reduce_multiples($mod_settings['notification_email'], ",", TRUE); 
        $admin_recipients = explode(",",$admin_notification_email);     
        $admin_recipients = array_unique($admin_recipients); 
        
        $sent = array();
        
        $basepath = ($entry->row('comment_url')!='') ? $entry->row('comment_url') : $entry->row('channel_url');
        
        $swap = array(
						'booking_id'		=> $booking_id,
                        'name'				=> $booking->row('name'),
                        'email'				=> $customer_email,
                        'phone'				=> $booking->row('phone'),
                        'old_status'		=> $this->EE->lang->line($booking->row('status')),
						'status'	     	=> $this->EE->lang->line($newstatus),
						'site_name'			=> $this->EE->config->item('site_name'),
						'site_url'			=> $this->EE->config->item('site_url'),
                        'entry_id'	        => $booking->row('entry_id'),
                        'title'		        => $entry->row('title'),
						'url_title'	      	=> $entry->row('url_title'),
						'channel_name'		=> $entry->row('channel_name'),
						'channel_title'		=> $entry->row('channel_title'),
                        'owner_id'  		=> $booking->row('owner_id'),
                        'owner_username'	=> $owner->row('username'),
                        'owner_name'    	=> $owner->row('screen_name'),
						'permalink'		    => $basepath.$booking->row('entry_id')."/",
                        'title_permalink'	=> $basepath.$entry->row('url_title')."/"
					 );
		if ($includecomment==FALSE)
        {
            $swap['comment']='';
        }
        else
        {
            $swap['comment']=$booking->row('moderation_comment');
        }
        
        if (empty($this->settings) || $this->settings['status_change_customer_notification']=='y')
        {
            //user email
    		$template = $this->EE->functions->fetch_email_template('reeservation_edited_user_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_from')), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_to')), $email_msg);
    			}
    		}
            
            $sent[] = $customer_email;
    
            $this->EE->email->initialize();
    		$this->EE->email->wordwrap = false;
    		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
    		$this->EE->email->to($customer_email); 
    		$this->EE->email->subject($email_tit);	
    		$this->EE->email->message(entities_to_ascii($email_msg));		
    		$this->EE->email->Send();
        }
        
        if (!empty($this->settings) && $this->settings['status_change_owner_notification']=='y' && !in_array($owner_email, $sent) && $this->EE->session->userdata('member_id')!=$booking->row('owner_id'))
        {
            //user email
    		$template = $this->EE->functions->fetch_email_template('reeservation_edited_owner_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_from')), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_to')), $email_msg);
    			}
    		}
            
            $sent[] = $owner_email;
            
            $this->EE->email->initialize();
    		$this->EE->email->wordwrap = false;
    		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
    		$this->EE->email->to($owner_email); 
    		$this->EE->email->subject($email_tit);	
    		$this->EE->email->message(entities_to_ascii($email_msg));		
    		$this->EE->email->Send();
        }
        
        if (!empty($this->settings) && $this->settings['status_change_owner_notification']=='y' && !empty($admin_recipients))
        {
            //user email
    		$template = $this->EE->functions->fetch_email_template('reeservation_edited_admin_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_from')), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $booking->row('date_to')), $email_msg);
    			}
    		}
            
            foreach($admin_recipients as $recipient)
            {
                if (!in_array($recipient, $sent))
                {
                    $sent[] = $recipient;
                    $this->EE->email->initialize();
            		$this->EE->email->wordwrap = false;
            		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
            		$this->EE->email->to($recipient); 
            		$this->EE->email->subject($email_tit);	
            		$this->EE->email->message(entities_to_ascii($email_msg));		
            		$this->EE->email->Send();
                }
            }
        }
        
        return true;	
    }

}
// END CLASS

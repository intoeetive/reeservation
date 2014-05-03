<?php

/*
=====================================================
 rEEservation
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.reeservation_paypal.php
-----------------------------------------------------
 Purpose: PayPal integration for rEEservation module
 Requires Simple Commerce module to be installed
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

class Reeservation_paypal_ext {

	var $name	     	= 'rEEservation PayPal';
	var $version 		= '0.5';
	var $description	= 'PayPal integration for rEEservation module. Requires Simple Commerce';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/reeservation.html';
    var $settings       = array();
    
    var $debug = TRUE;
    var $encrypt = FALSE;
    var $paypal_account = '';
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
        
        $this->settings = $settings;
        
        $this->paypal_account = ( ! $this->EE->config->item('sc_paypal_account')) ? $this->EE->config->item('webmaster_email') : $this->EE->config->item('sc_paypal_account');
        
        $this->EE->lang->loadfile('reeservation_paypal');  

	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'reeservation_booking_created_absolute_end',
    			'method'	=> 'send_to_paypal',
    			'priority'	=> 10
    		),
            array(
    			'hook'		=> 'simple_commerce_evaluate_ipn_response',
    			'method'	=> 'evaluate_paypal_responce',
    			'priority'	=> 10
    		)
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> serialize($this->settings),
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
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
    	
    	if ($current < '0.1')
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
    
    
    // --------------------------------
    //  Settings
    // --------------------------------  
    
    function settings()
    {
        $settings = array();
        
        //module settings
        $mod_settings = array(
			'booking_channels' => ''
		);
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $mod_settings = unserialize($query->row('settings')); 
        
        $fields = array();
        
        if ($mod_settings['booking_channels']!='')
        {
            $sql = "SELECT f.field_id, f.field_label FROM exp_channel_fields AS f LEFT JOIN exp_field_groups AS g ON f.group_id=g.group_id LEFT JOIN exp_channels AS c ON g.group_id=c.field_group WHERE c.channel_id IN ({$mod_settings['booking_channels']}) ";
            $query = $this->EE->db->query("SHOW COLUMNS FROM `exp_channel_fields` LIKE 'field_is_drifter'");
            if ($query->num_rows() != 0)
            {
                $sql .= " OR f.field_is_drifter='y'";
            }
            $q = $this->EE->db->query($sql);
            foreach ($q->result_array() as $row)
            {
                $fields[$row['field_id']] = $row['field_label'];
            }
        }
        
        $settings['success_url']    = "/";
        $settings['cancel_url']    = "/";
        $settings['currency']    = "USD";
        $settings['extra_fee']    = "0";
        $settings['price_field']    = array('s', $fields);
        $settings['required_comment_field_val']    = "";
        
        return $settings;
    }
    

    function send_to_paypal($booking_id, $data)
    {
        if ($this->settings['price_field']=='') return false;
        
        if (($this->settings['required_comment_field_val']!='') && ($data['comment']!=$this->settings['required_comment_field_val'])) 
        {
            return false;
        }
        
        if ( ! class_exists('Simple_commerce'))
		{
			require PATH_MOD.'simple_commerce/mod.simple_commerce.php';
		}

		$SC = new Simple_commerce();
        
        //get the price
        $price_field = 'field_id_'.$this->settings['price_field'];
        $q = $this->EE->db->query("SELECT title, $price_field FROM exp_channel_titles AS t, exp_channel_data AS d WHERE t.entry_id=d.entry_id AND t.entry_id=".$data['entry_id']);
        $price = $q->row("$price_field");
        if ($price=='' || $price==0) return false;
        //get number of days
        $days = round(($data['date_to'] - $data['date_from'])/(24*60*60)+1);
        //get the name 'Booking for xxx for 01.02 - 02.02'
        $days_str = ($days==1)?$this->EE->lang->line('day'):$this->EE->lang->line('days');
        //$places_str = ($data['places']>1)? " (".$data['places']." ".$this->EE->lang->line('places').")":"";
        $title = $this->EE->lang->line('reservation_of').' '.$q->row('title').' '.$this->EE->lang->line('for').' '.$days.' '.$days_str;
        if (isset($this->settings['extra_fee']) && $this->settings['extra_fee']!='')
        {
            $extra_fee = $this->settings['extra_fee'];
        }
        else
        {
            $extra_fee = 0;
        }
        $amount = $price * $days + $extra_fee;
        if (substr($this->settings['success_url'], 0, 4) !== 'http')
		{
			$success = $this->EE->functions->create_url($this->settings['success_url']);
		}
        else
        {
            $success = $this->settings['success_url'];
        }
        if ($data['return']!='') $success = $data['return'];
		
		if (substr($this->settings['cancel_url'], 0, 4) !== 'http')
		{
			$cancel = $this->EE->functions->create_url($this->settings['cancel_url']);
		}
        else
        {
            $cancel = $this->settings['cancel_url'];
        }
        
        $buy_now['action']			= ($this->debug === TRUE) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
		$buy_now['hidden_fields']	= array(
										'cmd'  				=> '_xclick',
										'upload'			=> "1",
										'business'			=> $this->paypal_account,
										'return'			=> str_replace(SLASH, '/', $success),
										'cancel_return'		=> str_replace(SLASH, '/', $cancel),
										'item_name'			=> $title,
										'item_number'		=> $booking_id,
                                        'quantity'			=> $data['places'],
										'amount'			=> $amount,
										'lc'				=> 'US',
										'currency_code'		=> $this->settings['currency']
										);
		
		if ($this->encrypt === TRUE)
		{
			$url = $buy_now['action'].'?cmd=_s-xclick&amp;encrypted='.urlencode($SC->encrypt_data($buy_now['hidden_fields']));
		}
		else
		{
			$url = $buy_now['action'];
			
			foreach($buy_now['hidden_fields'] as $k => $v)
			{
				$url .= ($k == 'cmd') ? '?'.$k.'='.$v : '&amp;'.$k.'='.$SC->prep_val($v);
			}
		}
        
        return $this->EE->functions->redirect($url);
        
    }
    
    function evaluate_paypal_responce($sc_obj, $result)
    {
        $this->EE->extensions->end_script = TRUE;
                
        if (stristr($result, 'VERIFIED'))
		{

			// Not our paypal account receiving money, so invalid - and we key off txn_type for our conditional handling
			if (strtolower($this->paypal_account) != trim($sc_obj->post['receiver_email']) OR ! isset($sc_obj->post['txn_type']))
			{
				return FALSE;
			}
			
			//  booking_id valid?
			$this->EE->db->select('booking_id');
			$this->EE->db->where('booking_id', $sc_obj->post['item_number']); 
			$query = $this->EE->db->get('reeservation');
            if ($query->num_rows()==0)
            {
                return FALSE;
            }
            
            if (trim($sc_obj->post['payment_status']) != 'Completed')
			{
				return FALSE;
			}
            
            $this->EE->db->query("UPDATE exp_reeservation SET status='open', moderation_date='".$this->EE->localize->now."' WHERE booking_id=".$sc_obj->post['item_number']);
            
            // -------------------------------------------
    		// 'reeservation_booking_status_change' hook.
    		//  - Do additional processing when status is changed from CP
    		//  - Used to send notification
    		//
    			if ($this->EE->extensions->active_hook('reeservation_booking_status_change') === TRUE)
    			{
    				$edata = $this->EE->extensions->call('reeservation_booking_status_change', $sc_obj->post['item_number'], 'open', true, false);
    				//if ($this->EE->extensions->end_script === TRUE) return $edata;
    			}
    		//
            // -------------------------------------------
            
            return $result;
        }
    }

}
// END CLASS
?>
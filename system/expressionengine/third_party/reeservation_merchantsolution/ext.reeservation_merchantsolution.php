<?php

/*
=====================================================
 rEEservation
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2014 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.reeservation_merchantsolution.php
-----------------------------------------------------
 Purpose: Merchantsolution.com integration for rEEservation module
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

class Reeservation_merchantsolution_ext {

	var $name	     	= 'rEEservation Merchantsolution';
	var $version 		= '0.1';
	var $description	= 'Merchantsolution.com integration for rEEservation module';
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

        $this->EE->lang->loadfile('reeservation_merchantsolution');  

	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'reeservation_booking_created_absolute_end',
    			'method'	=> 'send_to_merchant',
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
        
        $settings['acctid']    = "TEST0";
        $settings['merchantpin']    = "";
        
        $settings['success_url']    = "/";
        $settings['cancel_url']    = "/";
        $settings['currency']    = "USD";
        $settings['extra_fee']    = "0";
        $settings['price_field']    = array('s', $fields);
        $settings['required_comment_field_val']    = "";
        
        return $settings;
    }
    

    function send_to_merchant($booking_id, $data)
    {
        if ($this->settings['price_field']=='') return false;
        
        if (($this->settings['required_comment_field_val']!='') && ($data['comment']!=$this->settings['required_comment_field_val'])) 
        {
            return false;
        }
        
        require_once(PATH_THIRD.'reeservation_merchantsolution/nusoap/nusoap'.EXT); 

		$wsdl = "https://trans.merchantsolution.com/Web/services/TransactionService?wsdl";
        $client = new nusoap_client($wsdl, 'wsdl');

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
        
        $param = array(
            'acctid' => $this->settings['acctid'],
            'subid' => '',
            'ccname' => $this->EE->input->post('ccname'),
            'ccnum' => $this->EE->input->post('ccnum'),
            'amount' => $amount,
            'expmon' => $this->EE->input->post('expmon'),
            'expyear' => $this->EE->input->post('expyear'),
            'cvv2_cid' => $this->EE->input->post('cvv2_cid'),
            'phone' => $this->EE->input->post('phone'),
            'email' => ($this->EE->input->post('email')!='')?$this->EE->input->post('email'):$this->EE->session->userdata('email'),
            'memo' => $title,
            'billaddress[addr1]' => $_POST['billaddress[addr1]'],
            'billaddress[addr2]' => $_POST['billaddress[addr2]'],
            'billaddress[city]' => $_POST['billaddress[city]'],
            'billaddress[state]' => $_POST['billaddress[state]'],
            'billaddress[zip]' => $_POST['billaddress[zip]'],
            'billaddress[memo]' => $_POST['billaddress[memo]']
        );

        $result = $client->call('processCCSale', array('ccinfo'=>$param));
        
        $upd = array(
            'moderation_date'   => $this->EE->localize->now,
        );
        
        $url = $cancel;
        if ($result!=false)
        {
            $upd['comment'] = $result['result'];
            if ($result['status']=='Approved')
            {
                $upd['status'] = 'open';
                $url = $success;
            }
        }
        else
        {
            $err = $client->getError();
            $upd['comment'] = $err['result'];
        }
        
        $this->EE->db->where('booking_id', $booking_id);
        $this->EE->db->update('reeservation', $upd);
        
        
        if (isset($upd['status']) && $upd['status'] == 'open')
        {
            // -------------------------------------------
    		// 'reeservation_booking_status_change' hook.
    		//  - Do additional processing when status is changed from CP
    		//  - Used to send notification
    		//
    			if ($this->EE->extensions->active_hook('reeservation_booking_status_change') === TRUE)
    			{
    				$edata = $this->EE->extensions->call('reeservation_booking_status_change', $booking_id, 'open', true, false);
    				//if ($this->EE->extensions->end_script === TRUE) return $edata;
    			}
    		//
            // -------------------------------------------
        }
        
        return $this->EE->functions->redirect($url);
        
    }


}

/*
Fields:

ccname - Consumer name as it appears on the card
ccnum - Credit Card number keyed in 
expmon - Expiration month keyed in 
expyear - Expiration year keyed in 
cvv2_cid - Credit card verification value/code (CVV2/CVC2)
billaddress[addr1] - Consumer billing address 
billaddress[addr2] - Second line of the consumer billing address 
billaddress[city] - Consumer city 
billaddress[state] - Consumer state or province 
billaddress[zip] - Consumer Zip code or Postal code 
billaddress[country] - Consumer country 
phone - Consumer phone number 
email - Consumer email address 
memo - Miscellaneous information field 

*/
// END CLASS
?>
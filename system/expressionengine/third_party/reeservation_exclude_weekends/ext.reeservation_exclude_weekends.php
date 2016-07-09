<?php

/*
=====================================================
 rEEservation
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2012 Yuriy Salimovskiy
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

class Reeservation_exclude_weekends_ext {

	var $name	     	= 'rEEservation Exclude Weekends';
	var $version 		= '0.1';
	var $description	= 'Makes impossible to book weekends';
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

	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'reeservation_booking_created_start',
    			'method'	=> 'disallow_booking',
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

        return $settings;
    }
    

    function disallow_booking()
    {
        
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $settings = unserialize($query->row('settings'));      
        
        if (strpos($this->EE->input->post('date_from'), "/")!==false)
        {
            //us date (%m/%d/%y)
            $date_parts = explode("/", $this->EE->input->post('date_from'));
            $m = $date_parts[0];
            $d = $date_parts[1];
            $y = $date_parts[2];
        }
        elseif (strpos($this->EE->input->post('date_from'), "-")!==false)
        {
            //european date (%Y-%m-%d)
            $date_parts = explode("-", $this->EE->input->post('date_from'));
            $m = $date_parts[1];
            $d = $date_parts[2];
            $y = $date_parts[0];
        }
        
        $standard_checkin_time = strtoupper($settings['standard_checkin_time']);
        if (strpos($standard_checkin_time, "AM")===false && strpos($standard_checkin_time, "PM")===false)
        {
            $standard_checkin_time_a = explode(":", $standard_checkin_time);
            $h = $standard_checkin_time_a[0];
            $i = $standard_checkin_time_a[1];
            $ampm = "AM";
            if ($h>12) 
            {
                $h=$h-12;
                $ampm = "PM";
            }
            $standard_checkin_time = str_pad($h, 2, "0", STR_PAD_LEFT).":".str_pad($i, 2, "0", STR_PAD_LEFT)." ".$ampm;
        }
        
        $date_from_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

        $date_from = (version_compare(APP_VER, '2.6.0', '<'))?$this->EE->localize->convert_human_date_to_gmt($date_from_human):$this->EE->localize->string_to_timestamp($date_from_human);
        
        if (gmdate("N", $date_from)>5)
        {
        	$this->EE->extensions->end_script = true;
        }
        
    }

}
// END CLASS
?>
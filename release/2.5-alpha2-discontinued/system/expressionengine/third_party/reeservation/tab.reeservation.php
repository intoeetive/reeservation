<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 File: tab.reeservation.php
-----------------------------------------------------
 Purpose: Booking / reservation engine
=====================================================
*/

class Reeservation_tab {

	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
        $this->EE->lang->loadfile('reeservation');

	}

	function publish_tabs($channel_id, $entry_id = '')
	{

		$settings = array();
        
        if ($entry_id=='')
        {
            return $settings;
        }
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $mod_settings = unserialize($query->row('settings'));  
        if (empty($mod_settings['booking_entries']))
        {
            return $settings;
        }
        else
        {
            $booking_entries = explode(",",$mod_settings['booking_entries']);
            if (!in_array($entry_id, $booking_entries))
            {
                return $settings;
            }
        }   

        if ($this->EE->db->count_all('reeservation_range_dates')==0)
        {
            return $settings;
        }

        $settings[] = array(
           'field_id' => 'reeservation_range_entries',
           'field_label' => lang('booking_ranges'),
           'field_required' => 'n',
           'field_data' => '',
           'field_list_items' => '',
           'field_fmt' => '',
           'field_instructions' => lang('ranges_tab_instructions'),
           'field_show_fmt' => 'n',
           'field_pre_populate' => 'n',
           'field_text_direction' => 'ltr',
           'field_type' => 'reeservation'
       );
  
		return $settings;
	}

	function validate_publish($params)
	{
		return FALSE;
	}
	
	function publish_data_db($params)
	{
		$this->EE->db->where('entry_id', $params['entry_id']);
        $this->EE->db->delete('reeservation_range_entries'); 
        
        $data = array();
        $i = 0;
        foreach ($params['data']['reeservation_range_entries'] as $range_id)
        {
            $data[$i] = array();
            $data[$i]['entry_id'] = $params['entry_id'];
            $data[$i]['range_id'] = $range_id;
            $data[$i]['price'] = $params['data']["reeservation_range_price_$range_id"];
            $i++;
        }
        $this->EE->db->insert_batch('reeservation_range_entries', $data); 

	}

	function publish_data_delete_db($params)
	{
		$this->EE->db->where('entry_id', $params['entry_id']);
        $this->EE->db->delete('reeservation_range_entries'); 
	}

}
/* END Class */

/* End of file tab.drafts.php */
/* Location: ./system/expressionengine/third_party/modules/download/tab.drafts.php */
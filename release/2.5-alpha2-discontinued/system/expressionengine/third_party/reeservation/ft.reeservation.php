<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reeservation_ft extends EE_Fieldtype {
	
	var $info = array(
		'name'		=> 'rEEservation range prices',
		'version'	=> '2.5'
	);
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Field on Publish
	 *
	 * @access	public
	 * @param	existing data
	 * @return	field html
	 *
	 */
	function display_field($data)
	{
		$this->EE->load->library('javascript');
        
        $outputjs = '
			$(".reeservation_range").change(function() {
				$("#"+$(this).attr("id")+"_price").toggle();
                if ($(this).is(":checked")) {
                    $("#"+$(this).attr("id")+"_price").removeAttr("disabled");
                } else {
                    $("#"+$(this).attr("id")+"_price").attr("disabled", true);
                }
            });
        ';

		$this->EE->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));
        
        $field_data = array();
        $field_list_items = array();
        $price = array();
        
        $this->EE->db->select('*');
        $this->EE->db->from('reeservation_range_dates');
        $ranges = $this->EE->db->get();
        if ($ranges->num_rows()==0)
        {
            return;
        }
        foreach ($ranges->result_array() as $row)
        {
            $field_list_items[$row['range_id']] = $row['range_title'];
        }
        
        $this->EE->db->select('*');
        $this->EE->db->from('reeservation_range_entries');
        $this->EE->db->where('entry_id', $this->EE->input->get('entry_id'));
        $entry_data = $this->EE->db->get();
        foreach ($entry_data->result_array() as $row)
        {
            $field_data[] = $row['range_id'];
            $price[$row['range_id']] = $row['price'];
        }
        
        $table = array();
        $i = 0;

		foreach($field_list_items as $option=>$text)
		{
			$data['table'][$i] = array();
            $checked = (in_array(form_prep($option), $field_data)) ? TRUE : FALSE;
            $hide = ($checked!==TRUE)?' style="display: none"':'';
            if (!isset($price[$option])) {$price[$option]='';}
            $data['table'][$i][] = form_checkbox('reeservation_range_entries[]', $option, $checked, ' class="reeservation_range" id="reeservation_range_'.$option.'"');
			$data['table'][$i][] = '<label for="reeservation_range_'.$option.'">'.$text.'</label>';
            
            $data['table'][$i][] = form_input('reeservation_range_price_'.$option, $price[$option], $hide.' id="reeservation_range_'.$option.'_price"')."&nbsp;";
            $i++;
		}
        
		return $this->EE->load->view('tab', $data, TRUE);
        
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Replace tag
	 *
	 * @access	public
	 * @param	field contents
	 * @return	replacement text
	 *
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
        return;
	}
    


 
    
    function save($data)
	{
		return $data;
	}
    
    function save_settings($data) {
        return array();    
    }
    
    
   	// ------------------------
	// P&T MATRIX SUPPORT
	// ------------------------
	
	/**
	 * Display Matrix field
	 */
	function display_cell($data) {
		return $this->display_field($data);
    }
	
    function display_cell_settings($data)
	{
	   return array();  
    }
    
    function save_cell_settings($data) {
		return $this->save_settings($data);
	}
    
	function save_cell($data)
	{
		return $this->save($data);
	}
    
	// --------------------------------------------------------------------
	
	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @return	default global settings
	 *
	 */
	function install()
	{
		return array();
	}
	

}

/* End of file ft.google_maps.php */
/* Location: ./system/expressionengine/third_party/google_maps/ft.google_maps.php */
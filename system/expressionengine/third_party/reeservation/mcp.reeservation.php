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
 File: mcp.reeservation.php
-----------------------------------------------------
 Purpose: Booking / reservation engine
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'reeservation/config.php';

class Reeservation_mcp {

    var $version = REESERVATION_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 50;
    
    function __construct() 
    { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $this->settings = unserialize($query->row('settings'));  

    } 
    
    
    /**
     * Booking calendar
     *
     * @param	Array	Settings
     * @return 	void
     */
    function index()
    {
    	//exit();
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
        if ($date_fmt == 'us')
		{
			//$date_format = '%m/%d/%y %h:%i %a';
            $date_format = '%m/%d/%y';
            $date_format_picker = 'mm/dd/y';
		}
		else
		{
			//$date_format = '%Y-%m-%d %H:%i';
            $date_format = '%Y-%m-%d';
            $date_format_picker = 'yy-mm-dd';
		}

        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output(' $("#date_from").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->output(' $("#date_to").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        //$this->EE->javascript->output(' alert($("#date_from").val()); '); 
        $this->EE->javascript->compile(); 
        
        $p_config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index';
		

    	$vars = array();
        
        $vars['channels'] = array();
        $vars['channels'][0] = 'All channels';
        $vars['entries'][0] = 'All entries';
        if (!empty($this->settings['booking_channels']))
        {
            $this->EE->db->select('channel_id, channel_title');
            $this->EE->db->from('channels');
            $this->EE->db->where_in('channel_id', explode(",",$this->settings['booking_channels']));  
            $query = $this->EE->db->get();
            foreach ($query->result() as $obj)
            {
               $vars['channels'][$obj->channel_id] = $obj->channel_title;
               $vars['entries']["$obj->channel_title"] = array();
            }
  
            $this->EE->db->select('entry_id, channel_id, title');
            $this->EE->db->from('channel_titles');
            $this->EE->db->where_in('channel_id', explode(",",$this->settings['booking_channels']));
            $query = $this->EE->db->get();
            foreach ($query->result() as $obj)
            {
               $vars['entries']["{$vars['channels'][$obj->channel_id]}"][$obj->entry_id] = $obj->title;
            }
        }   
        
        $vars['authors'] = array();
        $vars['authors'][0] = 'All customers';
        $query = $this->EE->db->query("SELECT DISTINCT exp_members.member_id, screen_name FROM exp_members, exp_reeservation WHERE exp_members.member_id=exp_reeservation.member_id");
        foreach ($query->result() as $obj)
        {
           $vars['authors'][$obj->member_id] = $obj->screen_name;
        }
        
        $vars['owners'] = array();
        $vars['owners'][0] = 'All owners';
        $query = $this->EE->db->query("SELECT DISTINCT exp_members.member_id, screen_name FROM exp_members, exp_reeservation WHERE exp_members.member_id=exp_reeservation.owner_id");
        foreach ($query->result() as $obj)
        {
           $vars['owners'][$obj->member_id] = $obj->screen_name;
        }
        
        $vars['statuses'] = array(
                        ''  =>  $this->EE->lang->line('any'),
                        'open'  =>  $this->EE->lang->line('open'),
                        'pending'  =>  $this->EE->lang->line('pending'),
                        'cancelled'  =>  $this->EE->lang->line('cancelled'),
                        'rejected'  =>  $this->EE->lang->line('rejected')
                    );

    	$vars['selected'] = array();
        $vars['selected']['channel_id']=$this->EE->input->get_post('channel_id');
        $vars['selected']['entry_id']=$this->EE->input->get_post('entry_id');
        $vars['selected']['owner_id']=$this->EE->input->get_post('owner_id');
        $vars['selected']['member_id']=$this->EE->input->get_post('member_id');
        $vars['selected']['email']=$this->EE->input->get_post('email');
        $vars['selected']['status']=$this->EE->input->get_post('status');
        
        $standard_checkin_time = strtoupper($this->settings['standard_checkin_time']);
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
        
        $standard_checkin_time = str_replace('00:00 ', '12:00 ', $standard_checkin_time);

        if ($this->EE->input->get_post('date_from')!='' && $this->EE->input->get_post('date_from')!=0)
        {
            $vars['selected']['date_from']=$this->EE->input->get_post('date_from');
            if (strpos($this->EE->input->post('date_from'), "/")!==false)
            {
                //us date (%m/%d/%y)
                $date_parts = explode("/", $this->EE->input->get_post('date_from'));
                $m = $date_parts[0];
                $d = $date_parts[1];
                $y = "20".$date_parts[2];
            }
            elseif (strpos($this->EE->input->get_post('date_from'), "-")!==false)
            {
                //european date (%Y-%m-%d)
                $date_parts = explode("-", $this->EE->input->get_post('date_from'));
                $m = $date_parts[1];
                $d = $date_parts[2];
                $y = $date_parts[0];
            }
            $date_from_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

            $date_from = $this->EE->localize->string_to_timestamp($date_from_human);
        }
        else
        {
            $vars['selected']['date_from']='';
        }
        if ($this->EE->input->get_post('date_to')!='' && $this->EE->input->get_post('date_to')!=0)
        {
            $vars['selected']['date_to']=$this->EE->input->get_post('date_to');

            if (strpos($this->EE->input->post('date_to'), "/")!==false)
            {
                //us date (%m/%d/%y)
                $date_parts = explode("/", $this->EE->input->get_post('date_to'));
                $m = $date_parts[0];
                $d = $date_parts[1];
                $y = "20".$date_parts[2];
            }
            elseif (strpos($this->EE->input->get_post('date_to'), "-")!==false)
            {
                //european date (%Y-%m-%d)
                $date_parts = explode("-", $this->EE->input->get_post('date_to'));
                $m = $date_parts[1];
                $d = $date_parts[2];
                $y = $date_parts[0];
            }
            $date_to_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

            $date_to = $this->EE->localize->string_to_timestamp($date_to_human);
        }
        else
        {
            $vars['selected']['date_to']='';
        }
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $this->EE->db->select('r.booking_id, c.channel_title, t.title, r.places, r.name, r.email, r.date_from, r.date_to, r.status');
        
        //$this->EE->db->start_cache();
        $this->EE->db->from('reeservation AS r');
        $this->EE->db->join('channel_titles AS t', 'r.entry_id = t.entry_id', 'left');
        //$this->EE->db->stop_cache();
        
        $this->EE->db->join('channels AS c', 'c.channel_id = t.channel_id', 'left');
        
        //$this->EE->db->start_cache();
        if ($vars['selected']['channel_id']!='' && $vars['selected']['channel_id']!=0)
        {
            $this->EE->db->where('t.channel_id', $vars['selected']['channel_id']);
            $p_config['base_url'] .= AMP.'channel_id='.$vars['selected']['channel_id'];
        }
        if ($vars['selected']['entry_id']!='' && $vars['selected']['entry_id']!=0)
        {
            $this->EE->db->where('r.entry_id', $vars['selected']['entry_id']);
            $p_config['base_url'] .= AMP.'entry_id='.$vars['selected']['entry_id'];
        }
        if ($vars['selected']['owner_id']!='' && $vars['selected']['owner_id']!=0)
        {
            $this->EE->db->where('r.owner_id', $vars['selected']['owner_id']);
            $p_config['base_url'] .= AMP.'owner_id='.$vars['selected']['owner_id'];
        }
        if ($vars['selected']['member_id']!='' && $vars['selected']['member_id']!=0)
        {
            $this->EE->db->where('r.member_id', $vars['selected']['member_id']);
            $p_config['base_url'] .= AMP.'member_id='.$vars['selected']['member_id'];
        }
        if ($vars['selected']['email']!='' && $vars['selected']['email']!=0)
        {
            $this->EE->db->where('r.email', $vars['selected']['email']);
            $p_config['base_url'] .= AMP.'email='.$vars['selected']['email'];
        }
        if ($vars['selected']['status']!='' && $vars['selected']['status']!=0)
        {
            $this->EE->db->where('r.status', $vars['selected']['status']);
            $p_config['base_url'] .= AMP.'status='.$vars['selected']['status'];
        }
        if ($vars['selected']['date_from']!='')
        {
            $this->EE->db->where('r.date_from >=', $date_from-3800);
            $p_config['base_url'] .= AMP.'date_from='.$vars['selected']['date_from'];
        }
        if ($vars['selected']['date_to']!='')
        {
            $this->EE->db->where('r.date_to <=', $date_to+3800);
            $p_config['base_url'] .= AMP.'date_to='.$vars['selected']['date_to'];
        }
        //$this->EE->db->stop_cache();
        
        $this->EE->db->order_by('booking_date', 'desc');
        $this->EE->db->limit($this->perpage, $vars['selected']['rownum']);

        $query = $this->EE->db->get();
        $vars['total_count'] = $query->num_rows();
        //exit();
        
        $i = 0;
        $vars['table_headings'] = array(
                        $this->EE->lang->line('#'),
                        $this->EE->lang->line('channel'),
                        $this->EE->lang->line('title'),
                        $this->EE->lang->line('customer'),
                        $this->EE->lang->line('date_from'),
                        $this->EE->lang->line('date_to'),
                        $this->EE->lang->line('places'),
                        $this->EE->lang->line('status'),
                        ''
                    );
                    
        
        if ($vars['total_count'] > 0)  
        {          
            foreach ($query->result() as $obj)
            {
               $vars['bookings'][$i]['booking_id'] = $obj->booking_id;
               $vars['bookings'][$i]['channel'] = $obj->channel_title;
               $vars['bookings'][$i]['title'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=edit'.AMP.'id='.$obj->booking_id."\">".$obj->title."</a>";
               $vars['bookings'][$i]['customer'] = "<a href=\"mailto:".$obj->email."\">".$obj->name."</a>";
               $vars['bookings'][$i]['date_from'] = $this->format_date($date_format, $obj->date_from); 
               $vars['bookings'][$i]['date_to'] = ($obj->date_to!=$obj->date_from)?$this->format_date($date_format, $obj->date_to):'';  
               $vars['bookings'][$i]['places'] = $obj->places;
               $vars['bookings'][$i]['status'] = $this->EE->lang->line($obj->status);
               $vars['bookings'][$i]['delete_link'] = "<a class=\"reeservation_delete_warning\" href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=delete'.AMP.'booking_id='.$obj->booking_id."\">".$this->EE->lang->line('delete_booking')."</a>";
               /**
               * TODO: support daylight saving time
               */
               $i++;
            }
        }
        
        $outputjs = '
				var draft_target = "";

			$("<div id=\"reeservation_delete_warning\">'.$this->EE->lang->line('booking_delete_warning').'</div>").dialog({
				autoOpen: false,
				resizable: false,
				title: "'.$this->EE->lang->line('confirm_deleting').'",
				modal: true,
				position: "center",
				minHeight: "0px", 
				buttons: {
					Cancel: function() {
					$(this).dialog("close");
					},
				"'.$this->EE->lang->line('delete_booking').'": function() {
					location=draft_target;
				}
				}});

			$(".reeservation_delete_warning").click( function (){
				$("#reeservation_delete_warning").dialog("open");
				draft_target = $(this).attr("href");
				$(".ui-dialog-buttonpane button:eq(2)").focus();	
				return false;
		});';

		$this->EE->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));
        
        $this->EE->load->library('pagination');
        
        $this->EE->db->select('booking_id');
        //cache is causing error in EE 2.2 for some reason, so had to duplicate these lines
        $this->EE->db->from('reeservation AS r');
        $this->EE->db->join('channel_titles AS t', 'r.entry_id = t.entry_id', 'left');
        if ($vars['selected']['channel_id']!='' && $vars['selected']['channel_id']!=0)
        {
            $this->EE->db->where('t.channel_id', $vars['selected']['channel_id']);
            $p_config['base_url'] .= AMP.'channel_id='.$vars['selected']['channel_id'];
        }
        if ($vars['selected']['entry_id']!='' && $vars['selected']['entry_id']!=0)
        {
            $this->EE->db->where('r.entry_id', $vars['selected']['entry_id']);
            $p_config['base_url'] .= AMP.'entry_id='.$vars['selected']['entry_id'];
        }
        if ($vars['selected']['owner_id']!='' && $vars['selected']['owner_id']!=0)
        {
            $this->EE->db->where('r.owner_id', $vars['selected']['owner_id']);
            $p_config['base_url'] .= AMP.'owner_id='.$vars['selected']['owner_id'];
        }
        if ($vars['selected']['member_id']!='' && $vars['selected']['member_id']!=0)
        {
            $this->EE->db->where('r.member_id', $vars['selected']['member_id']);
            $p_config['base_url'] .= AMP.'member_id='.$vars['selected']['member_id'];
        }
        if ($vars['selected']['email']!='' && $vars['selected']['email']!=0)
        {
            $this->EE->db->where('r.email', $vars['selected']['email']);
            $p_config['base_url'] .= AMP.'email='.$vars['selected']['email'];
        }
        if ($vars['selected']['status']!='' && $vars['selected']['status']!=0)
        {
            $this->EE->db->where('r.status', $vars['selected']['status']);
            $p_config['base_url'] .= AMP.'status='.$vars['selected']['status'];
        }
        if ($vars['selected']['date_from']!='')
        {
            $this->EE->db->where('r.date_from >=', $date_from-3800);
            $p_config['base_url'] .= AMP.'date_from='.$vars['selected']['date_from'];
        }
        if ($vars['selected']['date_to']!='')
        {
            $this->EE->db->where('r.date_to <=', $date_to+3800);
            $p_config['base_url'] .= AMP.'date_to='.$vars['selected']['date_to'];
        }
        $query = $this->EE->db->get();
        //$this->EE->db->flush_cache();
        
        $p_config['total_rows'] = $query->num_rows();
		$p_config['per_page'] = $this->perpage;
		$p_config['page_query_string'] = TRUE;
		$p_config['query_string_segment'] = 'rownum';
		$p_config['full_tag_open'] = '<p id="paginationLinks">';
		$p_config['full_tag_close'] = '</p>';
		$p_config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$p_config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$p_config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$p_config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';
        

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('reeservation_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('reeservation_module_name'));
        }
        
    	return $this->EE->load->view('index', $vars, TRUE);
	
    }    
    
    
    function delete()
    {

        if (!empty($_GET['booking_id']))
        {
            $this->EE->db->where('booking_id', $this->EE->input->get_post('booking_id'));
            $this->EE->db->delete('reeservation');
            if ($this->EE->db->affected_rows()>0)
            {
                $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('booking_deleted')); 
            }
            else
            {
                $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_booking_to_delete'));  
            }
            
        }
        else 
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('no_booking_to_delete'));  
        }

        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index');
        
        
    }
    
    /**
     * Edit booking
     *
     * @param	Array	Settings
     * @return 	void
     */
    function edit()
    {
    	$this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
        if ($date_fmt == 'us')
		{
            $date_format = '%m/%d/%y';
            $date_format_picker = 'mm/dd/y';
		}
		else
		{
            $date_format = '%Y-%m-%d';
            $date_format_picker = 'yy-mm-dd';
		}

    	$vars = array();
 
        $this->EE->db->select('r.*, c.channel_title, c.channel_url, c.comment_url, t.title');
        $this->EE->db->from('reeservation AS r');
        $this->EE->db->join('channel_titles AS t', 'r.entry_id = t.entry_id', 'left');
        $this->EE->db->join('channels AS c', 'c.channel_id = t.channel_id', 'left');
        $this->EE->db->where('r.booking_id', $this->EE->input->get('id'));
        $query = $this->EE->db->get();

        $statuses = array(
                        'open'  =>  $this->EE->lang->line('open'),
                        'pending'  =>  $this->EE->lang->line('pending'),
                        'cancelled'  =>  $this->EE->lang->line('cancelled'),
                        'rejected'  =>  $this->EE->lang->line('rejected')
                    );
                    
        
        $yesno = array(
                                    'y' => $this->EE->lang->line('yes'),
                                    'n' => $this->EE->lang->line('no')
                                );
        
                     
        $basepath = ($query->row('comment_url')!='')?$query->row('comment_url'):$query->row('channel_url');
        $basepath = trim($basepath, "/")."/";
 
        $vars['data'] = array(	
            'booking_id'	=> form_hidden('booking_id', $query->row('booking_id')).$query->row('booking_id'),
            'channel'	=> $query->row('channel_title'),
            'title'	=> "<a href=\"".$basepath.$query->row('entry_id')."\">".$query->row('title')."</a>",
            'customer'	=> $query->row('name')."<br /><a href=\"mailto:".$query->row('email')."\">".$query->row('email')."</a><br />".$query->row('phone')."<br />".$query->row('contact'),
            'date_from'	=> "<strong>".$this->format_date($date_format, $query->row('date_from'))."</strong>",
            'date_to'	=> ($query->row('date_to')!=$query->row('date_from'))?"<strong>".$this->format_date($date_format, $query->row('date_to'))."</strong>":'',
            'places'	=> $query->row('places'),
            'comment'	=> nl2br($query->row('comment')),
            'booking_date'	=> $this->format_date($date_format, $query->row('booking_date')),
            'status'	=> form_dropdown('status', $statuses, $query->row('status'))
    		);
        
        
        if ($query->row('moderator_id')!='')
        {
            $q = $this->EE->db->query("SELECT screen_name FROM exp_members WHERE member_id=".$this->EE->db->escape_str($query->row('moderator_id')));
            $vars['data']['last_edit'] = $this->format_date($date_format, $query->row('moderation_date'))." ".$this->EE->lang->line('by')." <a href=\"".BASE.AMP."D=cp&C=myaccount&id=".$query->row('moderator_id')."\">".$q->row('screen_name')."</a>";
            
        }
        //echo "<pre>".$query->row('moderation_comment')."</pre>";
        $vars['data']['moderator_comment'] = form_textarea('moderator_comment', $query->row('moderation_comment'));
        
        //check for extension
        $q = $this->EE->db->query("SELECT extension_id FROM exp_extensions WHERE class='Reeservation_ext' AND hook='reeservation_booking_status_change'");
        if ($q->num_rows()>0)
        {
            $vars['data']['notify_user'] = form_dropdown('notify_user', $yesno);
            $vars['data']['include_comment'] = form_dropdown('include_comment', $yesno);
        }

        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('reeservation_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('reeservation_module_name'));
        }
        
    	return $this->EE->load->view('edit', $vars, TRUE);
	
    }        
    
    
    function save_booking()
    {

        $status = (isset($_POST['status']))?$this->EE->security->xss_clean($_POST['status']):$this->settings['default_status'];
        $moderator_comment = (isset($_POST['moderator_comment']))?$this->EE->security->xss_clean($_POST['moderator_comment']):'';
        $includecomment = (isset($_POST['include_comment']) && $_POST['include_comment']=='y') ? TRUE : FALSE;
        
        if (!isset($_POST['booking_id']))
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('missing_booking_id'));
        }
        else
        {
            $this->EE->db->where('booking_id', $_POST['booking_id']);
            $this->EE->db->update('reeservation', array('moderation_date' => $this->EE->localize->now, 'moderator_id' => $this->EE->session->userdata('member_id'), 'status' => $status, 'moderation_comment' => $moderator_comment));
            
            
            if (isset($_POST['notify_user']) && $_POST['notify_user']=='y')
            {
                $send = true;
            }
            else
            {
                $send = false;
            }
            
            // -------------------------------------------
    		// 'reeservation_booking_status_change' hook.
    		//  - Do additional processing when status is changed from CP
    		//  - Used to send notification
    		//
    			if ($this->EE->extensions->active_hook('reeservation_booking_status_change') === TRUE)
    			{
    				$edata = $this->EE->extensions->call('reeservation_booking_status_change', $_POST['booking_id'], $status, $send, $includecomment);
    				if ($this->EE->extensions->end_script === TRUE) return $edata;
    			}
    		//
            // -------------------------------------------

        }
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index');
    }
    
    
    
    /**
     * Create booking
     *
     * @param	Array	Settings
     * @return 	void
     */
    function create_booking()
    {
    	if (empty($this->settings['booking_channels']))
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('nothing_to_book'));
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index');
            return;
        }
        
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        
        $date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');
        if ($date_fmt == 'us')
		{
            $date_format = '%m/%d/%y';
            $date_format_picker = 'mm/dd/y';
		}
		else
		{
            $date_format = '%Y-%m-%d';
            $date_format_picker = 'yy-mm-dd';
		}

    	$vars = array();
 
        $this->EE->db->select('channel_id, channel_title');
        $this->EE->db->from('channels');
        $this->EE->db->where_in('channel_id', explode(",",$this->settings['booking_channels']));  
        $query = $this->EE->db->get();
        foreach ($query->result() as $obj)
        {
           $channels[$obj->channel_id] = $obj->channel_title;
           $entries["$obj->channel_title"] = array();
        }

        $this->EE->db->select('entry_id, channel_id, title');
        $this->EE->db->from('channel_titles');
        $this->EE->db->where_in('channel_id', explode(",",$this->settings['booking_channels']));
        $query = $this->EE->db->get();
        foreach ($query->result() as $obj)
        {
           $entries["{$channels[$obj->channel_id]}"][$obj->entry_id] = $obj->title;
        }
  

        $statuses = array(
                        'open'  =>  $this->EE->lang->line('open'),
                        'pending'  =>  $this->EE->lang->line('pending'),
                        'cancelled'  =>  $this->EE->lang->line('cancelled'),
                        'rejected'  =>  $this->EE->lang->line('rejected')
                    );
                    
        
        $yesno = array(
                        'y' => $this->EE->lang->line('yes'),
                        'n' => $this->EE->lang->line('no')
                    );
        
                     
 
        $vars['data'] = array(	
            'object'   	=> form_multiselect('entry_id[]', $entries),
            'places'    => form_input('places', 1),
            'date_from'	=> form_input('date_from', '', 'id="date_from"'),
            'date_to'	=> form_input('date_to', '', 'id="date_to"'),
            'name'      => form_input('name'),
            'email'     => form_input('email'),
            'phone'     => form_input('phone'),
            'contact'   => form_textarea('contact'),
            'comment'   => form_textarea('comment'),
            'status'	=> form_dropdown('status', $statuses, $this->settings['default_status'])
    		);
        
        $this->EE->cp->add_js_script('ui', 'datepicker'); 
        $this->EE->javascript->output(' $("#date_from").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->output(' $("#date_to").datepicker({ dateFormat: "'.$date_format_picker.'" }); '); 
        $this->EE->javascript->compile(); 
        
        $vars['data']['moderator_comment'] = form_textarea('moderator_comment');
        $vars['data']['notify_user_on_creation'] = form_dropdown('notify_user', $yesno, $this->settings['send_user_notification']);

        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('reeservation_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('reeservation_module_name'));
        }
        
    	return $this->EE->load->view('create', $vars, TRUE);
	
    }        
    
    
    function submit_booking()
    {
        /**
         * @todo
         * add support for booking of multiple entries
         */
        
        $ts = $this->EE->localize->now;
        //check for all the fields
        $errors = array();

        
        
        $name = $this->EE->input->post('name');
        $email = $this->EE->input->post('email');
        if ($email!='')
        {
            $this->EE->load->helper('email');
            if (!valid_email($email))
            {
                $errors[] = $this->EE->lang->line('invalid_email');
            }
        }

        if ($name=='' || $email=='')
        {
            $errors[] = $this->EE->lang->line('missing_booking_userdata');
        }
        
        

        //valid user data
        $member_id = $this->EE->session->userdata('member_id');

        //the dates are available
        if ($this->EE->input->post('date_from')=='' && ($this->EE->input->post('date_from_month')=='' || $this->EE->input->post('date_from_day')==''))
        {
            $errors[] = $this->EE->lang->line('missing_dates');
        }
        else
        {
            //check input format
            if ($this->EE->input->post('date_from')!='')
            {
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
                else
                {
                    //wrong date format!
                    $errors[] = $this->EE->lang->line('wrong_date_format');
                }
            }
            else
            {
                if ($this->EE->input->post('date_from_day')=='' || $this->EE->input->post('date_from_month')=='')
                {
                    $errors[] = $this->EE->lang->line('missing_dates');
                }
                else
                {
                    $d = $this->EE->input->post('date_from_day');
                    $m = $this->EE->input->post('date_from_month');
                }
                if ($this->EE->input->post('date_from_year')=='')
                {
                    $y = date("Y");
                }
                else
                {
                    $y = $this->EE->input->post('date_from_year');
                } 
            }
        }
        
        //show errors
        if (!empty($errors))
        {
            foreach ($errors as $error)
            {
                $this->EE->session->set_flashdata('message_failure', $error);
            }
            
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking');
            return;
        }   

        $standard_checkin_time = strtoupper($this->settings['standard_checkin_time']);
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
        $standard_checkin_time = str_replace('00:00 ', '12:00 ', $standard_checkin_time);
        
        $date_from_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

        $date_from = $this->EE->localize->string_to_timestamp("$date_from_human");
        /*if ($this->settings['use_time']=='n')
        {
            $date_from_human = gmdate("Y-m-d", $date_from);
            $date_from = $this->EE->localize->convert_human_date_to_gmt($date_from_human." ".$this->settings['standard_checkin_time']);
        }*/

        if ($date_from < $ts)
        {
            $errors[] = $this->EE->lang->line('date_is_in_the_past');
        }
        
        if ($this->EE->input->post('date_to')=='' && ($this->EE->input->post('date_to_month')=='' || $this->EE->input->post('date_to_day')==''))
        {
            $date_to = $date_from;
        }
        else
        {
            //check input format
            if ($this->EE->input->post('date_to')!='')
            {
                if (strpos($this->EE->input->post('date_to'), "/")!==false)
                {
                    //us date (%m/%d/%y)
                    $date_parts = explode("/", $this->EE->input->post('date_to'));
                    $m = $date_parts[0];
                    $d = $date_parts[1];
                    $y = "20".$date_parts[2];
                }
                elseif (strpos($this->EE->input->post('date_to'), "-")!==false)
                {
                    //european date (%Y-%m-%d)
                    $date_parts = explode("-", $this->EE->input->post('date_to'));
                    $m = $date_parts[1];
                    $d = $date_parts[2];
                    $y = $date_parts[0];
                }
                else
                {
                    //wrong date format!
                    $errors[] = $this->EE->lang->line('wrong_date_format');
                }
            }
            else
            {
                if ($this->EE->input->post('date_to_day')=='' || $this->EE->input->post('date_to_month')=='')
                {
                    $errors[] = $this->EE->lang->line('missing_dates');
                }
                else
                {
                    $d = $this->EE->input->post('date_to_day');
                    $m = $this->EE->input->post('date_from_month');
                }
                if ($this->EE->input->post('date_to_year')=='')
                {
                    $y = date("Y");
                }
                else
                {
                    $y = $this->EE->input->post('date_to_year');
                } 
            }
            $date_to_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

            $date_to = $this->EE->localize->string_to_timestamp($date_to_human);
        }
        
        
        //show errors
        if (!empty($errors))
        {
            foreach ($errors as $error)
            {
                $this->EE->session->set_flashdata('message_failure', $error);
            }
            
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking');
            return;
        }   
        
        
        if ($date_from > $date_to)
        {
            $errors[] = $this->EE->lang->line('date_start_greater_date_end');
        }

        
        if (is_array($_POST['entry_id']))
        {
      		$entry_ids = $_POST['entry_id'];
        }
        else
        {
        	$entry_ids = array($_POST['entry_id']);
        }
        
        $owner_id_a = array();
        $title_a = array();
        $url_title_a = array();
        $channel_name_a = array();
        $channel_title_a = array();
        $channel_url_a = array();
        $comment_url_a = array();
        
        //valid entry_id
        foreach ($entry_ids as $entry_id)
        {
       
	        if ($entry_id==0)
	        {
	            $errors[] = $this->EE->lang->line('missing_booking_entry_id');
	        }
	        else
	        {
	            if ($this->settings['limit_field']!='')
	            {
	                $limit_field = 'field_id_'.$this->settings['limit_field'];
	                $this->EE->db->select('t.title, t.url_title, t.author_id, t.status, t.expiration_date, c.channel_name, c.channel_title, c.channel_url, c.comment_url, d.'.$limit_field);
	                $this->EE->db->from('channel_titles AS t');
	                $this->EE->db->join('channel_data AS d', 't.entry_id = d.entry_id', 'left');
	            }
	            else
	            {
	                $this->EE->db->select('t.title, t.url_title, t.author_id, t.status, t.expiration_date, c.channel_name, c.channel_title, c.channel_url, c.comment_url');
	                $this->EE->db->from('channel_titles AS t');
	            }
	            $this->EE->db->join('channels AS c', 't.channel_id = c.channel_id', 'left');
	            $this->EE->db->where('t.entry_id', $entry_id);
	            $this->EE->db->where('t.status', 'open');
	            $this->EE->db->where('(t.expiration_date >= '. $ts.' OR t.expiration_date=0)');
	            $this->EE->db->limit(1);
	            $query = $this->EE->db->get();
	            if ($query->num_rows()==0)
	            {
	                $errors[] = $this->EE->lang->line('invalid_booking_entry_id');
	            }
	            else
	            {
	                /*$booking_entries = explode(",", $this->settings['booking_entries']);
	                if (!in_array($entry_id, $booking_entries))
	                {
	                    $errors[] = $this->EE->lang->line('invalid_booking_entry_id');
	                }*/
	                $owner_id_a[$entry_id] = $query->row('author_id');
	                $title_a[$entry_id] = $query->row('title');
	                $url_title_a[$entry_id] = $query->row('url_title');
	                $channel_name_a[$entry_id] = $query->row('channel_name');
	                $channel_title_a[$entry_id] = $query->row('channel_title');
	                $channel_url_a[$entry_id] = $query->row('channel_url');
	                $comment_url_a[$entry_id] = $query->row('comment_url');
	            }
	        } 
	        
	        //anything booked already?
	        $sql = "SELECT booking_id, date_from, date_to, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ( (date_from>".($date_from-3800)." AND date_from<".($date_to+3800).") OR (date_to>".($date_from-3800)." AND date_to<".($date_to+3800).") )  AND status='open'";
	        //you might wonder where 3800 comes from? that's 1 hour plus 200 seconds added because of possible time calculation fault
	        $query = $this->EE->db->query($sql);
	        
	        $req_places = (intval($this->EE->input->post('places'))==0) ? 1 : intval($this->EE->input->post('places'));
	        $booked_places = 0;
	        if ($query->num_rows()>0)
	        {
	            foreach ($query->result_array() as $row)
	            {
	                if (isset($this->settings['allow_changeover_booking']) && $this->settings['allow_changeover_booking']=='y')
	                {
	                	//exclude changover date bookings from the check
	                	if ($this->format_date("%Y-%m-%d", $date_from) == $this->format_date("%Y-%m-%d", $row['date_to']))
	                	{
	                		continue;
	                	}
	                	if ($this->format_date("%Y-%m-%d", $date_to) == $this->format_date("%Y-%m-%d", $row['date_from']))
	                	{
	                		continue;
	                	}
	                }
					$booked_places += $row['places'];
	            }
	        }
	        
	        //pending bookings?
	        $pend_booked_places = 0;
	        if (intval($this->settings['lock_timeout'])!=0)
	        {
	            $lock_timeout = $ts - intval($this->settings['lock_timeout'])*60;
	
	            $sql = "SELECT booking_id, date_from, date_to, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ((date_from>$date_from AND date_from<$date_to) OR (date_to>$date_from AND date_to<$date_to))  AND status='pending' AND booking_date>$lock_timeout AND booking_date<$ts";
	            $pend_query = $this->EE->db->query($sql);
	            if ($pend_query->num_rows()>0)
	            {
	                foreach ($pend_query->result_array() as $row)
	                {
	                    if (isset($this->settings['allow_changeover_booking']) && $this->settings['allow_changeover_booking']=='y')
		                {
		                	//exclude changover date bookings from the check
		                	if ($this->format_date("%Y-%m-%d", $date_from) == $this->format_date("%Y-%m-%d", $row['date_to']))
		                	{
		                		continue;
		                	}
		                	if ($this->format_date("%Y-%m-%d", $date_to) == $this->format_date("%Y-%m-%d", $row['date_from']))
		                	{
		                		continue;
		                	}
		                }
						$pend_booked_places += $row['places'];
	                }
	            }
	            
	        }
	        
	        if ($this->settings['limit_field']!='')
	        {
	            $bookings_limit = (intval($this->settings['bookings_limit'])<intval($query->row("$limit_field")))?intval($this->settings['bookings_limit']):intval($query->row("$limit_field"));
	        }
	        else
	        {
	            $bookings_limit = intval($this->settings['bookings_limit']);
	        }
	        if ($bookings_limit<=0) $bookings_limit = 1;
	        
	                                
	        if ($this->settings['allow_multiple_bookings']=='y')
	        {
	            $avail_places = $bookings_limit - $booked_places - $req_places;
	            if ($avail_places<0)
	            {
	                $errors[] = str_replace('%X', $title_a[$entry_id], $this->EE->lang->line('bookings_limit_reached'));
	            }
	            else
	            {
	                $pend_avail_places = $avail_places - $pend_booked_places;
	                if ($pend_avail_places<0)
	                {
	                    $errors[] = str_replace('%X', $title_a[$entry_id], $this->EE->lang->line('bookings_limit_pending_reached'));
	                }
	            }
	        }
	        else
	        {
	            
	            if ($booked_places!=0)
	            {
	                $errors[] = str_replace('%X', $title_a[$entry_id], $this->EE->lang->line('booking_for_these_dates_exist'));
	            }
	            else
	            {
	                //pending bookings?
	                if (intval($this->settings['lock_timeout'])!=0)
	                {
	                    if ($pend_booked_places!=0)
	                    {
	                        $errors[] = str_replace('%X', $title_a[$entry_id], $this->EE->lang->line('pending_booking_for_these_dates_exist'));
	                    }
	                }
	            }
	        }
        }
        
        //show errors
        if (!empty($errors))
        {
            foreach ($errors as $error)
            {
                $this->EE->session->set_flashdata('message_failure', $error);
            }
            
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking');
            return;
        }   
        
        
        foreach ($entry_ids as $entry_id)
        {
			
			$owner_id =$owner_id_a[$entry_id];
            $title = $title_a[$entry_id];
            $url_title = $url_title_a[$entry_id];
            $channel_name = $channel_name_a[$entry_id];
            $channel_title = $channel_title_a[$entry_id];
            $channel_url = $channel_url_a[$entry_id];
            $comment_url = $comment_url_a[$entry_id];
        
	        //all is fine? thank God! we can add the record(s)
	        $data = array(
	               'entry_id' => $entry_id,
	               'member_id' => $member_id,
	               'owner_id' => $owner_id,
	               
	               'places' => $req_places,
	               'name' => $name,
	               'email' => $email,
	               'phone' => ($this->EE->input->post('phone')!='')?$this->EE->input->post('phone'):'',
	               'contact' => ($this->EE->input->post('contact')!='')?$this->EE->input->post('contact'):'',
	               'comment' => ($this->EE->input->post('comment')!='')?$this->EE->input->post('comment'):'',
	               'moderation_comment' => ($this->EE->input->post('moderator_comment')!='')?$this->EE->input->post('moderator_comment'):'',
	               'date_from' => $date_from,
	               'date_to' => $date_to,
	               'status' => $this->EE->input->post('status'),
	               'booking_date' => $ts
	            );
	        $this->EE->db->insert('reeservation', $data); 
	        $booking_id = $this->EE->db->insert_id();
	        
	        //send the notifications
	        $this->EE->load->library('email');
	        $this->EE->load->helper('string');
	        $this->EE->load->helper('text');
	        
	        $basepath = ($comment_url!='')?$comment_url:$channel_url;
	        $basepath = trim($basepath, "/")."/";
	        
	        $this->EE->db->select('username, screen_name, email');
	        $this->EE->db->from('members');
	        $this->EE->db->where('member_id', $owner_id);
	        $query = $this->EE->db->get();
	        $owner_email = $query->row('email');
	        $owner_username = $query->row('username');
	        $owner_name = $query->row('screen_name');
	        
	        $swap = array(
							'booking_id'		=> $booking_id,
	                        'name'				=> $name,
	                        'email'				=> $email,
	                        'phone'				=> $data['phone'],
	                        'places'	        => $data['places'],
	                        'comment'	        => $data['comment'],
							'status'	     	=> $this->EE->lang->line($this->EE->input->post('status')),
							'site_name'			=> $this->EE->config->item('site_name'),
							'site_url'			=> $this->EE->config->item('site_url'),
	                        'entry_id'		    => $entry_id,
	                        'title'		        => $title,
							'url_title'	      	=> $url_title,
							'channel_name'		=> $channel_name,
							'channel_title'		=> $channel_title,
	                        'owner_id'  		=> $owner_id,
	                        'owner_username'	=> $owner_username,
	                        'owner_name'    	=> $owner_name,
							'permalink'		    => $basepath.$entry_id."/",
	                        'title_permalink'	=> $basepath.$url_title."/"
						 );
			
	        //user email
	        if ($this->settings['send_user_notification']=='y' && $this->EE->input->post('notify_user')=='y')
	        {
	    		$template = $this->EE->functions->fetch_email_template('reeservation_created_user_notification');
	    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
	    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
	            
	            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
	    		{
	    			for ($j = 0; $j < count($matches['0']); $j++)
	    			{
	                    $email_msg = str_replace($matches['0'][$j], $this->format_date($matches['1'][$j], $date_from), $email_msg);
	    			}
	    		}
	            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
	    		{
	    			for ($j = 0; $j < count($matches['0']); $j++)
	    			{
	                    $email_msg = str_replace($matches['0'][$j], $this->format_date($matches['1'][$j], $date_to), $email_msg);
	    			}
	    		}
	            
	            $this->EE->email->initialize();
	    		$this->EE->email->wordwrap = false;
	    		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
	    		$this->EE->email->to($email); 
	    		$this->EE->email->subject($email_tit);	
	    		$this->EE->email->message(entities_to_ascii($email_msg));		
	    		$this->EE->email->Send();
	        }
	        
	        //owner email
	        //only if did not receive admin notification 
	        if ($this->settings['send_owner_notification']=='y' && $owner_email!=$this->EE->session->userdata['email'])
	        {
	    		$template = $this->EE->functions->fetch_email_template('reeservation_created_owner_notification');
	    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
	    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
	            
	            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
	    		{
	    			for ($j = 0; $j < count($matches['0']); $j++)
	    			{
	                    $email_msg = str_replace($matches['0'][$j], $this->format_date($matches['1'][$j], $date_from), $email_msg);
	    			}
	    		}
	            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
	    		{
	    			for ($j = 0; $j < count($matches['0']); $j++)
	    			{
	                    $email_msg = str_replace($matches['0'][$j], $this->format_date($matches['1'][$j], $date_to), $email_msg);
	    			}
	    		}
	            
	            $this->EE->email->initialize();
	    		$this->EE->email->wordwrap = false;
	    		$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
	    		$this->EE->email->to($owner_email); 
	    		$this->EE->email->subject($email_tit);	
	    		$this->EE->email->message(entities_to_ascii($email_msg));		
	    		$this->EE->email->Send();
	        }
       }


        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('booking_created'));
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index');

        return;
    }    
    
    
    
    /**
     * Settings Form
     *
     * @param	Array	Settings
     * @return 	void
     */
    function settings()
    {
    	$site_id = $this->EE->config->item('site_id');
        
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');

    	$vars = array();
        
        $statuses = array(
                        'open'  =>  $this->EE->lang->line('open'),
                        'pending'  =>  $this->EE->lang->line('pending'),
                        'cancelled'  =>  $this->EE->lang->line('cancelled'),
                        'rejected'  =>  $this->EE->lang->line('rejected')
                    );
                    
        $channels = array();
        $_channels = $this->EE->db->select('channel_id, channel_title')->from('channels')->where('site_id', $site_id)->get();

        foreach ($_channels->result_object() as $obj)
        {
            $channels[$obj->channel_id] = $obj->channel_title;
        }
        $channels_selected = array();
        $channels_selected = explode(",",$this->settings['booking_channels']);

/*
        $entries = array();
        if (!empty($channels_selected))
        {
            $this->EE->db->select('entry_id, title');
            $this->EE->db->from('channel_titles');
            $this->EE->db->where('status', 'open');
            $this->EE->db->where_in('channel_id', $channels_selected);
            
            $query = $this->EE->db->get();
            foreach ($query->result() as $obj)
            {
               $entries[$obj->entry_id] = $obj->title;
            }
        }
        $entries_selected = array();
        $entries_selected = explode(",",$this->settings['booking_entries']);
        */
        //a trick to make selects 'multiple'
        //array_push($channels_selected, array(0,0));
        //array_push($entries_selected, array(0,0));
        
        $yesno = array(
                                    'y' => $this->EE->lang->line('yes'),
                                    'n' => $this->EE->lang->line('no')
                                );
                                
        $fields = array();
        $fields['']='';
        if ($this->settings['booking_channels']!='')
        {
            $sql = "SELECT f.field_id, f.field_label FROM exp_channel_fields AS f LEFT JOIN exp_field_groups AS g ON f.group_id=g.group_id LEFT JOIN exp_channels AS c ON g.group_id=c.field_group WHERE c.channel_id IN ({$this->settings['booking_channels']}) ";
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
        
        if (!isset($this->settings['allow_changeover_booking'])) $this->settings['allow_changeover_booking'] = 'n';
 
        $vars['settings'] = array(	
            'default_status'	=> form_dropdown('default_status', $statuses, $this->settings['default_status']),
            'allow_multiple_bookings'	=> form_dropdown('allow_multiple_bookings', $yesno, $this->settings['allow_multiple_bookings']),
            'bookings_limit'	=> form_input('bookings_limit', $this->settings['bookings_limit']),
            'bookings_user_limit'	=> form_input('bookings_user_limit', $this->settings['bookings_user_limit']),
            
            'limit_field'	=> form_dropdown('limit_field', $fields, $this->settings['limit_field']),
            'booking_channels'	=> form_multiselect('booking_channels[]', $channels, $channels_selected),
            //'booking_entries'	=> form_multiselect('booking_entries[]', $entries, $entries_selected),
            'lock_timeout'	=> form_input('lock_timeout', $this->settings['lock_timeout']),
            'allow_changeover_booking'	=> form_dropdown('allow_changeover_booking', $yesno, $this->settings['allow_changeover_booking']),
            'standard_checkin_time'	=> form_input('standard_checkin_time', $this->settings['standard_checkin_time']),
            //'use_time'	=> form_dropdown('use_time', $yesno, $this->settings['use_time']),
            'use_captcha'	=> form_dropdown('use_captcha', $yesno, $this->settings['use_captcha']),
            'redirect_timeout'	=> form_input('redirect_timeout', $this->settings['redirect_timeout']),
            'notification_email'	=> form_input('notification_email', $this->settings['notification_email']),
            'send_admin_notification'	=> form_dropdown('send_admin_notification', $yesno, $this->settings['send_admin_notification']),
            'send_owner_notification'	=> form_dropdown('send_owner_notification', $yesno, $this->settings['send_owner_notification']),
            'send_user_notification'	=> form_dropdown('send_user_notification', $yesno, $this->settings['send_user_notification'])
    		);
    	
    	if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('reeservation_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('reeservation_module_name'));
        }
        
    	return $this->EE->load->view('settings', $vars, TRUE);
	
    }    
    
    function save_settings()
    {
        
        $settings['default_status'] = (isset($_POST['default_status']))?$this->EE->security->xss_clean($_POST['default_status']):'open';
        $settings['allow_multiple_bookings'] = (isset($_POST['allow_multiple_bookings']))?$this->EE->security->xss_clean($_POST['allow_multiple_bookings']):'n';
        $settings['bookings_limit'] = (isset($_POST['bookings_limit']))?$this->EE->security->xss_clean($_POST['bookings_limit']):1;
        $settings['bookings_user_limit'] = (isset($_POST['bookings_user_limit']))?$this->EE->security->xss_clean($_POST['bookings_user_limit']):0;
        $settings['limit_field'] = (isset($_POST['limit_field']))?$this->EE->security->xss_clean($_POST['limit_field']):'';        
        $settings['booking_channels'] = (isset($_POST['booking_channels']))?$this->EE->security->xss_clean(implode(",",$_POST['booking_channels'])):'';
        //$settings['booking_entries'] = (isset($_POST['booking_entries']))?$this->EE->security->xss_clean(implode(",",$_POST['booking_entries'])):'';
        $settings['lock_timeout'] = (isset($_POST['lock_timeout']))?$this->EE->security->xss_clean($_POST['lock_timeout']):'0';//15
        $settings['allow_changeover_booking'] = (isset($_POST['allow_changeover_booking']))?$this->EE->security->xss_clean($_POST['allow_changeover_booking']):'n';//15
        $settings['standard_checkin_time'] = (isset($_POST['standard_checkin_time']))?$this->EE->security->xss_clean($_POST['standard_checkin_time']):'00:00 AM';
        $settings['use_time'] = (isset($_POST['use_time']))?$this->EE->security->xss_clean($_POST['use_time']):'n';
        $settings['use_captcha'] = (isset($_POST['use_captcha']))?$this->EE->security->xss_clean($_POST['use_captcha']):'n';
        $settings['redirect_timeout'] = (isset($_POST['redirect_timeout']))?$this->EE->security->xss_clean($_POST['redirect_timeout']):'0';
        $settings['notification_email'] = (isset($_POST['notification_email']))?$this->EE->security->xss_clean($_POST['notification_email']):'';
        
        $settings['send_user_notification'] = (isset($_POST['send_user_notification']))?$this->EE->security->xss_clean($_POST['send_user_notification']):'y';
        $settings['send_owner_notification'] = (isset($_POST['send_owner_notification']))?$this->EE->security->xss_clean($_POST['send_owner_notification']):'y';
        $settings['send_admin_notification'] = (isset($_POST['send_admin_notification']))?$this->EE->security->xss_clean($_POST['send_admin_notification']):'y';
        
 
        $this->EE->db->where('module_name', 'Reeservation');
        $this->EE->db->update('modules', array('settings' => serialize($settings)));
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=settings');
    }
    
    
    /**
     * Settings Form
     *
     * @param	Array	Settings
     * @return 	void
     */
    function email_templates()
    {
    	$site_id = $this->EE->config->item('site_id');
        
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');
        $this->EE->load->library('typography');
        $this->EE->typography->initialize();


    	$vars = array();
  
        $yesno = array(
                                    'y' => $this->EE->lang->line('yes'),
                                    'n' => $this->EE->lang->line('no')
                                );
                                
        $tmpls = array('reeservation_created_admin_notification', 'reeservation_created_owner_notification', 'reeservation_created_user_notification');
        //check for extension
        $q = $this->EE->db->query("SELECT settings FROM exp_extensions WHERE class='Reeservation_ext' AND hook='reeservation_booking_status_change'");
        if ($q->num_rows()>0)
        {
            if ($q->row('settings')=='')
            {
                $tmpls[] = 'reeservation_edited_user_notification';
            }
            else
            {
                $ext_settings = unserialize($q->row('settings'));
                if ($ext_settings['status_change_customer_notification']=='y') $tmpls[] = 'reeservation_edited_user_notification';
                if ($ext_settings['status_change_admin_notification']=='y') $tmpls[] = 'reeservation_edited_admin_notification';
                if ($ext_settings['status_change_owner_notification']=='y') $tmpls[] = 'reeservation_edited_owner_notification';
            }
        }
 
        $this->EE->db->where_in('template_name', $tmpls);
        $query = $this->EE->db->get('specialty_templates');
        foreach ($query->result_array() as $row)
        {
            $vars['data'][$row['template_name']] = array(	
                'data_title'	=> form_input("{$row['template_name']}"."[data_title]", $row['data_title'], 'style="width: 100%"'),
                'template_data'	=> form_textarea("{$row['template_name']}"."[template_data]", $row['template_data'])/*,
                'enable_template'	=> form_dropdown("{$row['template_name']}"."[enable_template]", $yesno, $row['enable_template'])*/
        		);
    	}
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('reeservation_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('reeservation_module_name'));
        }
        
    	return $this->EE->load->view('email_templates', $vars, TRUE);
	
    }    
    
    function save_email_templates()
    {
        
        $templates = array('reeservation_created_admin_notification', 'reeservation_created_owner_notification', 'reeservation_created_user_notification', 'reeservation_edited_user_notification', 'reeservation_edited_owner_notification', 'reeservation_edited_admin_notification');
        //var_dump($_POST);
        foreach ($templates as $template)
        {
            $data_title = (isset($_POST[$template]['data_title']))?$this->EE->security->xss_clean($_POST[$template]['data_title']):$this->EE->lang->line(str_replace('reeservation', 'subject', $template));
            $template_data = (isset($_POST[$template]['template_data']))?$this->EE->security->xss_clean($_POST[$template]['template_data']):$this->EE->lang->line(str_replace('reeservation', 'message', $template));
            $enable_template = (isset($_POST[$template]['enable_template']))?$this->EE->security->xss_clean($_POST[$template]['enable_template']):'y';
            
            $this->EE->db->where('template_name', $template);
            $this->EE->db->update('specialty_templates', array('data_title' => $data_title, 'template_data' => $template_data, 'enable_template' => $enable_template));
            //echo $this->EE->db->last_query();
        }       
        //exit();
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=email_templates');
    }
    
    function format_date($one=false, $two=false, $three=false)
    {
    	if ($this->EE->config->item('app_version')>=260)
    	{
			return $this->EE->localize->format_date($one, $two, $three);
		}
		else
		{
			return $this->EE->localize->decode_date($one, $two, $three);
		}
    }


}
/* END */
?>
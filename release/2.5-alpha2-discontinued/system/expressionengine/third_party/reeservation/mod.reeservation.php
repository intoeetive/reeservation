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
 File: mod.reeservation.php
-----------------------------------------------------
 Purpose: Booking / reservation engine
=====================================================
*/


if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}


class Reeservation {

    var $return_data	= ''; 						// Bah!
    
    var $settings 		= array();    
    

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 

        $this->EE->lang->loadfile('reeservation');  
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Reeservation' LIMIT 1");
        $this->settings = unserialize($query->row('settings'));      
    }
    /* END */
    

    /** ----------------------------------------
    /**  Submit 
    /** ----------------------------------------*/
    function submit()
    {
        /**
         * @todo
         * add support for booking of multiple entries
         */
        
        $ts = $this->EE->localize->now;
        //check for all the fields
        $errors = array();
        
        // -------------------------------------------
		// 'reeservation_booking_created_start' hook.
		//  - Do additional processing when reservation process just started
		//  - You can bing your CAPTCHA extension to it
		//
			if ($this->EE->extensions->active_hook('reeservation_booking_created_start') === TRUE)
			{
				$edata = $this->EE->extensions->call('reeservation_booking_created_start');
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------

        //valid entry_id
        $entry_ids = array();        
        if (is_array($_POST['entry_id']))
        {
            foreach ($_POST['entry_id'] as $entry_id)
            {
                $entry_ids[] = intval($this->EE->security->xss_clean($entry_id));
            }                
        } 
        else
        {
            $entry_ids[] = intval($this->EE->input->post('entry_id'));
        }            
        
        foreach ($entry_ids as $entry_id)
        {                
                    
        }                
        $entry_id = intval($this->EE->input->post('entry_id'));
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
                $booking_entries = explode(",", $this->settings['booking_entries']);
                if (!in_array($entry_id, $booking_entries))
                {
                    $errors[] = $this->EE->lang->line('invalid_booking_entry_id');
                }
                $owner_id = $query->row('author_id');
                $title = $query->row('title');
                $url_title = $query->row('url_title');
                $channel_name = $query->row('channel_name');
                $channel_title = $query->row('channel_title');
                $channel_url = $query->row('channel_url');
                $comment_url = $query->row('comment_url');
            }
        } 
        
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
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

        //valid user data
        $member_id = $this->EE->session->userdata('member_id');
        if ($member_id!=0)
        {
            $name = ($this->EE->input->post('name')!='')?$this->EE->input->post('name'):$this->EE->session->userdata('screen_name');
            $email = ($this->EE->input->post('email')!='')?$this->EE->input->post('email'):$this->EE->session->userdata('email');
        }
        else
        {
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
        }
        if ($name=='' || $email=='')
        {
            $errors[] = $this->EE->lang->line('missing_booking_userdata');
        }
        
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
        }
        
        //form hash
        if ($this->EE->config->item('secure_forms') == 'y')
		{
            $query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

			if ($query->row('count')  == 0)
			{
				$errors[] = $this->EE->lang->line('form_out_of_date');
			}
            
        }
        
        //CAPTCHA is correct
        if ($this->settings['use_captcha']=='y' && $this->EE->session->userdata('member_id') == 0)
        {
            if ( $_POST['captcha'] == '')
			{
				$errors[] = $this->EE->lang->line('captcha_required');
			}
			else
			{
				$this->EE->db->select('COUNT(*) AS count');
                $this->EE->db->from('captcha');
                $this->EE->db->where('word', $_POST['captcha']);
                $this->EE->db->where('ip_address', $this->EE->input->ip_address());
                $this->EE->db->where('date > ', 'UNIX_TIMESTAMP()-7200');
                $query = $this->EE->db->get();
				if ($query->row('count') == 0)
				{
					$errors[] = $this->EE->lang->line('captcha_incorrect');
				}
                
                
			}
        }
        
        
        
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
        }
        
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
            return $this->EE->output->show_user_error('submission', $errors);
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
        
        $date_from_human = str_pad($y, 4, "20", STR_PAD_LEFT)."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

        $date_from = $this->EE->localize->convert_human_date_to_gmt($date_from_human);
        /*if ($this->settings['use_time']=='n')
        {
            $date_from_human = gmdate("Y-m-d", $date_from);
            $date_from = $this->EE->localize->convert_human_date_to_gmt($date_from_human." ".$this->settings['standard_checkin_time']);
        }*/

        if ($date_from < $ts && $_POST['range_id']=='')
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

            $date_to = $this->EE->localize->convert_human_date_to_gmt($date_to_human);
        }
        
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
        }
        
        if ($date_from > $date_to)
        {
            $errors[] = $this->EE->lang->line('date_start_greater_date_end');
        }
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
        }
        
        //check for ranges
        
        //range explicitly provided?
        $range_id = (!empty($_POST['range_id']))?$this->EE->input->post('range_id'):'';
        $range_title = ''; 
        if ($range_id!='')
        {
            $this->EE->db->select('range_title, date_from, date_to, dow_from, dow_to, time_from, time_to');
            $this->EE->db->from('reeservation_range_entries');
            $this->EE->db->join('reeservation_range_dates', 'reeservation_range_entries.range_id=reeservation_range_dates.range_id', 'left');
            $this->EE->db->where('entry_id', $entry_id);
            $this->EE->db->where('reeservation_range_entries.range_id', $range_id);
            $this->EE->db->limit(1);
            $range_q = $this->EE->db->get();
            if ($range_q->num_rows()==0)
            {
                $errors[] = $this->EE->lang->line('range_not_available');
            }
            //if the date_to is set, we need to make sure the range is within the scope
            if ($date_from!=$date_to)
            {
                $range_error = false;
                if ($range_q->row('date_from')!='' && $range_q->row('date_from')<$date_from)
                {
                    $range_error = true;
                }
                if ($range_q->row('date_to')!='' && $range_q->row('date_to')>$date_to)
                {
                    $range_error = true;
                }
                if ($range_q->row('dow_from')!='')
                {
                    $dow_from = date('w', $date_from);
                    if ($range_q->row('dow_to')=='' || $range_q->row('dow_to')==$range_q->row('dow_from'))
                    {
                        if ($dow_from!=$range_q->row('dow_from'))
                        {
                            $range_error = true;
                        }
                    }
                    $days = round(($date_to - $date_from)/(24*60*60)+1);
                    if ($days<7)
                    {
                        $dow_to = date('w', $date_to);
                        if ($range_q->row('dow_from')<$dow_from || $range_q->row('dow_to')>$dow_to)
                        {
                            $range_error = true;
                        }
                    }
                }    
                
                if ($range_error == true)
                {
                    $errors[] = $this->EE->lang->line('range_out_of_scope');
                }
            }
            
            if (!empty($errors))
            {
                return $this->EE->output->show_user_error('submission', $errors);
            }
            $range_title = $range_q->row('range_title');
        }
        
        //anything booked already?
        $req_places = (intval($this->EE->input->post('places'))==0) ? 1 : intval($this->EE->input->post('places'));
        
        //standard check
        if ($range_id=='')
        {
            $sql = "SELECT booking_id, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ( (date_from>".($date_from-3800)." AND date_from<".($date_to+3800).") OR (date_to>".($date_from-3800)." AND date_to<".($date_to+3800).") )  AND status='open'";
            //you might wonder where 3800 comes from? that's 1 hour plus 200 seconds added because of possible time calculation fault
            $query = $this->EE->db->query($sql);
    
            $booked_places = 0;
            if ($query->num_rows()>0)
            {
                foreach ($query->result_array() as $row)
                {
                    $booked_places += $row['places'];
                }
            }
            
            //pending bookings?
            $pend_booked_places = 0;
            if (intval($this->settings['lock_timeout'])!=0)
            {
                $lock_timeout = $ts - intval($this->settings['lock_timeout'])*60;
    
                $sql = "SELECT booking_id, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ((date_from>$date_from AND date_from<$date_to) OR (date_to>$date_from AND date_to<$date_to))  AND status='pending' AND booking_date>$lock_timeout AND booking_date<$ts";
                $pend_query = $this->EE->db->query($sql);
                if ($pend_query->num_rows()>0)
                {
                    foreach ($pend_query->result_array() as $row)
                    {
                        $pend_booked_places += $row['places'];
                    }
                }
                
            }
        
        }
        else
        //ranges check
        {
            $sql = "SELECT booking_id, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ( (date_from>".($date_from-3800)." AND date_from<".($date_to+3800).") OR (date_to>".($date_from-3800)." AND date_to<".($date_to+3800).") ) AND range_id='".$range_id."' AND status='open'";
            //you might wonder where 3800 comes from? that's 1 hour plus 200 seconds added because of possible time calculation fault
            $query = $this->EE->db->query($sql);
    
            $booked_places = 0;
            if ($query->num_rows()>0)
            {
                foreach ($query->result_array() as $row)
                {
                    $booked_places += $row['places'];
                }
            }
            
            //pending bookings?
            $pend_booked_places = 0;
            if (intval($this->settings['lock_timeout'])!=0)
            {
                $lock_timeout = $ts - intval($this->settings['lock_timeout'])*60;
    
                $sql = "SELECT booking_id, places FROM exp_reeservation WHERE entry_id='".$this->EE->db->escape_str($entry_id)."' AND ((date_from>$date_from AND date_from<$date_to) OR (date_to>$date_from AND date_to<$date_to)) AND range_id='".$range_id."' AND status='pending' AND booking_date>$lock_timeout AND booking_date<$ts";
                $pend_query = $this->EE->db->query($sql);
                if ($pend_query->num_rows()>0)
                {
                    foreach ($pend_query->result_array() as $row)
                    {
                        $pend_booked_places += $row['places'];
                    }
                }
                
            }
        }
             
                                
        if ($this->settings['allow_multiple_bookings']=='y')
        {
            $avail_places = $bookings_limit - $booked_places - $req_places;
            if ($avail_places<0)
            {
                $errors[] = $this->EE->lang->line('bookings_limit_reached');
            }
            else
            {
                $pend_avail_places = $avail_places - $pend_booked_places;
                if ($pend_avail_places<0)
                {
                    $errors[] = $this->EE->lang->line('bookings_limit_pending_reached');
                }
            }
        }
        else
        {
            
            if ($query->num_rows()>0)
            {
                $errors[] = $this->EE->lang->line('booking_for_these_dates_exist');
            }
            else
            {
                //pending bookings?
                if (intval($this->settings['lock_timeout'])!=0)
                {
                    if ($pend_query->num_rows()>0)
                    {
                        $errors[] = $this->EE->lang->line('pending_booking_for_these_dates_exist');
                    }
                }
            }
        }
        
        //show errors
        if (!empty($errors))
        {
            return $this->EE->output->show_user_error('submission', $errors);
        }
        
        //clean XID and CAPTCHA
        $this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
        
        $this->EE->db->where('word', $this->EE->input->post('captcha'));
        $this->EE->db->where('ip_address', $this->EE->input->ip_address());
        $this->EE->db->or_where('date < ', 'UNIX_TIMESTAMP()-7200');
        $this->EE->db->delete('captcha');
        
        $status = $this->settings['default_status'];
        
        //all is fine? thank God! we can add the record
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
               'date_from' => $date_from,
               'date_to' => $date_to,
               'range_id' => $range_id,
               'status' => $status,
               'booking_date' => $ts
            );
        $this->EE->db->insert('reeservation', $data); 
        $booking_id = $this->EE->db->insert_id();
        
        
        // -------------------------------------------
		// 'reeservation_booking_created_end' hook.
		//  - Do additional processing when booking is created
		//  - Could be used to 'add to cart' for making payment
		//
			if ($this->EE->extensions->active_hook('reeservation_booking_created_end') === TRUE)
			{
				$edata = $this->EE->extensions->call('reeservation_booking_created_end', $booking_id, $data);
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------
        
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
                        'range_title'		=> $range_title,
						'status'	     	=> $this->EE->lang->line($status),
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
        if ($this->settings['send_user_notification']=='y')
        {
    		$template = $this->EE->functions->fetch_email_template('reeservation_created_user_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_from), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_to), $email_msg);
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

		//admin email
        
        $notification_email = reduce_multiples($this->settings['notification_email'], ",", TRUE); 
        $recipients = explode(",",$notification_email);     
        $recipients = array_unique($recipients); 
        $sent = array(); 
        
        if ($this->settings['send_admin_notification']=='y' && !empty($recipients))
        {
            $swap['booking_cp_link'] = $this->EE->config->item('cp_url').'?D=cp'.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=edit'.AMP.'id='.$booking_id;
            
    		$template = $this->EE->functions->fetch_email_template('reeservation_created_admin_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_from), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_to), $email_msg);
    			}
    		}
            
            foreach ($recipients as $recipient)
    		{
    			$this->EE->email->initialize();
    			$this->EE->email->wordwrap = false;
    			$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));	
    			$this->EE->email->to($recipient); 
    			$this->EE->email->subject($email_tit);	
    			$this->EE->email->message(entities_to_ascii($email_msg));		
    			$this->EE->email->Send();
    		}
            $sent[] = $recipient;
        }
        
        //owner email
        //only if did not receive admin notification 
        if ($this->settings['send_owner_notification']=='y' && !in_array($owner_email, $sent))
        {
    		$template = $this->EE->functions->fetch_email_template('reeservation_created_owner_notification');
    		$email_tit = $this->EE->functions->var_swap($template['title'], $swap);
    		$email_msg = $this->EE->functions->var_swap($template['data'], $swap);
            
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_from), $email_msg);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $email_msg, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $email_msg = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $date_to), $email_msg);
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
        
        // -------------------------------------------
		// 'reeservation_booking_created_absolute_end' hook.
		//  - Do additional processing when booking is created
		//  - Could be used to redirect to payment gateways
		//
			if ($this->EE->extensions->active_hook('reeservation_booking_created_absolute_end') === TRUE)
			{
				$edata = $this->EE->extensions->call('reeservation_booking_created_absolute_end', $booking_id, $data);
				if ($this->EE->extensions->end_script === TRUE) return $edata;
			}
		//
        // -------------------------------------------

        //...and do the redirect
        if (intval($this->settings['redirect_timeout']) != 0)
        {
			$data = array(	'title' 	=> $this->EE->lang->line('booking_received'),
							'heading'	=> $this->EE->lang->line('thank_you'),
							'content'	=> $this->EE->lang->line('booking_received_msg'),
							'redirect'	=> $this->EE->input->post('RET'),							
							'link'		=> array($this->EE->input->post('RET'), $this->EE->config->item('site_name')),
							'rate'		=> $this->settings['redirect_timeout']
						 );
					
			$this->EE->output->show_message($data);
		}
		else
		{
        	$this->EE->functions->redirect($this->EE->input->post('RET'));
    	}
        
        return;
    }    
    /* END */
    
    
    
    
    
    /** ----------------------------------------
    /**  Build  form
     * fields:
     * - date from
     * - date to (optional)
     * - entry_id
     * - member_id OR name+email
    /** ----------------------------------------*/
    function form()
    {
        $cond = array();
        $tagdata = '';
        if ($this->EE->TMPL->fetch_param('include_js'))
        {
            //reserved for future
        }
        $tagdata .= $this->EE->TMPL->tagdata;
        
        if ($this->EE->session->userdata('member_id')==0)
        {
            $cond['logged_in']=FALSE;
            $cond['logged_out']=TRUE;
        }
        else
        {
            $cond['logged_in']=TRUE;
            $cond['logged_out']=FALSE;
        }
        
        if ($this->settings['use_captcha']=='y' && $this->EE->session->userdata('member_id') == 0)
        {
            $cond['captcha']=TRUE;
            if (preg_match("/({captcha})/", $tagdata))
			{
				$tagdata = preg_replace("/{captcha}/", $this->EE->functions->create_captcha(), $tagdata);
			}
        }
        else
        {
            $cond['captcha']=FALSE;
        }
        
        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
        
        $RET = ($this->EE->TMPL->fetch_param('return') != "") ? ((strpos($this->EE->TMPL->fetch_param('return'), 'http')===0)?$this->EE->TMPL->fetch_param('return'):$this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'), FALSE)) : $this->EE->functions->fetch_current_uri();

		$hidden_fields = array(
								'ACT'	  	=> $this->EE->functions->fetch_action_id('Reeservation', 'submit'),
								'RET'	  	=> $RET
							  );
        if ($this->EE->TMPL->fetch_param('entry_id')!='')
        {
            $hidden_fields['entry_id'] = $this->EE->TMPL->fetch_param('entry_id');
        }
        
        $data = array(
						'hidden_fields'	=> $hidden_fields,
						'id'			=> ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'booking_form',
						'class'			=> ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : NULL
					);        
                    
        if (preg_match_all("/".LD."ranges".RD."(.*?)".LD."\/ranges".RD."/s", $tagdata, $matches))
		{
			if ($this->EE->TMPL->fetch_param('entry_id')!='')
            {
                $this->EE->db->select('reeservation_range_entries.range_id, price, range_title');
                $this->EE->db->from('reeservation_range_entries');
                $this->EE->db->join('reeservation_range_dates', 'reeservation_range_entries.range_id=reeservation_range_dates.range_id','left');
                $this->EE->db->where('entry_id', $this->EE->TMPL->fetch_param('entry_id'));
                $ranges = $this->EE->db->get();
                $out = '';
                $chunk = $matches[1][0];
                foreach ($ranges->result_array() as $row)
                {
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('range_id', $row['range_id'], $chunk);
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('range_price', $row['price'], $parsed_chunk);
                    $parsed_chunk = $this->EE->TMPL->swap_var_single('range_title', $row['range_title'], $parsed_chunk);
                    $out .= $parsed_chunk;
                }
                $tagdata = str_replace($matches[0][0], $out, $tagdata);
            }
            else
            {
                $tagdata = str_replace($matches[0][0], '', $tagdata);
            }
		}                        
        
        $tagdata = $this->EE->functions->form_declaration($data).stripslashes($tagdata)."</form>";
        
        return $tagdata;

    }
    /* END */
    
    /** ----------------------------------------
    /**  Display bookings
    /** ----------------------------------------*/
    function check($entry_id='', $day='', $month='', $year='', $tagdata='')
    {
        if ($this->settings['allow_multiple_bookings']=='y')
        {
            //do nothing, because we still want to display the booking info
        }
        
        if ($entry_id=='')
        {
            $entry_id = $this->EE->TMPL->fetch_param('entry_id');
        }
        $status_arr = ($this->EE->TMPL->fetch_param('status')) ? explode("|", $this->EE->TMPL->fetch_param('status')) : array('open');
        if ($day=='')
        {
            $day = ($this->EE->TMPL->fetch_param('day')!='')?$this->EE->TMPL->fetch_param('day'):$this->EE->TMPL->fetch_param('day_from');
        }
        if ($month=='')
        {
            $month = ($this->EE->TMPL->fetch_param('month')!='')?$this->EE->TMPL->fetch_param('month'):$this->EE->TMPL->fetch_param('month_from');
        }
        if ($year=='')
        {
            $year = ($this->EE->TMPL->fetch_param('year')!='')?$this->EE->TMPL->fetch_param('year'):$this->EE->TMPL->fetch_param('year_from');
        }
        
        if ($entry_id=='' || $day=='' || $month=='' || $year=='')
        {
            return false;
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
        
        $date_human = str_pad($year, 4, "20", STR_PAD_LEFT)."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($day, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;

        $date = $this->EE->localize->convert_human_date_to_gmt($date_human);
        
        if ($this->EE->TMPL->fetch_param('day_to')!='')
        {
            $day_to = $this->EE->TMPL->fetch_param('day_to');
            $month_to = ($this->EE->TMPL->fetch_param('month_to')!='')?$this->EE->TMPL->fetch_param('month_to'):$month;
            $year_to = ($this->EE->TMPL->fetch_param('year_to')!='')?$this->EE->TMPL->fetch_param('year_to'):$year;
            $date_to_human = str_pad($year_to, 4, "20", STR_PAD_LEFT)."-".str_pad($month_to, 2, "0", STR_PAD_LEFT)."-".str_pad($day_to, 2, "0", STR_PAD_LEFT)." ".$standard_checkin_time;
    
            $date_to = $this->EE->localize->convert_human_date_to_gmt($date_to_human);
        }
        
        //echo $date_human." ".$date." <br />";
        //$this->EE->db->_compile_select(); 
        $this->EE->db->select('*');
        $this->EE->db->from('reeservation');
        $this->EE->db->where('entry_id', $entry_id);
        $this->EE->db->where_in('status', $status_arr);
        $this->EE->db->where('date_from <=', $date+3800);
        $this->EE->db->where('date_to >=', $date-3800);
        if (isset($date_to))
        {
            $this->EE->db->or_where('(entry_id = '.$entry_id.' AND status IN ('."'".implode("','", $status_arr)."'".') AND date_from <= '.($date_to+3800).' AND date_to >= '.($date_to-3800).')');
        }
        
        /*$status_where = "(".implode(" OR ", $status_arr).")";*/
        
        
        $query = $this->EE->db->get();
        //echo $this->EE->db->last_query();
        //you might wonder where 3800 comes froms? that's 1 hour plus 200 seconds added because of possible time calculation fault

        if ($query->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        $tagdata = ($tagdata=='') ? $this->EE->TMPL->tagdata : $tagdata;
        
        if ($this->EE->session->userdata('member_id')==0)
        {
            $cond['logged_in']=FALSE;
            $cond['logged_out']=TRUE;
        }
        else
        {
            $cond['logged_in']=TRUE;
            $cond['logged_out']=FALSE;
        }
        
        $cond['booking_admin']=FALSE;
        if ($this->EE->session->userdata('group_id')==1)
        {
            $cond['booking_admin']=TRUE;
        }
        else
        {
            $this->EE->db->select('module_member_groups.module_id');
            $this->EE->db->from('module_member_groups');
            $this->EE->db->join('modules', 'module_member_groups.module_id = modules.module_id', 'left');
            $this->EE->db->where('module_member_groups.group_id', $this->EE->session->userdata('group_id'));
            $this->EE->db->where('modules.module_name', 'Reeservation');
			$check = $this->EE->db->get();
			
			if ($check->num_rows() > 0)
			{
                $cond['booking_admin']=TRUE;
			}
        }
        
        $booked_places = 0;
        
        //var_dump($cond);
        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
        
        preg_match_all("/".LD."booking_details".RD."(.*?)".LD."\/booking_details".RD."/s", $tagdata, $booking_details);

        $row_chunk = '';
        $row_inner = '';
        foreach($booking_details[0] as $row_key => $row_tag)
        {
        	$row_chunk = $booking_details[0][$row_key];
        
        	$row_chunk_content = $booking_details[1][$row_key];
        //var_dump($row_chunk_content);
        	$row_inner = '';
        
        	// loop over the row_data
        	foreach ($query->result_array() as $row)
        	{
            
                $row_template = $row_chunk_content;
                
                $booked_places += $row['places'];

                $row_template = $this->EE->TMPL->swap_var_single('booking_id', $row['booking_id'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('member_id', $row['member_id'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('owner_id', $row['owner_id'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('name', $row['name'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('email', $row['email'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('phone', $row['phone'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('contact', $row['contact'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('comment', $row['comment'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('status', $this->EE->lang->line($row['status']), $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('moderator_id', $row['moderator_id'], $row_template);
                $row_template = $this->EE->TMPL->swap_var_single('moderation_comment', $row['moderation_comment'], $row_template);
                if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $row_template, $matches))
        		{
        			for ($j = 0; $j < count($matches['0']); $j++)
        			{
                        $row_template = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['date_from']), $row_template);
        			}
        		}
                if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $row_template, $matches))
        		{
        			for ($j = 0; $j < count($matches['0']); $j++)
        			{
                        $row_template = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['date_to']), $row_template);
        			}
        		}
                if (preg_match_all("/".LD."booking_date\s+format=[\"'](.*?)[\"']".RD."/is", $row_template, $matches))
        		{
        			for ($j = 0; $j < count($matches['0']); $j++)
        			{
                        $row_template = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['booking_date']), $row_template);
        			}
        		}
                if (preg_match_all("/".LD."moderation_date\s+format=[\"'](.*?)[\"']".RD."/is", $row_template, $matches))
        		{
        			for ($j = 0; $j < count($matches['0']); $j++)
        			{
                        $row_template = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['moderation_date']), $row_template);
        			}
        		}
        
        		$row_inner .= $row_template;
            }  
            //echo $row_inner;
         }
         //echo $row_inner;

         $tagdata = str_replace($row_chunk, $row_inner, $tagdata);
         
        if ($this->settings['limit_field']!='')
        {
            $limit_field = 'field_id_'.$this->settings['limit_field'];
            $q = $this->EE->db->query("SELECT title, url_title, $limit_field FROM exp_channel_titles AS t, exp_channel_data AS d WHERE t.entry_id=d.entry_id AND t.entry_id=".$entry_id);
            $bookings_limit = (intval($this->settings['bookings_limit'])<intval($q->row("$limit_field")))?intval($this->settings['bookings_limit']):intval($q->row("$limit_field"));
        }
        else
        {
            $q = $this->EE->db->query("SELECT title, url_title FROM exp_channel_titles AS t WHERE t.entry_id=".$entry_id);
            $bookings_limit = intval($this->settings['bookings_limit']);
        }
        if ($bookings_limit<=0) $bookings_limit = 1;
         
         $tagdata = $this->EE->TMPL->swap_var_single('bookings_limit', $bookings_limit, $tagdata);
         $tagdata = $this->EE->TMPL->swap_var_single('entry_id', $entry_id, $tagdata);
         $tagdata = $this->EE->TMPL->swap_var_single('title', $q->row('title'), $tagdata);
         $tagdata = $this->EE->TMPL->swap_var_single('url_title', $q->row('url_title'), $tagdata);
         $tagdata = $this->EE->TMPL->swap_var_single('bookings_count', $booked_places, $tagdata);
         
         
         $available = $bookings_limit - $booked_places;
         $tagdata = $this->EE->TMPL->swap_var_single('available', $available, $tagdata);        
        
        return $tagdata;
    }
    /* END */
    
    /* list booking by parameters */
    function listing()
    {
        
        $valid_params = array('owner_id', 'member_id', 'entry_id', 'booking_id');
        $params = array();
        foreach ($valid_params as $param)
        {
            if ($this->EE->TMPL->fetch_param($param)!='')
            {
                $params[$param] = $this->EE->TMPL->fetch_param($param);
            }
        }
        if (isset($params['member_id']) && ($params['member_id']=='{member_id}' || $params['member_id']=='{logged_in_member_id}'))
        {
            $params['member_id']= $this->EE->session->userdata['member_id'];
        }
        $status_arr = ($this->EE->TMPL->fetch_param('status')) ? explode("|", $this->EE->TMPL->fetch_param('status')) : array('open');
        
        if (empty($params))
        {
            return false;
        }
        
        //$this->EE->db->_compile_select(); 
        $this->EE->db->select('reeservation.*, exp_channel_titles.title, exp_channel_titles.url_title');
        $this->EE->db->from('reeservation');
        $this->EE->db->join('exp_channel_titles', 'reeservation.entry_id = exp_channel_titles.entry_id', 'left');
        foreach ($params as $param=>$value)
        {
            $this->EE->db->where("reeservation.".$param, $value);
        }
        /*$status_where = "(".implode(" OR ", $status_arr).")";*/
        $this->EE->db->where_in('reeservation.status', $status_arr);
        $query = $this->EE->db->get();
        //echo $this->EE->db->last_query();
        

        if ($query->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        
        $tagdata = $this->EE->TMPL->tagdata;
        
        if ($this->EE->session->userdata('member_id')==0)
        {
            $cond['logged_in']=FALSE;
            $cond['logged_out']=TRUE;
        }
        else
        {
            $cond['logged_in']=TRUE;
            $cond['logged_out']=FALSE;
        }
        
        $cond['booking_admin']=FALSE;
        if ($this->EE->session->userdata('group_id')==1)
        {
            $cond['booking_admin']=TRUE;
        }
        else
        {
            $this->EE->db->select('module_member_groups.module_id');
            $this->EE->db->from('module_member_groups');
            $this->EE->db->join('modules', 'module_member_groups.module_id = modules.module_id', 'left');
            $this->EE->db->where('module_member_groups.group_id', $this->EE->session->userdata('group_id'));
            $this->EE->db->where('modules.module_name', 'Reeservation');
			$check = $this->EE->db->get();
			
			if ($check->num_rows() > 0)
			{
                $cond['booking_admin']=TRUE;
			}
        }
        
        $booked_places = 0;
        
        //var_dump($cond);
        $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
        $tagdata_tmp = $tagdata;
        $out = '';
        
    	foreach ($query->result_array() as $row)
    	{
            $tagdata = $tagdata_tmp;
            $tagdata = $this->EE->TMPL->swap_var_single('entry_id', $row['entry_id'], $tagdata);
             $tagdata = $this->EE->TMPL->swap_var_single('title', $row['title'], $tagdata);
             $tagdata = $this->EE->TMPL->swap_var_single('url_title', $row['url_title'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('booking_id', $row['booking_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('places', $row['places'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('member_id', $row['member_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('owner_id', $row['owner_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('name', $row['name'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('email', $row['email'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('phone', $row['phone'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('contact', $row['contact'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('comment', $row['comment'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('status', $this->EE->lang->line($row['status']), $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('moderator_id', $row['moderator_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('moderation_comment', $row['moderation_comment'], $tagdata);
            if (preg_match_all("/".LD."date_from\s+format=[\"'](.*?)[\"']".RD."/is", $tagdata, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $tagdata = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['date_from']), $tagdata);
    			}
    		}
            if (preg_match_all("/".LD."date_to\s+format=[\"'](.*?)[\"']".RD."/is", $tagdata, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $tagdata = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['date_to']), $tagdata);
    			}
    		}
            if (preg_match_all("/".LD."booking_date\s+format=[\"'](.*?)[\"']".RD."/is", $tagdata, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $tagdata = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['booking_date']), $tagdata);
    			}
    		}
            if (preg_match_all("/".LD."moderation_date\s+format=[\"'](.*?)[\"']".RD."/is", $tagdata, $matches))
    		{
    			for ($j = 0; $j < count($matches['0']); $j++)
    			{
                    $row_template = str_replace($matches['0'][$j], $this->EE->localize->decode_date($matches['1'][$j], $row['moderation_date']), $tagdata);
    			}
    		}
            
            if ($this->settings['limit_field']!='')
            {
                $limit_field = 'field_id_'.$this->settings['limit_field'];
                $q = $this->EE->db->query("SELECT $limit_field FROM exp_channel_data AS d WHERE d.entry_id=".$row['entry_id']);
                $bookings_limit = (intval($this->settings['bookings_limit'])<intval($q->row("$limit_field")))?intval($this->settings['bookings_limit']):intval($q->row("$limit_field"));
            }
            else
            {
                $bookings_limit = intval($this->settings['bookings_limit']);
            }
            if ($bookings_limit<=0) $bookings_limit = 1;
             
             $tagdata = $this->EE->TMPL->swap_var_single('bookings_limit', $bookings_limit, $tagdata);
            
            $out .= $tagdata;
            
        }  

         
        if (($this->EE->TMPL->fetch_param('form')=='yes'||$this->EE->TMPL->fetch_param('form')=='true'||$this->EE->TMPL->fetch_param('form')=='on') && $this->EE->TMPL->fetch_param('booking_id')!='')
        {
            if ($cond['booking_admin']!=TRUE)
            {
                if ($query->row('owner_id')!=$this->EE->session->userdata('member_id'))
                {
                    return $this->EE->output->show_user_error('general', array($this->EE->lang->line('can_not_edit')));
                }
            }
            
            $RET = ($this->EE->TMPL->fetch_param('return') != "") ? ((strpos($this->EE->TMPL->fetch_param('return'), 'http')===0)?$this->EE->TMPL->fetch_param('return'):$this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'), FALSE)) : $this->EE->functions->fetch_current_uri();
    		$XID = '';

    		$hidden_fields = array(
    								'ACT'	  	=> $this->EE->functions->fetch_action_id('Reeservation', 'save_booking'),
    								'RET'	  	=> $RET,
    								'XID'	  	=> $XID,
    								'booking_id' 	=> $this->EE->TMPL->fetch_param('booking_id')
    							  );
            $data = array(
						'hidden_fields'	=> $hidden_fields,
						'id'			=> ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'booking_edit_form',
						'class'			=> ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : NULL
					);
            $out = $this->EE->functions->form_declaration($data).stripslashes($out)."</form>";
        }
         
         
        
        
        return $out;
    }
    /* END */
    
    
    function save_booking()
    {

        $status = (isset($_POST['status']))?$this->EE->security->xss_clean($_POST['status']):$this->settings['default_status'];
        $moderator_comment = (isset($_POST['moderator_comment']))?$this->EE->security->xss_clean($_POST['moderator_comment']):'';
        $includecomment = (isset($_POST['include_comment']) && $_POST['include_comment']=='y') ? TRUE : FALSE;
        
        if (!isset($_POST['booking_id']))
        {
            return $this->EE->output->show_user_error('general', array($this->EE->lang->line('missing_booking_id')));
        }

        $cond['booking_admin']=FALSE;
        if ($this->EE->session->userdata('group_id')==1)
        {
            $cond['booking_admin']=TRUE;
        }
        else
        {
            $this->EE->db->select('owner_id');
            $this->EE->db->where('booking_id', $_POST['booking_id']);
            $check = $this->EE->db->get();
            if ($check->row('owner_id')==$this->EE->session->userdata('member_id'))
            {
                $cond['booking_admin']=TRUE;
            }
            else
            {
                
                $this->EE->db->select('module_member_groups.module_id');
                $this->EE->db->from('module_member_groups');
                $this->EE->db->join('modules', 'module_member_groups.module_id = modules.module_id', 'left');
                $this->EE->db->where('module_member_groups.group_id', $this->EE->session->userdata('group_id'));
                $this->EE->db->where('modules.module_name', 'Reeservation');
    			$check = $this->EE->db->get();
    			
    			if ($check->num_rows() > 0)
    			{
                    $cond['booking_admin']=TRUE;
    			}
            }
        }
        
        if ($cond['booking_admin']==FALSE)
        {
            return $this->EE->output->show_user_error('general', array($this->EE->lang->line('can_not_edit')));
        }
        
        if ($this->EE->config->item('secure_forms') == 'y')
		{
            $query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_security_hashes WHERE hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");
			$this->EE->db->query("DELETE FROM exp_security_hashes WHERE (hash='".$this->EE->db->escape_str($_POST['XID'])."' AND ip_address = '".$this->EE->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
            if ($query->row('count')  == 0)
			{
				return $this->EE->output->show_user_error('general', array($this->EE->lang->line('form_out_of_date')));
			}
        }
        
        
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
        
        return $this->EE->functions->redirect($_POST['RET']);
    }

}
/* END */

/**
* TODO:
* - setting to require email confirmation
*/
?>
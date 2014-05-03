<?php

/*
=====================================================
 Simple calendar
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011 Yuri Salimovskiy
=====================================================
 This software is based upon and derived from
 ExpressionEngine software protected under
 copyright dated 2004 - 2010. Please see
 http://expressionengine.com/docs/license.html
=====================================================
 File: pi.simple_calendar.php
-----------------------------------------------------
 Purpose: Simple monthly calendar. 
===================================================== 
*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  'pi_name' => 'Simple calendar',
  'pi_version' =>'1.2',
  'pi_author' =>'Yuri Salimovskiy',
  'pi_author_url' => 'http://www.intoeetive.com/',
  'pi_description' => 'Simple monthly calendar',
  'pi_usage' => Simple_calendar::usage()
  );

class Simple_calendar {
    
    var $return_data = '';
    
    function Simple_calendar()
    {
    
        $this->EE =& get_instance(); 
        
        $month = ($this->EE->TMPL->fetch_param('month')!='') ? $this->EE->TMPL->fetch_param('month') : date("n");
        $year = ($this->EE->TMPL->fetch_param('year')!='') ? $this->EE->TMPL->fetch_param('year') : date("Y");
        $start_day = (strtolower($this->EE->TMPL->fetch_param('start_day')=='monday')) ? 'monday' : 'sunday';
        
        $tagdata = $this->EE->TMPL->tagdata;

        /* table heading */
        $weekday_abrev = array(lang('Mo'), lang('Tu'), lang('We'), lang('Th'), lang('Fr'), lang('Sa'));
        $weekday_short = array(lang('Mon'), lang('Tue'), lang('Wed'), lang('Thu'), lang('Fri'), lang('Sat'));
        $weekday_long = array(lang('Monday'), lang('Tuesday'), lang('Wednesday'), lang('Thursday'), lang('Friday'), lang('Saturday'));
        if ($start_day=='sunday')
        {
            array_unshift($weekday_abrev, lang('Su'));
            array_unshift($weekday_short, lang('Sun'));
            array_unshift($weekday_long, lang('Sunday'));
        }
        else
        {
            array_push($weekday_abrev, lang('Su'));
            array_push($weekday_short, lang('Sun'));
            array_push($weekday_long, lang('Sunday'));
        }
        
        if (preg_match("/".LD."calendar_heading".RD."(.*?)".LD.'\/'."calendar_heading".RD."/s", $tagdata, $match))
		{
			$temp = '';

			for ($i = 0; $i < 7; $i ++)
			{
				$temp .= str_replace(array( LD.'lang:weekday_abrev'.RD,
                                            LD.'lang:weekday_short'.RD,
											LD.'lang:weekday_long'.RD),
									 array( $weekday_abrev[$i],
                                            $weekday_short[$i],
									 		$weekday_long[$i]),
				                    $match['1']);
			}

			$tagdata = preg_replace ("/".LD."calendar_heading".RD.".*?".LD.'\/'."calendar_heading".RD."/s", $temp, $tagdata);
		}
        
        if ($start_day=='sunday')
        {
            $day_counter_start = 0;
            $running_day = date('w',mktime(0,0,0,$month,1,$year));
            $end_of_the_week = 6;
        }
        else
        {
            $day_counter_start = 1;
            $running_day = date('N',mktime(0,0,0,$month,1,$year));
            $end_of_the_week = 7;
        }
        $days_in_month = date('t',mktime(0,0,0,$month,1,$year));

        $days_in_this_week = 0;
        $day_counter = 0;
        $dates_array = array();  
        
        if (preg_match("/".LD."reeservation_check".RD."(.*?)".LD.'\/'."reeservation_check".RD."/s", $tagdata, $match))
		{
            if ( ! class_exists('Reeservation'))
        	{
        		require PATH_THIRD.'reeservation/mod.reeservation.php';
        	}
        	
        	$RSRV = new Reeservation();  
        }
        
        if (preg_match("/".LD."calendar_rows".RD."(.*?)".LD.'\/'."calendar_rows".RD."/s", $tagdata, $match))
		{
			$tmpl = $match['1'];
            $calendar_rows = '';
            
            
            if (preg_match("/".LD."row_start".RD."(.*?)".LD.'\/'."row_start".RD."/s", $tmpl, $match))
			{
                $calendar_rows .= $match['1'];				
			}
            
            /* print "blank" days until the first of the current week */
            for($x = $day_counter_start; $x < $running_day; $x++) 
            {
                $row = $tmpl;
                
                if (preg_match("/".LD."if empty".RD."(.*?)".LD.'\/'."if".RD."/s", $tmpl, $match))
    			{
    				$row = $match['1'];
    			}
                else
                {
                    $row = $this->EE->TMPL->swap_var_single('day_number', '', $row);
                    $row = $this->EE->TMPL->swap_var_single('day', '', $row);
                    $row = $this->EE->TMPL->swap_var_single('month', '', $row);
                    $row = $this->EE->TMPL->swap_var_single('year', '', $row);
                    $row = $this->EE->TMPL->swap_var_single('day_of_week', '', $row);
                    
                    
                    if (preg_match("/".LD."row_start".RD."(.*?)".LD.'\/'."row_start".RD."/s", $tmpl, $match))
        			{
                        $row = str_replace ($match['0'], "", $row);				
        			}
                    
                    if (preg_match("/".LD."row_end".RD."(.*?)".LD.'\/'."row_end".RD."/s", $tmpl, $match))
        			{
                        $row = str_replace ($match['0'], "", $row);			
        			}
                    
                    if (preg_match("/".LD."if today".RD."(.*?)".LD.'\/'."if".RD."/s", $tmpl, $match))
        			{
        				$row = str_replace ($match['0'], '', $row);
        			}
                    
                }
                
                $calendar_rows .= $row;
                $days_in_this_week++;
            
            }//endfor
            
            /* keep going with days */

            for ($list_day=1; $list_day<=$days_in_month; $list_day++) 
            {
                $row = $tmpl;
                
                if (preg_match("/".LD."if today".RD."(.*?)".LD.'\/'."if".RD."/s", $tmpl, $match))
    			{
    				if($list_day == date("j",mktime(0,0,0,$month)) && $month == date("n") && $year == date("Y")) 
                    {
                        $row = str_replace ($match['0'], $match['1'], $row);
                    }
                    else
                    {
                        $row = str_replace ($match['0'], '', $row);
                    }
    			}
                
                if (preg_match("/".LD."reeservation_check".RD."(.*?)".LD.'\/'."reeservation_check".RD."/s", $row, $match))
        		{
                	$chunk = $RSRV->check($this->EE->TMPL->fetch_param('entry_id'), $list_day, $month, $year, $match['1']);
                    $row = str_replace ($match['0'], $chunk, $row);	  
                }
                
                $row = $this->EE->TMPL->swap_var_single('day_number', $list_day, $row);
                $row = $this->EE->TMPL->swap_var_single('day', $list_day, $row);
                $row = $this->EE->TMPL->swap_var_single('month', $month, $row);
                $row = $this->EE->TMPL->swap_var_single('year', $year, $row);
                
                $gmt = ($this->EE->config->item('app_version')<260)?$this->EE->localize->convert_human_date_to_gmt($year."-".$month."-".$list_day." 10:10 AM"):$this->EE->localize->string_to_timestamp($year."-".$month."-".$list_day." 10:10 AM");
                $day_of_week = gmdate("N", $gmt);
                
                $row = $this->EE->TMPL->swap_var_single('day_of_week', $day_of_week, $row);
                
                
                if (preg_match("/".LD."row_start".RD."(.*?)".LD.'\/'."row_start".RD."/s", $tmpl, $match))
    			{
                    if ($days_in_this_week == 0 && $list_day < $days_in_month)
                    {
                        $row = str_replace ($match['0'], $match['1'], $row);	
                    }
                    else
                    {
                        $row = str_replace ($match['0'], "", $row);	
                    }			
    			}
                
                if (preg_match("/".LD."row_end".RD."(.*?)".LD.'\/'."row_end".RD."/s", $tmpl, $match))
    			{
                    if ($days_in_this_week==6)
                    {
                        $row = str_replace ($match['0'], $match['1'], $row);
                    }
                    else
                    {
                        $row = str_replace ($match['0'], "", $row);		
                    }
    			}
                
                $calendar_rows .= $row;
                if ($days_in_this_week==6)
                {
                    $days_in_this_week = 0;		
                }
                else
                {
                    $days_in_this_week++;
                } 
                
    
            }//endfor 
            
            /* finish the rest of the days in the week */
            if($days_in_this_week < 6 && $days_in_this_week!=0) 
            {        
                $row = $tmpl;
                
                for($x=1;$x <= (7-$days_in_this_week);$x++) 
                {
                    
                    
                    if (preg_match("/".LD."if empty".RD."(.*?)".LD.'\/'."if".RD."/s", $tmpl, $match))
        			{
        				$row = $match['1'];
        			}
                    else
                    {
                        $row = $this->EE->TMPL->swap_var_single('day_number', '', $row);
                        $row = $this->EE->TMPL->swap_var_single('day', '', $row);
                        $row = $this->EE->TMPL->swap_var_single('month', '', $row);
                        $row = $this->EE->TMPL->swap_var_single('year', '', $row);
                        
                        if (preg_match("/".LD."row_start".RD."(.*?)".LD.'\/'."row_start".RD."/s", $tmpl, $match))
            			{
            				$row = str_replace ($match['0'], "", $row);						
            			}
                        
                        if (preg_match("/".LD."row_end".RD."(.*?)".LD.'\/'."row_end".RD."/s", $tmpl, $match))
            			{
                            $row = str_replace ($match['0'], "", $row);			
            			}
                        
                        if (preg_match("/".LD."if today".RD."(.*?)".LD.'\/'."if".RD."/s", $tmpl, $match))
            			{
            				$row = str_replace ($match['0'], '', $row);
            			}
                    }
                    
                    $calendar_rows .= $row;            
            
                }     
                
                if (preg_match("/".LD."row_end".RD."(.*?)".LD.'\/'."row_end".RD."/s", $tmpl, $match))
    			{
                    $calendar_rows .= $match['1'];			
    			}
                      
            }//endif
            

			$tagdata = preg_replace ("/".LD."calendar_rows".RD.".*?".LD.'\/'."calendar_rows".RD."/s", $calendar_rows, $tagdata);
		}
        
        $next_month = ($month+1<=12)?($month+1):1;
        $next_year = ($next_month==1)?($year+1):$year;
        $prev_month = ($month-1>0)?($month-1):12;
        $prev_year = ($prev_month==12)?($year-1):$year;
        $tagdata = $this->EE->TMPL->swap_var_single('next_month', $next_month, $tagdata);
        $tagdata = $this->EE->TMPL->swap_var_single('next_year', $next_year, $tagdata);
        $tagdata = $this->EE->TMPL->swap_var_single('prev_month', $prev_month, $tagdata);
        $tagdata = $this->EE->TMPL->swap_var_single('prev_year', $prev_year, $tagdata);

        $this->return_data = $tagdata;
        
        return $this->return_data;
    }
  

  
  // ----------------------------------------
  //  Plugin Usage
  // ----------------------------------------

  // This function describes how the plugin is used.
  //  Make sure and use output buffering

  function usage()
  {
	  ob_start(); 
	  ?>
	This is a simple monthly calendar displaying the dates. The plugin is designed for integration with rEEservation bookings calendar, but can also be used with other add-ons or stand-alone.
	
{exp:simple_calendar start_day="sunday" month="{current_time format='%n'}" year="{current_time format='%Y'}"}
<h4>{current_time format="%F %Y"}</h4>
<a href="{path=cal/{prev_month}/{prev_year}}">Prev month</a> <a href="{path=cal/{next_month}/{next_year}}">Next month</a>
<table class="calendar" border="0" cellpadding="6" cellspacing="1" width="300px">
<tr>
{calendar_heading}
<th title="{lang:weekday_long}">{lang:weekday_abrev}</th>
{/calendar_heading}
</tr>
{calendar_rows}
{row_start}<tr>{/row_start}
<td{if today} class="today"{/if}>{day_number} ({day}/{month}/{year})</td>
{if empty}<td>&nbsp;</td>{/if}
{row_end}</tr>{/row_end}
{/calendar_rows}
</table>
{/exp:simple_calendar} 

All parameters are optional.
	
	  <?php
	  $buffer = ob_get_contents();
		
	  ob_end_clean(); 
	
	  return $buffer;
  }
  // END

}
?>
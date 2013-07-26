<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=range_save', array('id'=>'reeservation_ranges_form'));?>

<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index'?>"><?=lang('bookings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking'?>"><?=lang('create_booking')?></a>  </li> 
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=email_templates'?>"><?=lang('email_templates')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=ranges'?>"><?=lang('time_ranges')?></a>  </li> 
</ul> 
<div class="clear_left shun"></div> 



<?php
$this->table->set_template($cp_table_template);
$this->table->set_heading(
    array('data' => lang('property'), 'style' => 'width:50%;'),
    lang('value')
);


foreach ($ranges as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

$this->table->clear();
?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>


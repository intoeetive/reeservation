<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=save_settings', array('id'=>'reeservation_settings_form'));?>

<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index'?>"><?=lang('bookings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking'?>"><?=lang('create_booking')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=email_templates'?>"><?=lang('email_templates')?></a>  </li> 
</ul> 
<div class="clear_left shun"></div> 

<?php 
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);


foreach ($settings as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

?>
<?php $this->table->clear()?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?php
form_close();


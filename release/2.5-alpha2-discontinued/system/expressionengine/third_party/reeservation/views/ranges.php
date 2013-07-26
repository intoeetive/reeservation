<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=save_settings', array('id'=>'reeservation_settings_form'));?>

<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index'?>"><?=lang('bookings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking'?>"><?=lang('create_booking')?></a>  </li> 
<li class="content_tab "> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=email_templates'?>"><?=lang('email_templates')?></a>  </li> 
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=ranges'?>"><?=lang('time_ranges')?></a>  </li> 
</ul> 
<div class="clear_left shun"></div> 

<?php if ($total_count == 0):?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_ranges_defined')?></p>
	</div>
<?php else:?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading($table_headings);

		echo $this->table->generate($ranges);
	?>



<?php endif; /* if $total_count > 0*/?>



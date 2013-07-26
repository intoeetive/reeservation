<?php 
if ($cp_notification!='')
{
    echo '<p class="notice">'.$cp_notification.'<br /></p>';
}
?>

<ul class="tab_menu" id="tab_menu_tabs">
<li class="content_tab current"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index'?>"><?=lang('bookings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=create_booking'?>"><?=lang('create_booking')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=settings'?>"><?=lang('settings')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=email_templates'?>"><?=lang('email_templates')?></a>  </li> 
<li class="content_tab"> <a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=ranges'?>"><?=lang('time_ranges')?></a>  </li> 
</ul> 
<div class="clear_left shun"></div> 

<div id="filterMenu">
	<fieldset>
		<legend><?=lang('search_bookings')?></legend>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=reeservation'.AMP.'method=index', array('id'=>'reeservation_search_form'));?>

		<div class="group">
			<?=lang('channel').NBS.NBS.form_dropdown('channel_id', $channels, $selected['channel_id']).NBS.NBS?>
			<?=lang('entry').NBS.NBS.form_dropdown('entry_id', $entries, $selected['entry_id']).NBS.NBS?>
            <?=lang('owner').NBS.NBS.form_dropdown('owner_id', $owners, $selected['owner_id']).NBS.NBS?>
            <?=lang('status').NBS.NBS.form_dropdown('status', $statuses, $selected['status']).NBS.NBS?>
            <br />
            <?=lang('author').NBS.NBS.form_dropdown('member_id', $authors, $selected['member_id']).NBS.NBS?>
            <?php $data = array(
              'name'        => 'email',
              'value'       => $selected['email'],
              'size'        => '80',
              'style'       => 'width:200px'
            );?>
            <?=lang('email').NBS.NBS.form_input($data)?>
            <br />
            <?php $data = array(
              'name'        => 'date_from',
              'value'       => $selected['date_from'],
              'size'        => '25',
              'id'          => 'date_from',
              'style'       => 'width:120px'
            );?>
            <?=lang('dates_from').NBS.NBS.form_input($data)?>
            <?php $data = array(
              'name'        => 'date_to',
              'value'       => $selected['date_to'],
              'size'        => '25',
              'id'          => 'date_to',
              'style'       => 'width:120px'
            );?>
            <?=lang('_to').NBS.NBS.form_input($data)?>

		</div>


		<div>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
		</div>

	<?=form_close()?>
	</fieldset>
</div>

<div style="padding: 10px;">

<?php if ($total_count == 0):?>
	<div class="tableFooter">
		<p class="notice"><?=lang('no_entries_matching_that_criteria')?></p>
	</div>
<?php else:?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading($table_headings);

		echo $this->table->generate($bookings);
	?>



<span class="pagination"><?=$pagination?></span>


<?php endif; /* if $total_count > 0*/?>

</div>



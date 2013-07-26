<?php
echo form_fieldset('');

$this->table->set_template($cp_table_template);

$this->table->set_heading(array('', lang('booking_ranges'), lang('price')));

echo $this->table->generate($table);
    
echo form_fieldset_close();
?>
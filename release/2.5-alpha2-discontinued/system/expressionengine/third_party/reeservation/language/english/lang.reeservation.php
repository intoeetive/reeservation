<?php

$lang = array(

//----------------------------------------
// Required for MODULES page
//----------------------------------------

"reeservation_module_name" =>
"rEEservation",

"reeservation_module_description" =>
"Booking / reservation engine",

//----------------------------------------
//initial email templates

"reeservation_created_admin_notification"  =>
"Booking created admin notification",

"reeservation_created_owner_notification"  =>
"Booking created owner notification",

"reeservation_created_user_notification"  =>
"Booking created user notification",

"reeservation_edited_user_notification"  =>
"Booking edited user notification",

"subject_created_admin_notification"  =>
"A new booking has been made",

"message_created_admin_notification"  =>
"A new booking has been made for {title} ({permalink}) in {channel_title}

Details:
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"} {range_title}
Name: {name}
Email: {email}
Phone: {phone}

{booking_cp_link}

{site_name}
{site_url}
",

"subject_created_owner_notification"  =>
"A new booking has been made",

"message_created_owner_notification"  =>
"A new booking has been made for {title} ({permalink}) in {channel_title} at {site_name}

Details:
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"}
Name: {name}
Email: {email}
Phone: {phone}

Booking ID: {booking_id}

{site_name}
{site_url}
",

"subject_created_user_notification"  =>
"Your booking at {site_name}",

"message_created_user_notification"  =>
"Hello {name},

we have just received your booking request for {title} ({permalink}) in {channel_title}
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"}
{range_title}

Status of your booking is {status}

{site_name}
{site_url}
",

"subject_edited_user_notification"  =>
"Your booking has been reviewed",

"message_edited_user_notification"  =>
"Hello {name},

our staff have revieved the booking you made for {title} ({permalink}) in {channel_title}
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"}
{range_title}

Status of your booking is now {status}

{comment}

{site_name}
{site_url}",

//----------------------------------------
// search

"search_bookings"  =>
"Search bookings",

"author"  =>
"Customer",

"owner"  =>
"Owner",

"email"  =>
"Email address",

"dates_from"  =>
"Dates from",

"_to"  =>
"to",

//----------------------------------------
// error messages

"missing_booking_entry_id"  =>
"The object for booking is not provided",

"invalid_booking_entry_id"  =>
"This object is not available for booking",

"missing_booking_userdata"  =>
"You need to provide your name and email",

"missing_dates"  =>
"The booking dates are missing",

"wrong_date_format"  =>
"Could not recognize the date you provided",


"date_is_in_the_past"  =>
"The booking date cannot be in the past",

"date_start_greater_date_end"  =>
"The booking start date is later then booking end date",

"booking_for_these_dates_exist"  =>
"The object is already booked for the period selected",

"pending_booking_for_these_dates_exist"  =>
"Someone is trying to book same object for the same dates (or time range). You may try later or book another object.",

"invalid_email"  =>
"The email address you provided is not valid",

"form_out_of_date"  =>
"Too much time passed since the form has been generated. Please try again.",

"bookings_limit_reached"  =>
"The object has reached maximum number of bookings for dates selected.",

"bookings_limit_pending_reached"  =>
"The object has reached maximum number of bookings for dates selected, but not all of them are confirmed. You may try again later.",

"nothing_to_book" =>
"There are no booking objects set in your settings",

//----------------------------------------

"bookings"  =>
"Bookings",

"settings"  =>
"Settings",

"default_status"    =>
"Default booking status",

"open"  =>
"Open / approved",

"cancelled"  =>
"Cancelled by user",

"rejected"  =>
"Rejected",

"pending"  =>
"Pending payment / approval",

"booking_channels"  =>
"Channels with booking-enabled entries",

"booking_entries"  =>
"Booking-enabled entries",

"allow_multiple_bookings"  =>
"Allow multiple bookings for the same date",

"bookings_limit"  =>
"Limit of bookings per object",

"lock_timeout"  =>
"Lock pending bookings for ... minutes",

"standard_checkin_time"  =>
"Standard checkin/checkout time (HH:mm)",

"use_time"  =>
"Let people set their own checkin/checkout time?",

"email_templates"  =>
"Email templates",

"data_title"  =>
"Message subject",

"template_data"  =>
"Message text",

"enable_template"  =>
"Enable this notification?",

"no_entries_matching_that_criteria"  =>
"There are no bookings matching the criteria you selected",

"use_captcha"  =>
"Use CAPTCHA?",

"notification_email"  =>
"Email addresses for admin notification",

"send_admin_notification"  =>
"Send notification to admin addresses above?",

"send_owner_notification"  =>
"Send notification to object owner?",

"send_user_notification"  =>
"Send notification to customer?",

"redirect_timeout"  =>
"Time to show success message before redirect, seconds",

"booking_received"  =>
"Booking request received",

"thank_you"  =>
"Thank you for your booking",

"booking_received_msg"  =>
"Your booking has been received. \nOur staff has been notified about it. \nThe details of your order have been sent to your email.",

"channel"  =>
"Channel",

"title"  =>
"Title",

"customer"  =>
"Customer",

"date_from"  =>
"Date from",

"date_to"  =>
"To",

"status"  =>
"Status",

"booking_id"  =>
"Booking ID",

"comment"  =>
"Customer's comment",

"booking_date"  =>
"Booking date",

"last_edit"  =>
"Last edit",

"moderator_comment"  =>
"Moderator's comment",

"notify_user"  =>
"Notify customer about changes to this booking?",

"include_comment"  =>
"Include moderator's comment into notification message?",

"missing_booking_id"  =>
"The booking ID was missing",

"places"  =>
"Places",

"limit_field"  =>
"Bookings limit field",

"can_not_edit"  =>
"You can not edit this booking",

"create_booking"  =>
"Create booking",

"object"  =>
"Object",

"name"  =>
"Name",

"phone"  =>
"Phone",

"contact"  =>
"Contact details",

"booking_created"  =>
"New booking has been created successfully",

"notify_user_on_creation"  =>
"Notify user about booking created?",

"time_ranges"  =>
"Time ranges",

"no_ranges_defined"  =>
"There are no time ranges defined for booking",

"add_time_range" =>
"Add time range",

"ranges_field" =>
"Field containing ranges",

"time_to_should_be_greater" =>
"End of time range should be greater then start",

"need_set_both_time" =>
"If you want to use exact-time range, you need to set both start and end time",

"cp_notification_2.5" =>
"It looks like you recently upgraded the module. The new version requires some changes in email templates, as described in the docs. Please go to email templates section and make the changes.",

"delete_booking" =>
"Delete booking",

"confirm_deleting" =>
"Confirm deleting",

"booking_delete_warning" =>
"Are you sure you want to DELETE this booking? This is not recommended to do. If you simply want to cancel or reject previously made booking, edit and change its status instead.",

"booking_deleted" =>
"The booking has been deleted",

"no_booking_to_delete" =>
"There has been no booking to delete",

"booking_ranges" =>
"Booking time ranges",

"ranges_tab_instructions" =>
"Select all time ranges that can be used for booking of this object",

"select_ranges" =>
"Select available ranges",

"price" =>
"Price",

"reeservation" =>
"rEEservation",

"property" =>
"Property",

"value" =>
"Value",

"range_title" =>
"Range title",

"dow_from" =>
"Day of week, from",

"dow_to" =>
"to",

"time_from" =>
"Time, from",

"time_to" =>
"to",

"delete_range" =>
"Delete range",

"range_delete_warning" =>
"Are you sure you want to delete this time range? If you have bookings for it already, it is better to disable it for particular entry.",

"range_deleted" =>
"The time range has been deleted",

"no_range_to_delete" =>
"There has been no time range to delete",

"setfor" =>
"Price is set for...",

"one_day" =>
"One day",

"complete_range" =>
"Comlete range",

"range_not_available" =>
"The selected time range is not available for this object",

"not_defined" =>
"Noe defined",

"range" =>
"Time range",

"range_out_of_scope" =>
"The time range you selected is out of scope of available dates",

/* END */
''=>''
);
?>
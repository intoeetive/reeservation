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

"reeservation_edited_owner_notification"  =>
"Booking edited owner notification",

"reeservation_edited_admin_notification"  =>
"Booking edited admin notification",

"subject_created_admin_notification"  =>
"A new booking has been made",

"message_created_admin_notification"  =>
"A new booking has been made for {title} ({permalink}) in {channel_title}

Details:
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"}
Name: {name}
Email: {email}
Phone: {phone}

{booking_cp_link}

{site_name}
{site_url}
",

"subject_edited_admin_notification"  =>
"Booking has been updated",

"message_edited_admin_notification"  =>
"Booking has been updated for {title} ({permalink}) in {channel_title}

Booking status: {status}

Details:
Dates: {date_from format=\"%Y-%m-%d\"} - {date_to format=\"%Y-%m-%d\"}
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

"subject_edited_owner_notification"  =>
"Booking has been updated",

"message_edited_owner_notification"  =>
"Booking has been updated for {title} ({permalink}) in {channel_title} at {site_name}

Booking status: {status}

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
"The object %X is already booked for the period selected",

"pending_booking_for_these_dates_exist"  =>
"Someone is trying to book same object %X for the same dates. Please try later.",

"invalid_email"  =>
"The email address you provided is not valid",

"form_out_of_date"  =>
"Too much time passed since the form has been generated. Please try again.",

"bookings_user_limit_reached"  =>
"You cannot make more bookings for given dates.",

"bookings_limit_reached"  =>
"The object %X has reached maximum number of bookings for dates selected.",

"bookings_limit_pending_reached"  =>
"The object %X has reached maximum number of bookings for dates selected, but not all of them are confirmed. You may try again later.",

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

"bookings_user_limit"  =>
"Limit of bookings per user",

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

"status_change_admin_notification" =>
"Sent notification about booking status change to admin?",

"status_change_owner_notification" =>
"Sent notification about booking status change to object owner?",

"status_change_customer_notification" =>
"Sent notification about booking status change to customer?",

"allow_changeover_booking" =>
"Allow bookings overlap on changeover days?",

"allowed_start_date" =>
"Earliest allowed booking date",

"allowed_end_date" =>
"Latest allowed booking date",


"dates_out_of_allowed_range" =>
"The requested dates cannot be booked",

/* END */
''=>''
);
?>
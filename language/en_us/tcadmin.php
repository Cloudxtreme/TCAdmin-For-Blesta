<?php
/**
 * TCAdmin - The Game Hosting Control Panel
 * 
 * @author Jamie Sage
 * @link https://www.jamies-servers.co.uk
 *
 * With thanks to the Blesta team and TCAdmin
 * @link http://www.blesta.com/
 * @link http://www.tcadmin.com/
 * @link http://help.tcadmin.com/Billing_API_Examples
 */

// Module
$lang['Tcadmin.name'] = "TCAdmin";

// Manage
$lang['Tcadmin.manage.title'] = "Manage TCAdmin";
$lang['Tcadmin.manage.hostname'] = "TCAdmin Hostname";
$lang['Tcadmin.manage.username'] = "Administrator Username";
$lang['Tcadmin.manage.password'] = "Administrator Password";
$lang['Tcadmin.manage.submit'] = "Update";
$lang['Tcadmin.add_module_row'] = "Add Server";
$lang['Tcadmin.manage.module_rows_title'] = "Servers";
$lang['Tcadmin.manage.module_rows_heading.server_name'] = "Server Label";
$lang['Tcadmin.manage.module_rows_heading.options'] = "Options";
$lang['Tcadmin.manage.module_rows.edit'] = "Edit";
$lang['Tcadmin.manage.module_rows.delete'] = "Delete";
$lang['Tcadmin.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Tcadmin.manage.module_rows_no_results'] = "There are no servers.";
$lang['Tcadmin.manage.module_rows_in_use_0'] = "No";
$lang['Tcadmin.manage.module_rows_in_use_1'] = "Yes";
$lang['Tcadmin.manage.addrow'] = "Add Server";
$lang['Tcadmin.manage.editrow'] = "Edit Server";
$lang['Tcadmin.module_row.name'] = "Server";

// Admin Service Info
$lang['Tcadmin.service_info.ip_address'] = "Primary IP Address";
$lang['Tcadmin.service_info.manage'] = "Manage";

// Client Service Info
$lang['Tcadmin.tab.tcadmin'] = "TCAdmin";

// Tooltips
$lang['Tcadmin.!tooltip.hostname'] = "Full URL address to your TCAdmin installation, including the ending slash. Example: http://127.0.0.1:8880/";
$lang['Tcadmin.!tooltip.username'] = "Your TCAdmin administrator username which you use to login";
$lang['Tcadmin.!tooltip.password'] = "Your TCAdmin administrator password which you use to login";

// Packages
$lang['TcadminPackage.package_fields.game_id'] = "Game Server ID";
$lang['TcadminPackage.package_fields.voice_id'] = "Voice Server ID";
$lang['TcadminPackage.package_fields.branded'] = "Branded";
$lang['TcadminPackage.package_fields.server_type'] = "Server Type";
$lang['TcadminPackage.package_fields.gamevar_Xms'] = "Game Variables - Xms";
$lang['TcadminPackage.package_fields.gamevar_Xmx'] = "Game Variables - Xmx";
$lang['TcadminPackage.package_fields.upload_quota'] = "Teamspeak 3 - Upload Quota";
$lang['TcadminPackage.package_fields.download_quota'] = "Teamspeak 3 - Download Quota";
$lang['TcadminPackage.package_fields.master_game_slots'] = "Game Server Slots (Overwrites game_slots)";
$lang['TcadminPackage.package_fields.master_voice_slots'] = "Voice Server Slots (Overwrites voice_slots)";

// Configurable Options
$lang['Tcadmin.config.hostname'] = "Hostname";
$lang['Tcadmin.config.rcon_pass'] = "RCon Password";
$lang['Tcadmin.config.private_pass'] = "Private Password";

// Errors
$lang['Tcadmin.!error.hostname'] = "You did not submit a valid hostname!";
$lang['Tcadmin.!error.username'] = "You did not submit a valid username!";
$lang['Tcadmin.!error.password'] = "You did not submit a valid password!";
$lang['Tcadmin.!error.module_row.missing'] = "TCAdmin is not configured properly, please check your Blesta / TCAdmin configuration!";
$lang['Tcadmin.!error.config.hostname'] = "You must provide a hostname for your server!";
$lang['Tcadmin.!error.config.rcon_pass'] = "You must provide a RCon password for your server!";
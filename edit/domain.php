<?php
// /edit/domain.php
// 
// Domain Manager - A web-based application written in PHP & MySQL used to manage a collection of domain names.
// Copyright (C) 2010 Greg Chetcuti
// 
// Domain Manager is free software; you can redistribute it and/or modify it under the terms of the GNU General
// Public License as published by the Free Software Foundation; either version 2 of the License, or (at your
// option) any later version.
// 
// Domain Manager is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
// implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
// 
// You should have received a copy of the GNU General Public License along with Domain Manager. If not, please 
// see http://www.gnu.org/licenses/
?>
<?php
include("../_includes/start-session.inc.php");
include("../_includes/config.inc.php");
include("../_includes/database.inc.php");
include("../_includes/software.inc.php");
include("../_includes/auth/auth-check.inc.php");
include("../_includes/timestamps/current-timestamp.inc.php");

$page_title = "Editting A Domain";
$software_section = "domains";

$did = $_GET['did'];

// 'Delete Domain' Confirmation Variables
$del = $_GET['del'];
$really_del = $_GET['really_del'];

// Form Variables
$new_domain = $_POST['new_domain'];
$new_expiry_date = $_POST['new_expiry_date'];
$new_function = $_POST['new_function'];
$new_status = $_POST['new_status'];
$new_status_notes = $_POST['new_status_notes'];
$new_cat_id = $_POST['new_cat_id'];
$new_dns_id = $_POST['new_dns_id'];
$new_ip_id = $_POST['new_ip_id'];
$new_hosting_id = $_POST['new_hosting_id'];
$new_account_id = $_POST['new_account_id'];
$new_privacy = $_POST['new_privacy'];
$new_active = $_POST['new_active'];
$new_notes = $_POST['new_notes'];
$new_did = $_POST['new_did'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	function MyCheckDate( $postedDate ) {
	   if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $postedDate, $datebit)) {
		  return checkdate($datebit[2] , $datebit[3] , $datebit[1]);
	   } else {
		  return false;
	   }
	} 	

	if (MyCheckDate($new_expiry_date) && preg_match("/^[A-Z0-9.-]+\.[A-Z]{2,10}$/i", $new_domain) && $new_cat_id != "" && $new_dns_id != "" && $new_ip_id != "" && $new_hosting_id != "" && $new_account_id != "" && $new_cat_id != "0" && $new_dns_id != "0" && $new_ip_id != "0" && $new_hosting_id != "0" && $new_account_id != "0") {

		$tld = preg_replace("/^((.*?)\.)(.*)$/", "\\3", $new_domain);

		$sql = "SELECT registrar_id, owner_id
				FROM registrar_accounts
				WHERE id = '$new_account_id'";
		$result = mysql_query($sql,$connection);
		
		while ($row = mysql_fetch_object($result)) { $new_registrar_id = $row->registrar_id; $new_owner_id = $row->owner_id; }

		$sql2 = "SELECT id
				 FROM fees
				 WHERE registrar_id = '$new_registrar_id'
				   AND tld = '$tld'";
		$result2 = mysql_query($sql2,$connection);
		
		if (mysql_num_rows($result2) >= 1) { 
		
			while ($row2 = mysql_fetch_object($result2)) {
				$temp_fee_id = $row2->id;
			}
			$temp_fee_fixed = "1"; 

		} else { 

			$temp_fee_id = "0";
			$temp_fee_fixed = "0";

		}

		$sql2 = "UPDATE domains
				 SET owner_id = '$new_owner_id',
					registrar_id = '$new_registrar_id',
					account_id = '$new_account_id',
					domain = '" . mysql_real_escape_string($new_domain) . "',
					tld = '$tld',
					expiry_date = '$new_expiry_date',
					cat_id = '$new_cat_id',
					dns_id = '$new_dns_id',
					ip_id = '$new_ip_id',
					hosting_id = '$new_hosting_id',
					fee_id = '$temp_fee_id',
					function = '" . mysql_real_escape_string($new_function) . "',
					status = '" . mysql_real_escape_string($new_status) . "',
					status_notes = '" . mysql_real_escape_string($new_status_notes) . "',
					notes = '" . mysql_real_escape_string($new_notes) . "',
					privacy = '$new_privacy',
					active = '$new_active',
					fee_fixed = '$temp_fee_fixed',
					update_time = '$current_timestamp'
				WHERE id = '$new_did'";
		$result2 = mysql_query($sql2,$connection) or die(mysql_error());
		
		$did = $new_did;
		
		$_SESSION['session_result_message'] = "Domain <font class=\"highlight\">$new_domain</font> Updated<BR>";

		include("../_includes/system/update-domain-fees.inc.php");
		include("../_includes/system/update-segments.inc.php");

	} else {
	
		if (!preg_match("/^[A-Z0-9.-]+\.[A-Z]{2,10}$/i", $new_domain)) { $_SESSION['session_result_message'] .= "The domain format is incorrect<BR>"; }
		if (!MyCheckDate($new_expiry_date)) { $_SESSION['session_result_message'] .= "The expiry date you entered is invalid<BR>"; }

	}

} else {

	$sql = "SELECT d.domain, d.expiry_date, d.cat_id, d.dns_id, d.ip_id, d.hosting_id, d.function, d.status, d.status_notes, d.notes, d.privacy, d.active, ra.id as account_id
			FROM domains as d, registrar_accounts as ra
			WHERE d.account_id = ra.id
			  AND d.id = '$did'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) { 
	
		$new_domain = $row->domain;
		$new_expiry_date = $row->expiry_date;
		$new_cat_id = $row->cat_id;
		$new_dns_id = $row->dns_id;
		$new_ip_id = $row->ip_id;
		$new_hosting_id = $row->hosting_id;
		$new_function = $row->function;
		$new_status = $row->status;
		$new_status_notes = $row->status_notes;
		$new_notes = $row->notes;
		$new_privacy = $row->privacy;
		$new_active = $row->active;
		$new_account_id = $row->account_id;
	
	}

}

if ($del == "1") {

	$sql = "SELECT domain_id
			FROM ssl_certs
			WHERE domain_id = '$did'";
	$result = mysql_query($sql,$connection);
	
	while ($row = mysql_fetch_object($result)) {
		$existing_ssl_certs = 1;
	}
	
	if ($existing_ssl_certs > 0) {

		$_SESSION['session_result_message'] = "This Domain has SSL Certificates associated with it and cannot be deleted<BR>";

	} else {

		$_SESSION['session_result_message'] = "Are you sure you want to delete this Domain?<BR><BR><a href=\"$PHP_SELF?did=$did&really_del=1\">YES, REALLY DELETE THIS DOMAIN ACCOUNT</a><BR>";

	}

}

if ($really_del == "1") {

	$sql = "DELETE FROM domains 
			WHERE id = '$did'";
	$result = mysql_query($sql,$connection);
	
	$_SESSION['session_result_message'] = "Domain <font class=\"highlight\">$new_domain</font> Deleted<BR>";

	include("../_includes/system/update-domain-fees.inc.php");
	include("../_includes/system/update-segments.inc.php");
	include("../_includes/auth/login-checks/domain-and-ssl-asset-check.inc.php");
	
	header("Location: ../domains.php");
	exit;

}
?>
<?php include("../_includes/doctype.inc.php"); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("../_includes/head-tags.inc.php"); ?>
</head>
<body>
<?php include("../_includes/header.inc.php"); ?>
<form name="edit_domain_form" method="post" action="<?=$PHP_SELF?>">
<strong>Domain</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<input name="new_domain" type="text" size="50" maxlength="255" value="<?php if ($new_domain != "") echo $new_domain; ?>">
<BR><BR>
<strong>Expiry Date (YYYY-MM-DD)</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<input name="new_expiry_date" type="text" size="10" maxlength="10" value="<?php if ($new_expiry_date != "") echo $new_expiry_date; ?>">
<BR><BR>
<strong>Registrar Account</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php 
$sql_account = "SELECT ra.id, ra.username, o.name AS o_name, r.name AS r_name
				FROM registrar_accounts AS ra, owners AS o, registrars AS r
				WHERE ra.owner_id = o.id
				  AND ra.registrar_id = r.id
				ORDER BY r_name asc, o_name asc, ra.username asc";
$result_account = mysql_query($sql_account,$connection) or die(mysql_error());
echo "<select name=\"new_account_id\">";
while ($row_account = mysql_fetch_object($result_account)) {

	if ($row_account->id == $new_account_id) {

		echo "<option value=\"$row_account->id\" selected>$row_account->r_name :: $row_account->o_name :: $row_account->username</option>";
	
	} else {

		echo "<option value=\"$row_account->id\">$row_account->r_name :: $row_account->o_name :: $row_account->username</option>";
	
	}
}
echo "</select>";
?>
<BR><BR>
<strong>DNS Profile</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
$sql_dns = "SELECT id, name
			FROM dns
			WHERE active = '1'
			ORDER BY name asc";
$result_dns = mysql_query($sql_dns,$connection) or die(mysql_error());
echo "<select name=\"new_dns_id\">";
while ($row_dns = mysql_fetch_object($result_dns)) {

	if ($row_dns->id == $new_dns_id) {

		echo "<option value=\"$row_dns->id\" selected>$row_dns->name</option>";
	
	} else {

		echo "<option value=\"$row_dns->id\">$row_dns->name</option>";
	
	}
}
echo "</select>";
?>
<BR><BR>
<strong>IP Address</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
$sql_ip = "SELECT id, name, ip
		   FROM ip_addresses
		   ORDER BY name asc, ip asc";
$result_ip = mysql_query($sql_ip,$connection) or die(mysql_error());
echo "<select name=\"new_ip_id\">";

while ($row_ip = mysql_fetch_object($result_ip)) {

	if ($row_ip->id == $new_ip_id) {

		echo "<option value=\"$row_ip->id\" selected>$row_ip->name ($row_ip->ip)</option>";
	
	} else {

		echo "<option value=\"$row_ip->id\">$row_ip->name ($row_ip->ip)</option>";
	
	}
}
echo "</select>";
?>
<BR><BR>
<strong>Web Hosting Provider</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
$sql_hosting = "SELECT id, name
				FROM hosting
				WHERE active = '1'
				ORDER BY name asc";
$result_hosting = mysql_query($sql_hosting,$connection) or die(mysql_error());
echo "<select name=\"new_hosting_id\">";
while ($row_hosting = mysql_fetch_object($result_hosting)) {

	if ($row_hosting->id == $new_hosting_id) {

		echo "<option value=\"$row_hosting->id\" selected>$row_hosting->name</option>";
	
	} else {

		echo "<option value=\"$row_hosting->id\">$row_hosting->name</option>";
	
	}
}
echo "</select>";
?>
<BR><BR>
<strong>Category</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
$sql_cat = "SELECT id, name
			FROM categories
			WHERE active = '1'
			ORDER BY name asc";
$result_cat = mysql_query($sql_cat,$connection) or die(mysql_error());
echo "<select name=\"new_cat_id\">";
while ($row_cat = mysql_fetch_object($result_cat)) {

	if ($row_cat->id == $new_cat_id) {

		echo "<option value=\"$row_cat->id\" selected>$row_cat->name</option>";
	
	} else {

		echo "<option value=\"$row_cat->id\">$row_cat->name</option>";
	
	}
}
echo "</select>";
?>
<BR><BR>
<strong>Domain Status</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
echo "<select name=\"new_active\">";
echo "<option value=\"1\""; if ($new_active == "1") echo " selected"; echo ">Active</option>";
echo "<option value=\"2\""; if ($new_active == "2") echo " selected"; echo ">In Transfer</option>";
echo "<option value=\"5\""; if ($new_active == "5") echo " selected"; echo ">Pending (Registration)</option>";
echo "<option value=\"3\""; if ($new_active == "3") echo " selected"; echo ">Pending (Renewal)</option>";
echo "<option value=\"4\""; if ($new_active == "4") echo " selected"; echo ">Pending (Other)</option>";
echo "<option value=\"0\""; if ($new_active == "0") echo " selected"; echo ">Expired</option>";
echo "<option value=\"10\""; if ($new_active == "10") echo " selected"; echo ">Sold</option>";
echo "</select>";
?>
<BR><BR>
<strong>Privacy Enabled?</strong><a title="Required Field"><font class="default_highlight"><strong>*</strong></font></a><BR><BR>
<?php
echo "<select name=\"new_privacy\">";
echo "<option value=\"0\""; if ($new_privacy == "0") echo " selected"; echo ">No</option>";
echo "<option value=\"1\""; if ($new_privacy == "1") echo " selected"; echo ">Yes</option>";
echo "</select>";
?>
<BR><BR>
<strong>Function</strong><BR><BR>
<input name="new_function" type="text" size="50" maxlength="255" value="<?php if ($new_function != "") echo $new_function; ?>">
<BR><BR>
<strong>Status</strong><BR><BR>
<input name="new_status" type="text" size="50" maxlength="255" value="<?php if ($new_status != "") echo $new_status; ?>">
<BR><BR>
<strong>Status Notes</strong><BR><BR>
<textarea name="new_status_notes" cols="60" rows="5"><?=$new_status_notes?>
</textarea>
<BR><BR>
<strong>Notes</strong><BR><BR>
<textarea name="new_notes" cols="60" rows="5"><?=$new_notes?></textarea>
<BR><BR><BR>
<input type="hidden" name="new_did" value="<?=$did?>">
<input type="submit" name="button" value="Update This Domain &raquo;">
</form>
<BR><BR><a href="<?=$PHP_SELF?>?did=<?=$did?>&del=1">DELETE THIS DOMAIN</a>
<?php include("../_includes/footer.inc.php"); ?>
</body>
</html>
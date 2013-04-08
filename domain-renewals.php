<?php
// domain-renewals.php
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
session_start();

include("_includes/config.inc.php");
include("_includes/database.inc.php");
include("_includes/software.inc.php");
include("_includes/auth/auth-check.inc.php");
include("_includes/timestamps/current-timestamp-basic.inc.php");

$page_title = "Domain Renewal Export";

// Form Variables
$export = $_GET['export'];
$new_expiry_start = $_REQUEST['new_expiry_start'];
$new_expiry_end = $_REQUEST['new_expiry_end'];

$sql = "SELECT currency
		FROM currencies
		WHERE default_currency = '1'
		LIMIT 1";
$result = mysql_query($sql,$connection);
while ($row = mysql_fetch_object($result)) { $default_currency = $row->currency; }

if ($export == "1") {

	$sql = "SELECT d.id, d.domain, d.tld, d.expiry_date, d.function, d.status, d.status_notes, d.notes, d.privacy, d.active, ra.username, r.name AS registrar_name, o.name AS owner_name, f.renewal_fee AS renewal_fee, cc.conversion, cat.name AS category_name, cat.stakeholder AS category_stakeholder, dns.name AS dns_profile, ip.name, ip.ip, ip.rdns
			FROM domains AS d, registrar_accounts AS ra, registrars AS r, owners AS o, fees AS f, currencies AS cc, categories AS cat, dns, ip_addresses AS ip
			WHERE d.account_id = ra.id
			  AND ra.registrar_id = r.id
			  AND ra.owner_id = o.id
			  AND d.registrar_id = f.registrar_id
			  AND d.tld = f.tld
			  AND f.currency_id = cc.id
			  AND d.cat_id = cat.id
			  AND d.dns_id = dns.id
			  AND d.ip_id = ip.id
			  AND cat.active = '1'
			  AND d.expiry_date between '$new_expiry_start' AND '$new_expiry_end'
			ORDER BY d.expiry_date asc";	

} else {

	$sql = "SELECT d.id, d.domain, d.tld, d.expiry_date, d.function, d.status, d.status_notes, d.notes, d.privacy, d.active, ra.username, r.name AS registrar_name, o.name AS owner_name, f.renewal_fee AS renewal_fee, cc.conversion, cat.name AS category_name, cat.stakeholder AS category_stakeholder, dns.name AS dns_profile, ip.name, ip.ip, ip.rdns
			FROM domains AS d, registrar_accounts AS ra, registrars AS r, owners AS o, fees AS f, currencies AS cc, categories AS cat, dns, ip_addresses AS ip
			WHERE d.account_id = ra.id
			  AND ra.registrar_id = r.id
			  AND ra.owner_id = o.id
			  AND d.registrar_id = f.registrar_id
			  AND d.tld = f.tld
			  AND f.currency_id = cc.id
			  AND d.cat_id = cat.id
			  AND d.dns_id = dns.id
			  AND d.ip_id = ip.id
			  AND cat.active = '1'
			  AND d.active NOT IN ('0', '10')
			  AND d.expiry_date between '$new_expiry_start' AND '$new_expiry_end'
			ORDER BY d.expiry_date asc";	

}

$result = mysql_query($sql,$connection) or die(mysql_error());
$result2 = mysql_query($sql,$connection) or die(mysql_error());

$full_export = "";

if ($export == "1") {

	$full_export .= "\"All prices are listed in " . $default_currency . "\"\n\n";

	$full_export .= "\"DOMAIN STATUS\",\"Expiry Date\",\"Renew?\",\"Renewal Fee\",\"Domain\",\"TLD\",\"WHOIS Status\",\"DNS Profile\",\"IP Address Name\",\"IP Address\",\"IP Address rDNS\",\"Function\",\"Status\",\"Status Notes\",\"Category\",\"Category Stakeholder\",\"Owner\",\"Registrar\",\"Username\",\"Notes\"\n";

	while ($row = mysql_fetch_object($result)) {
		
		$temp_renewal_fee = number_format($row->renewal_fee * $row->conversion, 2, '.', ',');
		$total_renewal_fee_export = $total_renewal_fee_export + $temp_renewal_fee;

		if ($row->active == "0") { $domain_status = "EXPIRED"; } 
		elseif ($row->active == "1") { $domain_status = "ACTIVE"; } 
		elseif ($row->active == "2") { $domain_status = "IN TRANSFER"; } 
		elseif ($row->active == "3") { $domain_status = "PENDING (RENEWAL)"; } 
		elseif ($row->active == "4") { $domain_status = "PENDING (OTHER)"; } 
		elseif ($row->active == "5") { $domain_status = "PENDING (REGISTRATION)"; } 
		elseif ($row->active == "10") { $domain_status = "SOLD"; } 
		else { $domain_status = "ERROR -- PROBLEM WITH CODE IN DOMAIN-RENEWALS.PHP"; } 
		
		if ($row->privacy == "1") {
			$privacy_status = "Private";
		} elseif ($row->privacy == "0") {
			$privacy_status = "Public";
		}

		$full_export .= "\"$domain_status\",\"$row->expiry_date\",\"$row->to_renew\",\"$temp_renewal_fee\",\"$row->domain\",\"$row->tld\",\"$privacy_status\",\"$row->dns_profile\",\"$row->name\",\"$row->ip\",\"$row->rdns\",\"$row->function\",\"$row->status\",\"$row->status_notes\",\"$row->category_name\",\"$row->category_stakeholder\",\"$row->owner_name\",\"$row->registrar_name\",\"$row->username\",\"$row->notes\"\n";
	}
	
	$full_export .= "\n";
	
	$full_export .= "\"\",\"\",\"Total Cost:\",\"" . number_format($total_renewal_fee_export, 2, '.', ',') . "\",\"" . $default_currency . "\"\n";
	
	$export = "0";
	
header('Content-Type: text/plain');
$full_content_disposition = "Content-Disposition: attachment; filename=\"export_domains_$new_expiry_start--$new_expiry_end.csv\"";
header("$full_content_disposition");
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
echo $full_export;
exit;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("_includes/head-tags.inc.php"); ?>
</head>
<body>
<?php include("_includes/header.inc.php"); ?>
<?php 
$result = $result2;
if (mysql_num_rows($result) > 0) { ?>
<strong>Number of Domains to Export:</strong> <?=number_format(mysql_num_rows($result))?><BR><BR>
<?php } ?>
Before exporting your domains you should <a href="system/update-conversion-rates.php">update the conversion rates</a>.
<BR><BR>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="search-table"><BR>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="search-table-inside">
<form name="export_domains_form" method="post" action="<?=$PHP_SELF?>">
Expiring Between 
  <input name="new_expiry_start" type="text" size="10" maxlength="10" <?php if ($new_expiry_start == "") { echo "value=\"$current_timestamp_basic\""; } else { echo "value=\"$new_expiry_start\""; } ?>> 
  and 
  <input name="new_expiry_end" type="text" size="10" maxlength="10" <?php if ($new_expiry_end == "") { echo "value=\"$current_timestamp_basic\""; } else { echo "value=\"$new_expiry_end\""; } ?>> 
  &nbsp;&nbsp;<input type="submit" name="button" value="List Domains &raquo;">
</form>
</td>
<td class="search-table-inside" width="200" valign="middle" align="center">
<?php if (mysql_num_rows($result) > 0) { ?>
<a href="domain-renewals.php?export=1&new_expiry_start=<?=$new_expiry_start?>&new_expiry_end=<?=$new_expiry_end?>">Export Results</a><BR>
<?php } ?>
</td>
</tr>
</table>
</tr>
</table>
<?php if (mysql_num_rows($result) > 0) { ?>
<BR>
<table class="main_table">
<tr class="main_table_row_heading_active">
<?php if ($_SESSION['session_display_domain_expiry_date'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Expiry Date</font>
    </td>
<?php } ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Domain Name</font>
    </td>
<?php if ($_SESSION['session_display_domain_tld'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">TLD</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_ip'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">IP Address</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_dns'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">DNS Profile</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_category'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Category</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_owner'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Owner</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_registrar'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Registrar</font>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_account'] == "1") { ?>
	<td class="main_table_cell_heading_active">
    	<font class="main_table_heading">Registrar Account</font>
    </td>
<?php } ?>
</tr>
<?php while ($row = mysql_fetch_object($result)) { ?>
<?php 
$renewal_fee_individual = $row->renewal_fee * $row->conversion;
$total_renewal_cost = $total_renewal_cost + $renewal_fee_individual; 
?>

<tr class="main_table_row_active">
<?php if ($_SESSION['session_display_domain_expiry_date'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->expiry_date?>
	</td>
<?php } ?>
	<td class="main_table_cell_active">
		<?=$row->domain?>
	</td>
<?php if ($_SESSION['session_display_domain_tld'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->tld?>
	</td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_ip'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->name?> (<?=$row->ip?>)
	</td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_dns'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->dns_profile?>
	</td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_category'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->category_name?>
	</td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_owner'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->owner_name?>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_registrar'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->registrar_name?>
    </td>
<?php } ?>
<?php if ($_SESSION['session_display_domain_account'] == "1") { ?>
	<td class="main_table_cell_active">
		<?=$row->registrar_name?> (<?=substr($row->username, 0, 20);?><?php if (strlen($row->username) >= 21) echo "..."; ?>)
    </td>
<?php } ?>
</tr>
<?php } ?>
</table>
<BR><strong>Total Cost:</strong> <?=number_format($total_renewal_cost,2)?> <?=$default_currency?><BR>
<?php } else {?>
<BR>The results that will be shown below will display the same columns as you have on your <a href="domains.php">Domains</a> page, but when you export the results you will be given even more information.<BR><BR>
The full list of fields in the export is:<BR><BR>
Domain Status<BR>
Expiry Date<BR>
Renewal Fee<BR>
Total Renewal Cost<BR>
Domain<BR>
TLD<BR>
WHOIS Status (Public or Private)<BR>
DNS Profile<BR>
IP Address Name<BR>
IP Address<BR>
IP Address rDNS<BR>
Domain Function<BR>
Domain Status<BR>
Domain Status Notes<BR>
Category<BR>
Category Stakeholder<BR>
Owner<BR>
Domain Registrar<BR>
Registrar Account<BR>
Notes<BR>
<?php } ?>
<?php include("_includes/footer.inc.php"); ?>
</body>
</html>
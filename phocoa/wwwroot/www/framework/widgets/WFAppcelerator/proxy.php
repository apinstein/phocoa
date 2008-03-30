<?php
/**
 * This file is part of Appcelerator.
 *
 * Copyright (C) 2006-2008 by Appcelerator, Inc. All Rights Reserved.
 * For more information, please visit http://www.appcelerator.org 
 *
 * Appcelerator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
$post_data = $HTTP_RAW_POST_DATA;

$url = $_GET['url'];
if (stristr($url,"://")==FALSE)
{
	$url = base64_decode($url);
}

$session = curl_init($url);

$header[] = "User-Agent: " . $_SERVER['HTTP_USER_AGENT'];
$header[] = "Content-Length: ".strlen($post_data);

if ($_SERVER['CONTENT_TYPE'])
{
	$header[] = "Content-Type: " . $_SERVER['CONTENT_TYPE'];
}

curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($session, CURLOPT_TIMEOUT, 10);
curl_setopt($session, CURLOPT_HTTPHEADER, $header);
curl_setopt($session, CURLOPT_HEADER, 1);

if ( strlen($post_data)>0 )
{
    curl_setopt($session, CURLOPT_POSTFIELDS, $post_data);
}

$response = curl_exec($session);     
curl_close($session);

$send = 0;
$tok = strtok($response, "\n");
while ($tok !== false) 
{
	if ($send)
	{
	   print $tok . "\n";
	}
	else 
	{
	   if (trim($tok)=='')
	   {
		   $send=1;
	   }
	   else
	   {
		   if (stristr($tok,'Set-Cookie')==FALSE && stristr($tok,'Transfer-Encoding')==FALSE)
		   {
			  header($tok);
		   }
	   }
	}
    $tok = strtok("\n");
}

?>

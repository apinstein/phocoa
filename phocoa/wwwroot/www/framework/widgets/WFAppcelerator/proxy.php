<?php
/*
 * Copyright 2006-2008 Appcelerator, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License. 
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

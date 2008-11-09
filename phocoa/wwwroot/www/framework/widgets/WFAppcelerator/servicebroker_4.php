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

    //---------------------------------------------------------------------------------
    //
    // Everything below here is the core framework and requires no configuration
    //
    //---------------------------------------------------------------------------------

    class ServiceAdapter
    {
        var $service;
        var $method;
        var $metadata;

        function ServiceAdapter(&$service, &$method, &$metadata)
        {
            $this->service = $service;
            $this->method = $method;
            $this->metadata = $metadata;
        }
        function getVersion ()
        {
            return $this->metadata['version'];
        }
        function getResponseType ()
        {
            return $this->metadata['response'];
        }
        function dispatch (&$request,&$response)
        {
            call_user_func_array(array($this->service,$this->method),array($request,$response));
        }
    }

    require_once("json.php");
    $json = new Services_JSON();

    define('START_DOC', '/**');
    define('END_DOC', '*/');
    function getFileNotations( $strFile )
    {
        $strText = file_get_contents( $strFile );
        $arrText = explode( "\n" , $strText );
   
        $arrNotations = array();
   
        for ( $intCount = 0 ; $intCount < count( $arrText ) ; ++$intCount )
        {
            $strLine = $arrText[ $intCount ];
       
            // inside the phpdoc //
            if ( strpos( trim( $strLine ) , START_DOC ) === 0 )
            {
                ++$intCount;
                $strLine = $arrText[ $intCount ];
                $arrNotation = array();
           
                // while the phpdoc is not finished //
                while ( ( strpos( trim( $strLine ) , END_DOC ) !== 0 ) and ( $intCount < count( $arrText ) ) )
                {
                    // get the name of the tag //
                    $strName = substr( $strLine , 0 , strpos( $strLine , ' ' ) );
                    // get the value of the tag //
                    $strLine = substr( $strLine , strpos( $strLine , ' ' ) + 1 );
               
                    if ( strpos( trim( $strLine ) , '*' ) === 0 )
                    {
                        $strLine = substr( $strLine , strpos( $strLine , '*' ) + 1 );
                    }
               
                    $strLine = trim( $strLine );
                    if (array_key_exists($strName, $arrNotation) === FALSE)
                    {
                        $arrNotation[ $strName ] = '';
                    }
                    else
                    {
                        if ( $strLine != '' )
                        {
                            $arrNotation[ $strName ] .= "\n";
                        }
                    }
                    $arrNotation[ $strName ] .= trim( $strLine );
                    ++$intCount;
                    $strLine = $arrText[ $intCount ];
                }
                if ( $intCount < count( $arrText ) )
                {
                    do
                    {
                        ++$intCount;
                        $strLine = $arrText[ $intCount ];
                    }
                    while ( $strLine == '' );
                    // adding the notation to the next command line //
                    $arrNotations[ trim( $arrText[ $intCount ] ) ] = $arrNotation;
                    $intCount--;
                }
            }
        }
        return( $arrNotations );
    }

    //
    // scan directory and load our services up
    //
    $files = searchdir($dir,-1,"FILES");
    foreach ($files as $i => $value)
    {
        $right = strrpos($value,'.');
        $left = strlen($dir)+1;
        $name = substr($value,$left,$right-$left);
        if (stristr($name,'Service'))
        {
            $tokens = split('_',$name);
            $str = '';
            foreach ($tokens as $t => $token)
            {
                $str = $str . strtoupper(substr($token,0,1)) . substr($token,1);
            }
            if ($str != '')
            {
                $annotations = getFileNotations($value);
                require_once $value;
                $instance = new $str;
                $methods = get_class_methods($instance);
                foreach ($methods as $m => $method)
                {
                    $key = null;
                    $comment = null;
                    foreach ($annotations as $a => $annotation)
                    {
                        if (stristr($a, $method))
                        {
                            foreach ($annotation as $text)
                            {
                                $comment = $comment . $text;
                            }
                            $key = $a;
                            break;
                        }
                    }

                    $metadata = getServiceMetaData($comment);

                    if ($metadata === FALSE) // could not parse metadata
                        continue;

                    $request = $metadata['request'];
                    $adapter = new ServiceAdapter($instance,$method,$metadata);
                    registerService($request,$adapter,$services);
                }
            }
        }
    }


    function getRequests($content_type, $input)
    {
        if (strpos(strtolower($content_type), "application/json") !== FALSE)
        {
            return getRequestsFromJSON($input);
        }
        else
        {
            return getRequestsFromXML($input);
        }
    }
    function getRequestsFromJSON($input)
    {
        global $json;
        $request = get_object_vars($json->decode($input));

        $timestamp = $request['timestamp'];
        
        $requests = array();
        foreach ($request['messages'] as $smessage)
        {
           $smessage = get_object_vars($smessage);
           $message = array();
           $message['type'] = $smessage['type']; 
           $message['version'] = $smessage['version']; 
           $message['scope'] = $smessage['scope']; 
           $message['data'] = get_object_vars($smessage['data']);
           array_push($requests, $message);
        }

        return $requests;
    }

    function getRequestsFromXML($input)
    {
        global $requests;
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, "getRequestsXMLStartElement", "getRequestsXMLEndElement");
        xml_set_character_data_handler($xml_parser, "getRequestsXMLCharacterData");
        xml_parse($xml_parser, $input);

        return $requests; // hide the use of globals (a result of SAX)
    }
    $version = NULL;
    $sessionid = NULL;
    $messageAttrs = NULL;
    $cdata = "";
    function getRequestsXMLStartElement($parser, $name, $attrs) 
    {
        global $version, $sessionid, $messageAttrs, $cdata;
        if ($name == 'MESSAGES')
        {
            if (is_set($attrs['VERSION']))
            {
                $version = $attrs['VERSION'];
            }
            if (is_set($attrs['SESSIONID']))
            {
                $sesionid = $attrs['SESSIONID'];
            }
        }
        else if ($name == 'MESSAGE')
        {
            $messageAttrs = $attrs;
            $cdata = '';
        }
    }
    function getRequestsXMLEndElement ($parser, $name)
    {
        global $messageAttrs, $cdata, $json, $responses, $requests;
        if ($name == 'MESSAGE')
        {
            $request = array();
            $request['type']      = $messageAttrs['TYPE'];
            $request['version']   = array_key_exists('VERSION', $messageAttrs) ? $messageAttrs['VERSION'] : '1.0';
            $request['scope']     = array_key_exists('SCOPE', $messageAttrs) ? $messageAttrs['SCOPE'] : 'appcelerator';
            $request['data'] = get_object_vars($json->decode($cdata));
            array_push($requests, $request);
        }
    }
    function getRequestsXMLCharacterData($parser, $data) 
    {
        global $cdata;
        $cdata = $cdata . $data;
    }
    function getResponseText($content_type, $responses, $sessionid)
    {
        if (strpos(strtolower($content_type), "application/json") !== FALSE)
        {
            return getResponseTextAsJSON($responses, $sessionid);
        }
        else
        {
            return getResponseTextAsXML($responses, $sessionid);
        }
    }
    function getResponseTextAsJSON($responses, $sessionid)
    {
        global $json;
        $out = array(
            'version' => '1.0',
            'timestamp' => gmdate('U999')
        );
            
        if (!is_null($sessionid))
        {
            $out['sessionid'] = $sessionid;
        }

        $out['messages'] = $responses;
        return $json->encode($out);
    }
    function getResponseTextAsXML($responses, $sessionid)
    {
        global $json;
        $out = "";
        $out = $out . '<?xml version="1.0" encoding="UTF-8"?>';
        $out = $out . '<messages version="1.0"';
       
        if (isset($sessionid))
        { 
            $out = $out . ' sessionid="' . $sessionid;
        }
        $out = $out . '>';
    
        foreach ($responses as $r)
        {
            $out = $out
                     . '<message requestid="1"' 
                     . ' direction="OUTGOING" datatype="JSON" type="' . $r['type']
                     . '" scope="' . $r['scope'] . '"><![CDATA['
                     . $json->encode($r['data']) . ']]></message>';
        }
        $out = $out . '</messages>';
        return $out;
    }

    $responses = array();
    $requests = array();

    $requests = getRequests($content_type, $post);
    foreach ($requests as $request)
    {
        // no handlers registered for this request type
        if (array_key_exists($request['type'], $services) === FALSE)
        {
            continue;
        }

        foreach ($services[$request['type']] as $handler)
        {
            // handler doesn't apply to this version
            if ($handler->getVersion() != $request['version'])
            {
                continue;
            }

            if (is_null($handler->getResponseType()))
            {
                $response = array(); // empty response type means
            }                        // this service needs no response
            else
            {
                $response = array();
                $data = array();
                $response['scope'] = $request['scope'];
                $response['version'] = $request['version'];
                $response['type'] = $handler->getResponseType();
                $response['data'] = &$data;

                array_push($responses, &$response);
            }

            // dispatch to the service
            $handler->dispatch($request,$response);
        }
    }

    // do le serialization
    $responseText = getResponseText($content_type, $responses, $sessionid);

    // return our response based on whether we have responses or not
    if (count($responses) > 0)
    {
        header('Content-type: ' . $content_type);
        print $responseText;
    }
    else
    {
        header('Content-Length: 0');
        header('Content-type: text/plain');
        header('HTTP/1.0 202 Accepted');
    }
?>

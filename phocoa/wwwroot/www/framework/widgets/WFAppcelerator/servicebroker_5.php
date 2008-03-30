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

	final class ServiceAdapter
	{
		private $service;
		private $method;
		private $metadata;
		
		public function __construct(&$service,&$method,&$metadata)
		{
			$this->service = $service;
			$this->method = $method;
			$this->metadata = $metadata;
		}
		public function getVersion ()
		{
			return $this->metadata['version'];
		}
		public function getResponseType ()
		{
			return $this->metadata['response'];
		}
		public function dispatch (&$request,&$response)
		{
			$this->method->invoke($this->service,$request,$response);
		}
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
				require_once $value;
				$instance = new $str;
				$methods = get_class_methods($instance);
				foreach ($methods as $m => $method)
				{
					$rm = new ReflectionMethod($str,$method);
					if ($rm->isUserDefined() && !$rm->isConstructor() && !$rm->isDestructor() && $rm->getNumberOfParameters()==2)
					{
						$comment = $rm->getDocComment();
						$metadata = getServiceMetadata($comment);
						$request = $metadata['request'];
						$adapter = new ServiceAdapter($instance,$rm,$metadata);
						registerService($request,$adapter,$services);
					}
				}
			}
		}
	}

	$dom = new DOMDocument();
    $dom->loadXML($post);
    $nodes = $dom->documentElement->childNodes;

    $responseDom = new DOMDocument();
    $responseElement = $responseDom->createElement('messages');
    $responseElement->setAttribute('version','1.0');
    if (!is_null($sessionid))
	{
		$responseElement->setAttribute('sessionid',$sessionid);
	}
    $responseDom->appendChild($responseElement);

	function toXML ($dom, $response)
	{
		$element = $dom->createElement('message');
		$element->setAttribute('type',$response['type']);
		$element->setAttribute('requestid',$response['requestid']);
		$element->setAttribute('datatype','JSON');
		$element->setAttribute('direction','OUTGOING');
		$element->setAttribute('timestamp',time());
		$cdata = $dom->createCDATASection(json_encode($response['data']));
		$element->appendChild($cdata);
		return $element;
	}
	
	$count = 0;

	//
	// process each incoming service request
	//
    foreach ($nodes as $node)
    {
         if ($node->nodeType == XML_ELEMENT_NODE)
         {
            // pull out message type and parse JSON body
			$cdata = '';
			foreach ($node->childNodes as $child)
			{
				$cdata = $cdata . $child->nodeValue;
			}
			$type = $node->getAttribute('type');
			if (array_key_exists($type,$services))
			{
				$request = array();
				$request['scope'] = $node->getAttribute('scope');
				$request['version'] = $node->getAttribute('version');
				$request['data'] = json_decode($cdata,true);
				//echo("decode:" . var_dump($request['data']));
				$request['requestid'] = $node->getAttribute('requestid');
				$array = $services[$type];
				foreach ($array as $handler)
				{
					if ($handler->getVersion() == $request['version'])
					{
						//
						// dispatch to the service and get response message (optional)
						//
						$responseType = $handler->getResponseType();
						if (is_null($responseType))
						{
							$handler->dispatch($request,null);
						}
						else
						{
							$data = array();
							$response = array();
							$response['scope']=$request['scope'];
							$response['version']=$request['version'];
							$response['type']=$responseType;
							$response['requestid']=$request['requestid'];
							$response['data']=&$data;
							$handler->dispatch($request,$response);
							$element = toXML($responseDom, $response);
							$responseElement->appendChild($element);
							$count++;
						}
					}
				}
			}
         }
    }
	
	//
	// return our response based on whether we have responses or not
	//
	if ($count > 0)
	{
		header('Content-type: text/xml;charset=UTF-8');
		print $responseDom->saveXML();
	}
	else
	{
		header('Content-Length: 0');
		header('Content-type: text/plain');
		header('HTTP/1.0 202 Accepted');
	}
?>
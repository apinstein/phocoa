<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @subpackage Widgets
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * This widget is an Appcelerator (http://appcelerator.org) container.
 * 
 * Messages send from Appcelerator in dotted notation are converted to php function names as follows:
 *
 * - request => request()
 * - request.send => requestSend()
 * - request.send.message => requestSendMessage()
 *
 * NOTE: any arguments to the message are passed into the message handler as a single argument in JSON format.
 *
 * <b>PHOCOA Builder Setup:</b>
 *
 * <b>Required:</b><br>
 * 
 * <b>Optional:</b><br>
 * - {@link WFAppcelerator::$debug debug} boolean True to use the debug version of Appcelerator.
 *
 * @internal
 * To update to the latest appcelerator code, do an app create:project and then rsync the public directory:
 * rsync  -vvrC --del ~/apptest/public/ ~/dev/sandbox/phocoa/phocoa/wwwroot/www/framework/widgets/WFAppcelerator
 * Then update subversion
 * svn st | grep '^!' | awk '{ print $2; }' | xargs svn rm
 * svn st | grep '^?' | awk '{ print $2; }' | xargs svn add
 */
class WFAppcelerator extends WFWidget
{
    protected $appceleratorDir;
    protected $debug;

    /**
      * Constructor.
      */
    function __construct($id, $page)
    {
        parent::__construct($id, $page);
        $this->appceleratorDir = $this->getWidgetWWWDir();
        $this->debug = true;
    }

    public static function exposedProperties()
    {
        $items = parent::exposedProperties();
        return array_merge($items, array(
            ));
    }
    function setupExposedBindings()
    {
        $myBindings = parent::setupExposedBindings();
        return $myBindings;
    }

    function render($blockContent = NULL)
    {
        if ($this->hidden)
        {
            return NULL;
        }
        else
        {
            return '
                <script src="' . $this->appceleratorDir . '/javascripts/appcelerator-' . ($this->debug ? 'debug' : 'lite') . '.js"></script>' . $this->jsStartHTML() . '
                        Appcelerator.Browser.autoReportStats = false;
                        Appcelerator.Util.ServerConfig.disableRemoteConfig = true;
                        Appcelerator.Util.ServiceBroker.marshaller = "application/x-www-form-urlencoded";

                        Appcelerator.Core.onload( function() {
                            Appcelerator.Util.ServerConfig.set({
                                "servicebroker": {
                                       "value": "' . WWW_ROOT . '/' . $this->page()->module()->invocation()->invocationPath() . '"
                                },
                                "sessionid": {
                                       "value": "' . ini_get('session.name') . '"
                                }});
                        });

                        var parentParameterize = Appcelerator.Util.ServiceBrokerMarshaller["application/x-www-form-urlencoded"].parameterize;
                        Appcelerator.Util.ServiceBrokerMarshaller["application/x-www-form-urlencoded"].parameterize = function(msg) {
                            var action = null;
                            msg[0]["type"].split(".").each(function(messagePart) {
                                if (action == null)
                                {
                                    action = messagePart;
                                }
                                else
                                {
                                    action += messagePart.capitalize();
                                }
                            });

                            var str = parentParameterize.call(Appcelerator.Util.ServiceBrokerMarshaller["application/x-www-form-urlencoded"], msg);
                            str += "&'
                                        // phocoa-ajax bridge
                                        . WFRPC::PARAM_ENABLE . '=1&'
                                        . WFRPC::PARAM_INVOCATION_PATH . '=' . WWW_ROOT . '/' . $this->page()->module()->invocation()->invocationPath() . '&'
                                        . WFRPC::PARAM_TARGET . '=#page#&'
                                        . WFRPC::PARAM_ACTION . '=" + action + "&'
                                        . WFRPC::PARAM_RUNS_IF_VALID . '=true&'
                                        . WFRPC::PARAM_ARGC . '=1&'
                                        . WFRPC::PARAM_ARGV_PREFIX . '0=" + $H(msg[0].data).toJSON() + "&'
                                        // form compatibility
                                        . ($this->getForm()
                                                ? '__formName=' . $this->getForm()->id() . '&' .
                                                  '__modulePath=' . $this->page()->module()->invocation()->modulePath() . '/' . $this->page()->pageName()
                                                : NULL)
                                        . '";
                            return str;
                        };
                   ' . $this->jsEndHTML();
        }
    }

    function canPushValueBinding() { return false; }
}

/**
 * A custom WFActionResponse class for sending messages back to the Appcelerator client.
 *
 * NOTE: payloads should be associative arrays.
 *
 * Fluent interface:
 * WFActionResponseAppcelerator::WFActionResponseAppcelerator($message, $payload)->addMessage($message2, $payload2)
 */
class WFActionResponseAppcelerator extends WFActionResponse
{
    /**
     * Fluent constructor.
     *
     * @return object WFActionResponseAppcelerator
     */
    public static function WFActionResponseAppcelerator($message = NULL, $payload = NULL)
    {
        $response = new WFActionResponseAppcelerator;
        $response->addMessage($message, $payload);
        return $response;
    }

    public function __construct()
    {
        parent::__construct(array());
    }

    public function contentType()
    {
        return 'application/json';
    }

    /**
     * Add another response message to the Appcelerator response queue.
     *
     * @param string The message to send on the client.
     * @param mixed The payload to send along with the message.
     */
    public function addMessage($message = NULL, $payload = NULL)
    {
        if ($message === NULL)
        {
            $this->data[] = array();
        }
        else
        {
            $this->data[] = array(
                    'type' => $message,
                    'data' => $payload,
                    'scope' => $_REQUEST['$scope'],
                    'requestid' => $_REQUEST['$requestid'],
                    );
        }
        return $this;
    }

    public function data()
    {
        return "/*-secure-\n" . WFJSON::json_encode($this->data) . "\n*/";
    }
}
?>

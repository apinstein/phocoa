<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

class module_autocomplete extends WFModule
{
    public $rainbow = array('red', 'orange', 'yellow', 'green', 'blue', 'indigo', 'violet');
    public $states = array(
        array('Alabama', 'AL'),  
        array('Alaska', 'AK'),  
        array('Arizona', 'AZ'),  
        array('Arkansas', 'AR'),  
        array('California', 'CA'),  
        array('Colorado', 'CO'),  
        array('Connecticut', 'CT'),  
        array('Delaware', 'DE'),  
        array('District Of Columbia', 'DC'),  
        array('Florida', 'FL'),  
        array('Georgia', 'GA'),  
        array('Hawaii', 'HI'),  
        array('Idaho', 'ID'),  
        array('Illinois', 'IL'),  
        array('Indiana', 'IN'),  
        array('Iowa', 'IA'),  
        array('Kansas', 'KS'),  
        array('Kentucky', 'KY'),  
        array('Louisiana', 'LA'),  
        array('Maine', 'ME'),  
        array('Maryland', 'MD'),  
        array('Massachusetts', 'MA'),  
        array('Michigan', 'MI'),  
        array('Minnesota', 'MN'),  
        array('Mississippi', 'MS'),  
        array('Missouri', 'MO'),  
        array('Montana', 'MT'),
        array('Nebraska', 'NE'),
        array('Nevada', 'NV'),
        array('New Hampshire', 'NH'),
        array('New Jersey', 'NJ'),
        array('New Mexico', 'NM'),
        array('New York', 'NY'),
        array('North Carolina', 'NC'),
        array('North Dakota', 'ND'),
        array('Ohio', 'OH'),  
        array('Oklahoma', 'OK'),  
        array('Oregon', 'OR'),  
        array('Pennsylvania', 'PA'),  
        array('Rhode Island', 'RI'),  
        array('South Carolina', 'SC'),  
        array('South Dakota', 'SD'),
        array('Tennessee', 'TN'),  
        array('Texas', 'TX'),  
        array('Utah', 'UT'),  
        array('Vermont', 'VT'),  
        array('Virginia', 'VA'),  
        array('Washington', 'WA'),  
        array('West Virginia', 'WV'),  
        array('Wisconsin', 'WI'),  
        array('Wyoming', 'WY'),
        );

    function defaultPage()
    {
        return 'example';
    }

    function states()
    {
        return $this->states;
    }

}

class module_autocomplete_example
{
    function setupSkin($page, $params, $skin)
    {
        $skin->addMetaKeywords(array('YUI Autocomplete', 'ajax combo box', 'ajax autocomplete', 'autocomplete ajax', 'autocomplete javascript', 'javascript autocomplete', 'php autocomplete', 'autocomplete php', 'php ajax', 'yui', 'combo box', 'ajax'));
        $skin->setMetaDescription('Example ajax autocomplete via YUI AutoComplete widget.');
        $skin->setTitle('YUI AutoComplete Example in PHP');
    }
    
    function stateSearcher($page, $params, $query)
    {
        $res = array();

        $len = strlen($query);
        foreach ($page->module()->states() as $stateInfo) {
            if (strncasecmp($stateInfo[0], $query, $len) == 0)
            {
                $res[] = array($stateInfo[0], $stateInfo[1]);
            }
        }

        return $res;
    }
}
?>

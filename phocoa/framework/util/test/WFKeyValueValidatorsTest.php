<?php

/*
 * @package test
 */
 
require_once getenv('PHOCOA_PROJECT_CONF');

class WFKeyValueValidatorsTest extends PHPUnit_Framework_TestCase
{
    function testPostalCodeValidatorFormats()
    {
        $data = array(
            'US' => array(
                'bad' => array(
                                '123456',
                                '1234',
                                '12345-12345',
                                '12345-123',
                                '123456-1234',
                                'X1Y 4A4',
                               ),
                'good' => array('55555', '55555-4444')
            )
        );
        foreach ($data as $country => $countryData) {
            foreach ($countryData['bad'] as $val) {
                $ok = WFKeyValueValidators::validatePostalCode($val, $edited, $errors, array('country' => $country));
                $this->assertFalse($ok, "Value '{$val}' should not have validated.");
            }
            foreach ($countryData['good'] as $val) {
                $ok = WFKeyValueValidators::validatePostalCode($val, $edited, $errors, array('country' => $country));
                $this->assertTrue($ok, "Value '{$val}' should have validated.");
            }
        }
    }
}

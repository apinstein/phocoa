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

    /**
     * @dataProvider emailValidationDataProvider
     */
    function testEmailValidation($input, $expectedOutput, $expectedValid, $options = array())
    {
        if ($expectedOutput === 'same') $expectedOutput = $input;

        $edited = false;
        $errors = new WFErrorArray;
        $ok = WFKeyValueValidators::validateEmail($input, $edited, $errors, $options);
        $this->assertEquals( ($expectedValid === true ? true : false), $ok);
        $this->assertEquals($expectedOutput, $input);
    }
    function emailValidationDataProvider()
    {
        return array(
            array('a@localhost', 'same', true, array('requireRealDomains' => false)),   // no '.' in domain is actually legit if requireRealDomains = false
            array('a@b.com', 'same', true),
            array(' a@b.com ', 'a@b.com', true),
            array('b.com', 'same', false),
            array('a@', 'same', false),
            array('a@localhost', 'same', false),                                        // no '.' in domain is not cool by default
            array('a@localhost', 'same', true, array('requireRealDomains' => false)),   // no '.' in domain is actually legit if requireRealDomains = false
            array('a@l', 'same', false, array('requireRealDomains' => false)),          // tld must be 2+ chars even with requireRealDomains = false
        );
    }

    /**
     * @dataProvider urlValidationDataProvider
     */
    function testUrlValidation($input, $expectedOutput, $expectedValid, $options = array())
    {
        $edited = false;
        $errors = new WFErrorArray;
        $ok     = (bool)WFKeyValueValidators::validateUrl($input, $edited, $errors, $options);
        $this->assertEquals( ($expectedValid === true ? true : false), $ok);
        $this->assertEquals($expectedOutput, $input);
    }
    function urlValidationDataProvider()
    {
        return array(
            //    Input                     Output                    Valid?  Options
            array('http://www.foobar.com',  'http://www.foobar.com',  true),
            array('https://www.foobar.com', 'https://www.foobar.com', true),
            array('www.foobar.com',         'http://www.foobar.com',  true),
            array(NULL,                     NULL,                     false), // Field is required
            array(NULL,                     NULL,                     true,   array('required' => false)),
        );
    }
}

<?php

require_once(getenv('PHOCOA_PROJECT_CONF'));

if ($argc != 2) die("You must enter the name of the class to test as an argument.\n\nphp createTestStub.php ClassUnderTest\n");
$className = $argv[1];
$classNameLower = strtolower($className[0]) . substr($className, 1);

$testFile = "./{$className}Test.php";
if (file_exists($testFile)) throw new WFException("Test file {$testFile} already exists.");

$testTemplate = <<<TPLEND
<?php

require_once getenv('PHOCOA_PROJECT_CONF');

// http://www.phpunit.de/pocket_guide/3.0/en/writing-tests-for-phpunit.html
class {$className}Test extends PHPUnit_Framework_TestCase
{
    protected \${$classNameLower};

    function setup()
    {
        \$fixture = <<<END
{$className}:
    valid:
        key: value
        key2: value
END;
        \$results = WFFixture::WFFixture()->loadFromYaml(\$fixture);
        \$this->{$classNameLower} = \$results['valid'];
    }

    function tearDown()
    {
        \$this->{$classNameLower}->delete();
    }

}
TPLEND;

file_put_contents($testFile, $testTemplate);

<?php

$spec = Pearfarm_PackageSpec::create(array(Pearfarm_PackageSpec::OPT_BASEDIR => dirname(__FILE__)))
             ->setName('phocoa')
             ->setChannel('apinstein.pearfarm.org')
             ->setSummary('php framework modeled after Cocoa')
             ->setDescription('php framework')
             ->setReleaseVersion('0.3.4')
             ->setReleaseStability('stable')
             ->setApiVersion('1.0.0')
             ->setApiStability('stable')
             ->setLicense(Pearfarm_PackageSpec::LICENSE_MIT)
             ->setNotes('See http://phocoa.com')
             ->addMaintainer('lead', 'Alan Pinstein', 'apinstein', 'apinstein@mac.com')
             ->addGitFiles()
             ->addExecutable('phing/phocoa')
             ;

<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use Cranberry\Shell\Input;
use Cranberry\Shell\Output;
use PHPUnit\Framework\TestCase;

class ApplicationFactoryTest extends TestCase
{
	/**
	 * @expectedException	RuntimeException
	 */
	public function test_create_withUnsupportedPHPVersionThrowsException()
	{
		$application = ApplicationFactory::create( 'app', '1.23', '999.99.9', [], [] );
	}

	public function test_create()
	{
		$appName = 'app-' . microtime( true );
		$appVersion = (string) microtime( true );

		$application = ApplicationFactory::create( $appName, $appVersion, '1.0', [$appName], [] );

		$this->assertEquals( $appName, $application->getName() );
		$this->assertEquals( $appVersion, $application->getVersion() );
	}
}

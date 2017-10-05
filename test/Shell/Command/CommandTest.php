<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Command;

use Cranberry\Shell\Middleware;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
	public function testGetDescription()
	{
		$commandDescription = 'Command description ' . microtime( true );
		$command = new \Cranberry\ShellTest\TestableCommand();

		$command->setDescription( $commandDescription );
		$this->assertEquals( $commandDescription, $command->getDescription() );
	}

	public function testGetMiddleware()
	{
		$commandMiddleware[] = new Middleware\Middleware( function(){} );
		$command = new \Cranberry\ShellTest\TestableCommand();

		$command->setMiddleware( $commandMiddleware );
		$this->assertEquals( $commandMiddleware, $command->getMiddleware() );
	}

	public function testGetName()
	{
		$commandName = 'command-' . microtime( true );
		$command = new \Cranberry\ShellTest\TestableCommand();

		$command->setName( $commandName );
		$this->assertEquals( $commandName, $command->getName() );
	}

	public function testGetUsage()
	{
		$commandUsage = 'command <foo> [--bar]';

		$command = new \Cranberry\ShellTest\TestableCommand();

		$command->setUsage( $commandUsage );
		$this->assertEquals( $commandUsage, $command->getUsage() );
	}

	public function testHasSubcommand()
	{
		$command = new \Cranberry\ShellTest\TestableCommand();

		$this->assertFalse( $command->hasSubcommand() );

		$command->setHasSubcommand( true );

		$this->assertTrue( $command->hasSubcommand() );
	}
}

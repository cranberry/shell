<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell;

use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
	public function optionProvider()
	{
		return [
			['--foo', 'foo', true],
			['--foo=bar', 'foo', 'bar'],
			['--foo=bar,baz', 'foo', 'bar,baz'],
			['-a', 'a', true],
			['-abc', 'a', true],
			['-abc', 'b', true],
			['-abc', 'c', true],

			['-v', 'v', true],
			['-vv', 'v', 2],
			['-vvv', 'v', 3],
			['-vvvv', 'v', 4],
		];
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testConstructorRegistersApplicationOptions( $argument, $optionName, $expectedValue )
	{
		$arguments = ['salso', $argument];
		$input = new Input( $arguments );

		$this->assertSame( $expectedValue, $input->getApplicationOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testConstructorRegistersCommandOptions( $argument, $optionName, $expectedValue )
	{
		$arguments = ['salso', 'command', $argument];
		$input = new Input( $arguments );

		$this->assertSame( $expectedValue, $input->getCommandOption( $optionName ) );
	}

	public function testConstructorRegistersCommandOptionsWithNonDeterminantOrder()
	{
		$arguments = ['salso', 'command', '--foo', '/path/', '-abc', '--bar'];
		$input = new Input( $arguments );

		$this->assertSame( true, $input->getCommandOption( 'foo' ) );
		$this->assertSame( true, $input->getCommandOption( 'a' ) );
		$this->assertSame( true, $input->getCommandOption( 'b' ) );
		$this->assertSame( true, $input->getCommandOption( 'c' ) );
		$this->assertSame( true, $input->getCommandOption( 'bar' ) );
	}

	public function testConstructorRegistersCommandArguments()
	{
		$commandArgument = '/path/';
		$arguments = ['salso', 'command', '--foo', $commandArgument];
		$input = new Input( $arguments );

		$commandArguments = $input->getCommandArguments();

		$this->assertTrue( is_array( $commandArguments ) );
		$this->assertTrue( in_array( $commandArgument, $commandArguments ) );
	}

	public function testEmptyCommandArgumentNotRegistered()
	{
		$commandArgument = '';
		$input = new Input( ['salso'] );

		$input->registerCommandArgument( $commandArgument );
		$commandArguments = $input->getCommandArguments();

		$this->assertTrue( is_array( $commandArguments ) );
		$this->assertEquals( 0, count( $commandArguments ) );
	}

	/**
	 * @expectedException	LengthException
	 */
	public function testEmptyArgumentsArrayThrowsLengthException()
	{
		$input = new Input( [] );
	}

	public function testGetCommandWithApplicationOptions()
	{
		$commandName = 'command-' . time();
		$arguments = ['salso', '--foo=bar', $commandName];
		$input = new Input( $arguments );

		$this->assertEquals( $commandName, $input->getCommandName() );
	}

	public function testGetCommandWithCommandOptions()
	{
		$commandName = 'command-' . time();
		$arguments = ['salso', $commandName, '--foo=bar'];
		$input = new Input( $arguments );

		$this->assertEquals( $commandName, $input->getCommandName() );
	}

	public function testGetUnknownApplicationOptionReturnsNull()
	{
		$input = new Input( ['salso'] );

		$this->assertSame( null, $input->getApplicationOption( 'foo' ) );
	}

	/**
	 * Parsing an option string which does not match `-a`, `-abc`, `--foo`, or
	 * `--foo=bar should throw an exception
	 *
	 * @expectedException	InvalidArgumentException
	 */
	public function testParseInvalidOptionStringThrowsException()
	{
		$result = Input::parseOptionString( 'foo' );
		$this->assertFalse( $result );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testParseOptionString( $argument, $optionName, $expectedValue )
	{
		$result = Input::parseOptionString( $argument );

		$this->assertTrue( is_array( $result ) );
		$this->assertTrue( isset( $result[$optionName] ) );

		$this->assertSame( $expectedValue, $result[$optionName] );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testRegisterApplicationOption( $argument, $optionName, $expectedValue )
	{
		$input = new Input( ['salso'] );

		$result = Input::parseOptionString( $argument );
		foreach( $result as $optionName => $optionValue )
		{
			$input->registerApplicationOption( $optionName, $optionValue );
		}

		$this->assertSame( $expectedValue, $input->getApplicationOption( $optionName ) );
	}

	public function testRegisterCommandArgument()
	{
		$commandArgument = '/path/';
		$input = new Input( ['salso'] );

		$input->registerCommandArgument( $commandArgument );
		$commandArguments = $input->getCommandArguments();

		$this->assertTrue( is_array( $commandArguments ) );
		$this->assertTrue( in_array( $commandArgument, $commandArguments ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testRegisterCommandOption( $argument, $optionName, $expectedValue )
	{
		$input = new Input( ['salso'] );

		$result = Input::parseOptionString( $argument );
		foreach( $result as $optionName => $optionValue )
		{
			$input->registerCommandOption( $optionName, $optionValue );
		}

		$this->assertSame( $expectedValue, $input->getCommandOption( $optionName ) );
	}
}

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
		$input = new Input( $arguments, [] );

		$this->assertSame( $expectedValue, $input->getApplicationOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testConstructorRegistersCommandOptions( $argument, $optionName, $expectedValue )
	{
		$arguments = ['salso', 'command', $argument];
		$input = new Input( $arguments, [] );

		$this->assertSame( $expectedValue, $input->getCommandOption( $optionName ) );
	}

	public function testConstructorRegistersCommandOptionsWithNonDeterminantOrder()
	{
		$arguments = ['salso', 'command', '--foo', '/path/', '-abc', '--bar'];
		$input = new Input( $arguments, [] );

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
		$input = new Input( $arguments, [] );

		$commandArguments = $input->getCommandArguments();

		$this->assertTrue( is_array( $commandArguments ) );
		$this->assertTrue( in_array( $commandArgument, $commandArguments ) );
	}

	public function testEmptyCommandArgumentNotRegistered()
	{
		$commandArgument = '';
		$input = new Input( ['salso'], [] );

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
		$input = new Input( [], [] );
	}

	public function testGetCommandArgumentByIndex()
	{
		$who = 'Dolly';
		$input = new Input( ['cranberry', 'hello', $who], [] );

		$this->assertSame( $who, $input->getCommandArgumentByIndex(0) );
	}

	public function testGetCommandArgumentWithIntParameterReturnsByIndex()
	{
		$input = new Input( ['cranberry', 'hello', 'Dolly'], [] );

		$this->assertSame( $input->getCommandArgumentByIndex( 0 ), $input->getCommandArgument( 0 ) );
	}

	public function testGetCommandArgumentWithStringParameterReturnsByName()
	{
		$input = new Input( ['cranberry', 'hello', 'Dolly'], [] );

		$input->nameCommandArgument( 0, 'who' );

		$this->assertSame( $input->getCommandArgumentByName( 'who' ), $input->getCommandArgument( 'who' ) );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function testGetCommandArgumentWithUnsupportedParameterTypeThrowsException()
	{
		$input = new Input( ['cranberry', 'hello', 'Dolly'], [] );

		$input->getCommandArgument( false );
	}

	public function testGetCommandOptionsReturnsArray()
	{
		$appName = time();
		$input = new Input( [$appName], [] );

		$commandOptions = $input->getCommandOptions();
		$this->assertSame( [], $commandOptions );
	}

	public function testGetCommandWithApplicationOptions()
	{
		$commandName = 'command-' . time();
		$arguments = ['salso', '--foo=bar', $commandName];
		$input = new Input( $arguments, [] );

		$this->assertEquals( $commandName, $input->getCommandName() );
	}

	public function testGetCommandWithCommandOptions()
	{
		$commandName = 'command-' . time();
		$arguments = ['salso', $commandName, '--foo=bar'];
		$input = new Input( $arguments, [] );

		$this->assertEquals( $commandName, $input->getCommandName() );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetInvalidCommandArgumentIndexThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );
		$input->getCommandArgumentByIndex(0);
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetInvalidCommandArgumentByNameThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );

		$commandArgumentName = 'who';
		$input->nameCommandArgument( 0, $commandArgumentName );
		$input->getCommandArgumentByName( $commandArgumentName );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetUnnamedCommandArgumentByNameThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );

		$commandArgumentName = 'who';
		$input->getCommandArgumentByName( $commandArgumentName );
	}

	public function testNameCommandArgument()
	{
		$who = 'Dolly';
		$input = new Input( ['cranberry', 'hello', $who], [] );

		$commandArgumentName = 'who';
		$input->nameCommandArgument( 0, $commandArgumentName );

		$this->assertSame( $who, $input->getCommandArgumentByIndex( 0 ) );
		$this->assertSame( $who, $input->getCommandArgumentByName( $commandArgumentName ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetUnknownApplicationOptionThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );

		$optionName = 'option-' . time();
		$input->getApplicationOption( $optionName );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetUnknownCommandOptionThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );

		$optionName = 'option-' . time();
		$input->getCommandOption( $optionName );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function testGetUnknownOptionThrowsException()
	{
		$input = new Input( ['cranberry', 'hello'], [] );

		$optionName = 'option-' . time();
		$input->getOption( $optionName );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testGetOptionWithMatchingApplicationOption( $argument, $optionName, $expectedValue )
	{
		$input = new Input( ['cranberry', $argument, 'hello'], [] );

		$this->assertSame( $expectedValue, $input->getOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testGetOptionWithMatchingCommandOption( $argument, $optionName, $expectedValue )
	{
		$input = new Input( ['cranberry', 'hello', $argument], [] );

		$this->assertSame( $expectedValue, $input->getOption( $optionName ) );
	}

	public function testGetOptionWithMatchingApplicationAndCommandOptionsReturnsCommandOptionValue()
	{
		$applicationOptionValue = 'bar';
		$commandOptionValue = 'baz';

		$input = new Input( ['cranberry', "--foo={$applicationOptionValue}", 'hello', "--foo={$commandOptionValue}"], [] );

		$this->assertSame( $commandOptionValue, $input->getOption( 'foo' ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasOptionWithNoMatchesReturnsFalse( $argument, $optionName )
	{
		$input = new Input( ['cranberry', 'hello'], [] );
		$this->assertFalse( $input->hasOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasOptionWithMatchingApplicationOptionReturnsTrue( $argument, $optionName )
	{
		$input = new Input( ['cranberry', $argument, 'hello'], [] );
		$this->assertTrue( $input->hasOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasOptionWithMatchingCommandOptionReturnsTrue( $argument, $optionName )
	{
		$input = new Input( ['cranberry', 'hello', $argument], [] );
		$this->assertTrue( $input->hasOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasMatchingApplicationOptionReturnsTrue( $argument, $optionName )
	{
		$input = new Input( ['cranberry', $argument, 'hello'], [] );
		$this->assertTrue( $input->hasApplicationOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasMatchingCommandOptionReturnsTrue( $argument, $optionName )
	{
		$input = new Input( ['cranberry', 'hello', $argument], [] );
		$this->assertTrue( $input->hasCommandOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasUnknownApplicationOptionReturnsFalse( $argument, $optionName )
	{
		$input = new Input( ['cranberry', 'hello'], [] );
		$this->assertFalse( $input->hasApplicationOption( $optionName ) );
	}

	/**
	 * @dataProvider	optionProvider
	 */
	public function testHasUnknownCommandOptionReturnsFalse( $argument, $optionName )
	{
		$input = new Input( ['cranberry', 'hello'], [] );
		$this->assertFalse( $input->hasCommandOption( $optionName ) );
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
		$input = new Input( ['salso'], [] );

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
		$input = new Input( ['salso'], [] );

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
		$input = new Input( ['salso'], [] );

		$result = Input::parseOptionString( $argument );
		foreach( $result as $optionName => $optionValue )
		{
			$input->registerCommandOption( $optionName, $optionValue );
		}

		$this->assertSame( $expectedValue, $input->getCommandOption( $optionName ) );
	}
}

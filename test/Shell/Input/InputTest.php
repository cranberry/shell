<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Input;

use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
	/**
	 * Returns a (mostly) unique string
	 */
	static public function __getUniqueString( string $prefix ) : string
	{
		usleep( 100 );
		return sprintf( '%s-%s', $prefix, microtime( true ) );
	}

	/**
	 * Providers
	 */
	public function provider_argumentsArray() : array
	{
		$appName = self::__getUniqueString( 'app' );

		return [
			[ [$appName] ],
			[ [$appName], '~/Desktop/' ],
			[ [$appName], '--foo=bar', '~/Desktop/' ],
		];
	}

	public function provider_hasArgument_returnsBool() : array
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );
		$argumentName = self::__getUniqueString( 'arg1' );

		return [
			[ [$appName], false, false, false],														// ex., "htop"
			[ [$appName, '~/Desktop'], true, false, false],											// ex., "ls ~/Desktop"

			[ [$appName, $commandName], false, true, false ],										// ex., "git status"
			[ [$appName, $commandName, $argumentName], true, true, false ],							// ex., "git checkout develop"
			[ [$appName, $commandName, $subcommandName], false, true, true ],						// ex., "git stash save"
			[ [$appName, $commandName, $subcommandName, '-m', $argumentName], true, true, true ],	// ex., "git stash save -m <message>"
		];
	}

	public function provider_hasCommand() : array
	{
		$appName = self::__getUniqueString( 'app' );

		return [
			[ [$appName], false ],
			[ [$appName, '--foo'], false ],
			[ [$appName, '--foo', '--bar=baz'], false ],

			[ [$appName, 'command'], true ],
		];
	}

	public function provider_hasCommandOption_withoutMatch() : array
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );

		return [
			[ [$appName] ],
			[ [$appName, $commandName] ],
			[ [$appName, '--foo=bar', $commandName] ],
		];
	}

	public function provider_hasSubcommand() : array
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		return [
			[ [$appName], false ],
			[ [$appName, '--foo'], false ],
			[ [$appName, '--foo', '--bar=baz'], false ],

			[ [$appName], false ],
			[ [$appName, '--foo'], false ],
			[ [$appName, '--foo', $commandName], false ],
			[ [$appName, '--foo', $commandName, '--bar=baz'], false ],

			[ [$appName, '--foo', $commandName, $subcommandName], true ],
			[ [$appName, '--foo', $commandName, '--bar=baz', $subcommandName], true ],
		];
	}

	public function provider_inputArguments_withNoArguments() : array
	{
		$appName = self::__getUniqueString( 'app' );

		return [
			[ [$appName] ],
			[ [$appName, '--foo=bar'] ],
		];
	}

	public function provider_inputObjects() : array
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$inputs[] = new Input( [$appName, '--foo=bar'], [] );
		$inputs[] = new Input( [$appName, $commandName, '--foo=bar'], [] );
		$inputs[] = new Input( [$appName, $commandName, $subcommandName, '--foo=bar'], [] );

		$inputs[1]->recognizeCommand( true );
		$inputs[2]->recognizeSubcommand( true );

		return [
			$inputs
		];
	}

	/**
	 * Returns arrays of valid option strings
	 */
	public function provider_optionsArray()
	{
		return [
			[ [] ],
			[ ['--foo'] ],
			[ ['--foo','--bar'] ],
			[ ['--foo=bar','-vvv'] ],
			[ ['-abc'] ],
			[ ['-a','-b','-c'] ],
		];
	}

	/**
	 * Returns valid option strings with option name and expected option value
	 */
	public function provider_optionString_withMetadata()
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
	 * @expectedException	LengthException
	 */
	public function test_emptyArgumentsArray_throwsException()
	{
		$input = new Input( [], [] );
	}

	/**
	 * @dataProvider	provider_argumentsArray
	 */
	public function test_getApplicationName( array $arguments )
	{
		$appName = $arguments[0];
		$input = new Input( $arguments, [] );

		$this->assertEquals( $appName, $input->getApplicationName() );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_getApplicationOption( string $optionString, string $optionName, $expectedValue )
	{
		$appName = self::__getUniqueString( 'app' );
		$input = new Input( [$appName, $optionString], [] );

		$this->assertEquals( $expectedValue, $input->getApplicationOption( $optionName ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getApplicationOption_whenUndefined_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$input = new Input( [$appName], [] );

		$optionName = 'option-' . time();
		$input->getApplicationOption( $optionName );
	}

	public function test_getArgument_usingNumericIndex()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument], [] );

		$this->assertEquals( $argument, $input->getArgument( 0 ) );
	}

	public function test_getArgument_usingString()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument], [] );
		$input->nameArgument( 0, 'foo' );

		$this->assertEquals( $argument, $input->getArgument( 'foo' ) );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function test_getArgument_usingUnsupportedType_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument], [] );

		$input->getArgument( false );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getArgumentByIndex_withInvalidIndex_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument], [] );

		$this->assertTrue( $input->hasArgument( 0 ) );
		$this->assertFalse( $input->hasArgument( 1 ) );

		$input->getArgumentByIndex( 1 );
	}

	public function test_getArgumentByIndex_commandUnrecognized()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument_1 = self::__getUniqueString( 'arg' );
		$argument_2 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument_1, $argument_2], [] );

		$this->assertTrue( $input->hasArgument( 0 ) );
		$this->assertTrue( $input->hasArgument( 1 ) );
		$this->assertEquals( $argument_1, $input->getArgumentByIndex( 0 ) );
		$this->assertEquals( $argument_2, $input->getArgumentByIndex( 1 ) );
	}

	public function test_getArgumentByIndex_commandRecognized()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$argument_1 = self::__getUniqueString( 'arg' );
		$argument_2 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $commandName, $argument_1, $argument_2], [] );
		$input->recognizeCommand( true );

		$this->assertTrue( $input->hasArgument( 0 ) );
		$this->assertTrue( $input->hasArgument( 1 ) );
		$this->assertEquals( $argument_1, $input->getArgumentByIndex( 0 ) );
		$this->assertEquals( $argument_2, $input->getArgumentByIndex( 1 ) );
	}

	public function test_getArgumentByIndex_subcommandRecognized()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );
		$argument_1 = self::__getUniqueString( 'arg' );
		$argument_2 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $commandName, $subcommandName, $argument_1, $argument_2], [] );
		$input->recognizeSubcommand( true );

		$this->assertTrue( $input->hasArgument( 0 ) );
		$this->assertTrue( $input->hasArgument( 1 ) );
		$this->assertEquals( $argument_1, $input->getArgumentByIndex( 0 ) );
		$this->assertEquals( $argument_2, $input->getArgumentByIndex( 1 ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getArgumentByName_withInvalidName_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );

		$input = new Input( [$appName], [] );
		$input->nameArgument( 0, 'arg0' );

		$input->getArgumentByName( 'arg0' );
	}

	public function test_getArgumentByName_withValidName()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument_0 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument_0], [] );
		$input->nameArgument( 0, 'arg0' );

		$this->assertEquals( $argument_0, $input->getArgumentByName( 'arg0' ) );
	}

	public function test_getArguments_returnsArray()
	{
		$appName = self::__getUniqueString( 'app' );

		$argument_1 = self::__getUniqueString( 'arg' );
		$argument_2 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument_1, $argument_2], [] );

		$this->assertCount( 2, $input->getArguments() );
		$this->assertContains( $argument_1, $input->getArguments() );
		$this->assertContains( $argument_2, $input->getArguments() );
	}

	/**
	 * @expectedException	\OutOfBoundsException
	 */
	public function test_getCommandName_commandUnrecognized_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument], [] );

		$input->getCommandName();
	}

	/**
	 * @expectedException	\OutOfBoundsException
	 */
	public function test_getCommandName_commandRecognizedButNotPresent_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );

		$input = new Input( [$appName], [] );
		$input->recognizeCommand( true );

		$input->getCommandName();
	}

	/**
	 * @dataProvider	provider_optionsArray
	 */
	public function test_getCommandName_commandRecognizedAndPresent( array $optionsArray )
	{
		$commandName = self::__getUniqueString( 'command' );

		$arguments = $optionsArray;
		array_unshift( $optionsArray, self::__getUniqueString( 'app' ) );
		array_push( $optionsArray, $commandName );

		$input = new Input( $optionsArray, [] );
		$input->recognizeCommand( true );

		$this->assertEquals( $commandName, $input->getCommandName() );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getCommandOption_commandUnrecognized_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );

		$input = new Input( [$appName, $commandName, '--foo=bar'], [] );

		$input->getCommandOption( 'foo' );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_getCommandOption( string $optionString, string $optionName, $expectedValue )
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );

		$input = new Input( [$appName, $commandName, $optionString], [] );
		$input->recognizeCommand( true );

		$this->assertEquals( $expectedValue, $input->getCommandOption( $optionName ) );
	}

	public function test_getEnv()
	{
		$envName = 'FOO_' . time();
		$envValue = (string)microtime( true );

		$input = new Input( ['cranberry'], [$envName => $envValue] );

		$this->assertSame( $envValue, $input->getEnv( $envName ) );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getEnv_withUnknownKey_throwsException()
	{
		$envName = 'FOO_' . time();
		$input = new Input( ['cranberry'], [] );

		$input->getEnv( $envName );
	}

	/**
	 * @dataProvider	provider_inputObjects
	 */
	public function test_getOption( Input $input )
	{
		$this->assertEquals( 'bar', $input->getOption( 'foo' ) );
	}

	public function test_getOption_prefersSubcommandValue()
	{
		$appName = self::__getUniqueString( 'app' );
		$appOptionValue = 'app-option';

		$commandName = self::__getUniqueString( 'command' );
		$commandOptionValue = 'command-option';

		$subcommandName = self::__getUniqueString( 'subcommand' );
		$subcommandOptionValue = 'subcommand-option';

		$input = new Input( [$appName, "--foo={$appOptionValue}", $commandName, "--foo={$commandOptionValue}", $subcommandName, "--foo={$subcommandOptionValue}"], [] );
		$input->recognizeSubcommand( true );

		$this->assertSame( $subcommandOptionValue, $input->getOption( 'foo' ) );
	}

	/**
	 * @dataProvider	provider_inputObjects
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getOption_withUnknownOption_throwsException( Input $input )
	{
		$this->assertFalse( $input->hasOption( 'baz' ) );
		$input->getOption( 'baz' );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getSubcommandName_subcommandUnrecognized_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, '--foo=bar', $subcommandName], [] );

		$input->getSubcommandName();
	}

	/**
	 * @expectedException	\OutOfBoundsException
	 */
	public function test_getSubcommandName_subcommandRecognizedButNotPresent_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );

		$input = new Input( [$appName, $commandName, '--foo=bar'], [] );
		$input->recognizeCommand( true );

		$input->getSubcommandName();
	}

	public function test_getSubcommandName_subcommandRecognizedAndPresent()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, '--foo=bar', $subcommandName], [] );
		$input->recognizeSubcommand( true );

		$this->assertEquals( $subcommandName, $input->getSubcommandName() );
	}

	/**
	 * @expectedException	OutOfBoundsException
	 */
	public function test_getSubcommandOption_subcommandNotRecognized_throwsException()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, $subcommandName, '--foo=bar'], [] );

		$input->getCommandOption( 'foo' );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_getSubcommandOption( string $optionString, string $optionName, $expectedValue )
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, $subcommandName, $optionString], [] );
		$input->recognizeSubcommand( true );

		$this->assertEquals( $expectedValue, $input->getSubcommandOption( $optionName ) );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_hasApplicationOption_withMatch_returnsTrue( $argument, $optionName )
	{
		$appName = self::__getUniqueString( 'app' );
		$input = new Input( [$appName, $argument], [] );

		$this->assertTrue( $input->hasApplicationOption( $optionName ) );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_hasApplicationOption_withoutMatch_returnsFalse( $argument, $optionName )
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );
		$input = new Input( [$appName, $argument], [] );

		$this->assertFalse( $input->hasOption( $optionName ) );
	}

	public function test_hasApplicationOption_inAbnormalPosition()
	{
		$appName = self::__getUniqueString( 'app' );
		$argument = self::__getUniqueString( 'arg' );
		$input = new Input( [$appName, $argument, $argument, '--foo=bar'], [] );

		$this->assertTrue( $input->hasApplicationOption( 'foo' ) );
		$this->assertEquals( 'bar', $input->getApplicationOption( 'foo' ) );
	}

	/**
	 * @dataProvider	provider_inputArguments_withNoArguments
	 */
	public function test_hasArgument_withoutMatch_returnsFalse( array $arguments )
	{
		$input = new Input( $arguments, [] );
		$this->assertFalse( $input->hasArgument(0) );
	}

	/**
	 * @dataProvider	provider_hasArgument_returnsBool
	 */
	public function test_hasArgument_withMatch_returnsBool( array $arguments, bool $shouldHaveArgument, bool $shouldRecognizeCommand=false, bool $shouldRecognizeSubcommand=false )
	{
		$input = new Input( $arguments, [] );

		$input->recognizeCommand( $shouldRecognizeCommand );
		$input->recognizeSubcommand( $shouldRecognizeSubcommand );

		$this->assertEquals( $shouldHaveArgument, $input->hasArgument( 0 ) );
	}

	/**
	 * @dataProvider	provider_hasCommand
	 */
	public function test_hasCommand_commandUnrecognized_returnsFalse( array $arguments )
	{
		$input = new Input( $arguments, [] );
		$this->assertEquals( false, $input->hasCommand() );
	}

	/**
	 * @dataProvider	provider_hasCommand
	 */
	public function test_hasCommand_commandRecognized( array $arguments, bool $hasCommand )
	{
		$input = new Input( $arguments, [] );
		$input->recognizeCommand( true );

		$this->assertEquals( $hasCommand, $input->hasCommand() );
	}

	/**
	 * @dataProvider	provider_hasCommandOption_withoutMatch
	 */
	public function test_hasCommandOption_withoutMatch_returnsFalse( array $arguments )
	{
		$appName = self::__getUniqueString( 'app' );
		$optionName = self::__getUniqueString( 'option' );

		$input = new Input( [$appName], [] );
		$input->recognizeCommand( true );

		$this->assertFalse( $input->hasCommandOption($optionName) );
	}

	public function test_hasCommandOption_withMatch_returnsTrue()
	{
		$appName = self::__getUniqueString( 'app' );
		$optionName = self::__getUniqueString( 'option' );

		$input = new Input( [$appName], [] );

		$this->assertFalse( $input->hasCommandOption($optionName) );
	}

	public function test_hasCommandOption_inAbnormalPosition()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, '--boo=baz', $commandName, $argument, '--foo=bar'], [] );
		$input->recognizeCommand( true );

		$this->assertEquals( $commandName, $input->getCommandName() );

		$this->assertTrue( $input->hasCommandOption( 'foo' ) );
		$this->assertEquals( 'bar', $input->getCommandOption( 'foo' ) );
	}

	public function test_hasEnv_withoutMatch_returnsFalse()
	{
		$envName = 'FOO_' . time();
		$input = new Input( ['cranberry'], [] );

		$this->assertFalse( $input->hasEnv( $envName ) );
	}

	public function test_hasEnv_withMatch_returnsTrue()
	{
		$envName = 'FOO_' . time();
		$input = new Input( ['cranberry'], [$envName => microtime()] );

		$this->assertTrue( $input->hasEnv( $envName ) );
	}

	/**
	 * @dataProvider	provider_inputObjects
	 */
	public function test_hasOption( Input $input )
	{
		$invalidOption = self::__getUniqueString( 'option' );

		$this->assertTrue( $input->hasOption( 'foo' ) );
		$this->assertFalse( $input->hasOption( $invalidOption ) );
	}

	/**
	 * @dataProvider	provider_hasSubcommand
	 */
	public function test_hasSubcommand_subcommandUnrecognized( array $arguments )
	{
		$input = new Input( $arguments, [] );
		$this->assertEquals( false, $input->hasSubcommand() );
	}

	/**
	 * @dataProvider	provider_hasSubcommand
	 */
	public function test_hasSubcommand_subcommandRecognized( array $arguments, bool $hasSubcommand )
	{
		$input = new Input( $arguments, [] );
		$input->recognizeSubcommand( true );

		$this->assertEquals( $hasSubcommand, $input->hasSubcommand() );
	}

	public function test_hasSubcommandOption_inAbnormalPosition()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );
		$argument = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, '--app=one', $commandName, '--command=two', $subcommandName, $argument, '--subcommand=three'], [] );
		$input->recognizeSubcommand( true );

		$this->assertEquals( $subcommandName, $input->getSubcommandName() );

		$this->assertTrue( $input->hasSubcommandOption( 'subcommand' ) );
		$this->assertEquals( 'three', $input->getSubcommandOption( 'subcommand' ) );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_hasSubcommandOption_subcommandNotRecognized_returnsFalse( string $optionString, string $optionName )
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, $subcommandName, $optionString], [] );

		$this->assertFalse( $input->hasSubcommandOption( $optionName ) );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_hasSubcommandOption_subcommandRecognized_withoutMatch_returnsFalse( string $optionString, string $optionName, $expectedValue )
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );
		$subcommandOptionName = self::__getUniqueString( 'subcommand-option' );

		$input = new Input( [$appName, $commandName, $subcommandName, $optionString], [] );
		$input->recognizeSubcommand( true );

		$this->assertFalse( $input->hasSubcommandOption( $subcommandOptionName ) );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_hasSubcommandOption_subcommandRecognized_withMatch_returnsTrue( string $optionString, string $optionName, $expectedValue )
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );
		$subcommandName = self::__getUniqueString( 'subcommand' );

		$input = new Input( [$appName, $commandName, $subcommandName, $optionString], [] );
		$input->recognizeSubcommand( true );

		$this->assertTrue( $input->hasSubcommandOption( $optionName ) );
	}

	public function test_nameArgument_commandNotRecognized()
	{
		$appName = self::__getUniqueString( 'app' );

		$argument_0 = self::__getUniqueString( 'arg' );
		$argument_1 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $argument_0, $argument_1], [] );

		$input->nameArgument( 0, 'arg0' );
		$input->nameArgument( 1, 'arg1' );

		$this->assertSame( $argument_0, $input->getArgumentByIndex( 0 ) );
		$this->assertSame( $argument_1, $input->getArgumentByIndex( 1 ) );

		$this->assertSame( $argument_0, $input->getArgumentByName( 'arg0' ) );
		$this->assertSame( $argument_1, $input->getArgumentByName( 'arg1' ) );
	}

	public function test_nameArgument_commandIsRecognized()
	{
		$appName = self::__getUniqueString( 'app' );
		$commandName = self::__getUniqueString( 'command' );

		$argument_0 = self::__getUniqueString( 'arg' );
		$argument_1 = self::__getUniqueString( 'arg' );

		$input = new Input( [$appName, $commandName, $argument_0, $argument_1], [] );
		$input->recognizeCommand( true );

		$input->nameArgument( 0, 'arg0' );
		$input->nameArgument( 1, 'arg1' );

		$this->assertSame( $argument_0, $input->getArgumentByIndex( 0 ) );
		$this->assertSame( $argument_1, $input->getArgumentByIndex( 1 ) );

		$this->assertSame( $argument_0, $input->getArgumentByName( 'arg0' ) );
		$this->assertSame( $argument_1, $input->getArgumentByName( 'arg1' ) );
	}

	/**
	 * Parsing an option string which does not match `-a`, `-abc`, `--foo`, or
	 * `--foo=bar should throw an exception
	 *
	 * @expectedException	InvalidArgumentException
	 */
	public function test_getOptionStringValues_withInvalidFormat_throwsException()
	{
		$result = Input::getOptionStringValues( 'foo' );
		$this->assertFalse( $result );
	}

	/**
	 * @dataProvider	provider_optionString_withMetadata
	 */
	public function test_getOptionStringValues_withValidFormat( $argument, $optionName, $expectedValue )
	{
		$result = Input::getOptionStringValues( $argument );

		$this->assertTrue( is_array( $result ) );
		$this->assertTrue( isset( $result[$optionName] ) );

		$this->assertSame( $expectedValue, $result[$optionName] );
	}
}

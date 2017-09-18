<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Output;

use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
	/**
	 * @var    string
	 */
	protected static $tempPathname;

	public static function setUpBeforeClass()
	{
		self::$tempPathname = dirname( dirname( __DIR__ ) ) . '/fixtures/temp';
		if( !file_exists( self::$tempPathname ) )
		{
			mkdir( self::$tempPathname, 0777, true );
		}
	}

	public static function tearDownAfterClass()
	{
		if( file_exists( self::$tempPathname ) )
		{
			$command = sprintf( 'rm -r %s', self::$tempPathname );
			exec( $command );
		}
	}

	public function testBufferDoesNotWriteToStream()
	{
		$output = new Output();

		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$contents = sprintf( 'contents %s', microtime( true ) );
		$output->buffer( $contents );

		$this->assertFalse( file_exists( $streamTarget ) );
	}

	public function testBufferAppendsToExistingBuffer()
	{
		$output = new Output();

		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$output->buffer( 'Hello' );
		$output->buffer( ', world.' );

		$this->assertFalse( file_exists( $streamTarget ) );

		$output->flush();

		$this->assertTrue( file_exists( $streamTarget ) );

		$actualContents = file_get_contents( $streamTarget );
		$this->assertEquals( 'Hello, world.', $actualContents );
	}

	public function testFlushEmptiesBuffer()
	{
		$output = new Output();

		$output->buffer( 'Hello' );
		$output->flush();

		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$this->assertFalse( file_exists( $streamTarget ) );

		$output->setStream( 'file', $streamTarget );

		$output->flush();
		$this->assertTrue( file_exists( $streamTarget ) );

		$actualContents = file_get_contents( $streamTarget );
		$this->assertEquals( '', $actualContents );
	}

	public function testFlushWritesBufferToStream()
	{
		$output = new Output();

		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$expectedContents = sprintf( 'contents %s', microtime( true ) );
		$output->buffer( $expectedContents );

		$this->assertFalse( file_exists( $streamTarget ) );

		$output->flush();

		$this->assertTrue( file_exists( $streamTarget ) );

		$actualContents = file_get_contents( $streamTarget );
		$this->assertEquals( $expectedContents, $actualContents );
	}

	public function testWriteToStream()
	{
		$output = new Output();

		$streamTarget = sprintf( '%s/%s.txt', self::$tempPathname, microtime( true ) );
		$output->setStream( 'file', $streamTarget );

		$expectedContents = sprintf( 'contents %s', microtime( true ) );
		$output->write( $expectedContents );

		$actualContents = file_get_contents( $streamTarget );
		$this->assertEquals( $expectedContents, $actualContents );
	}
}

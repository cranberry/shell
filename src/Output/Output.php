<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\Shell\Output;

class Output implements OutputInterface
{
	/**
	 * @var	string
	 */
	protected $buffer='';

	/**
	 * @var	string
	 */
	protected $streamURI='php://stdout';

	/**
	 * Write string to buffer
	 *
	 * @param	string	$string
	 *
	 * @return	void
	 */
	public function buffer( string $string )
	{
		$this->buffer .= $string;
	}

	/**
	 * Flush the write buffer
	 *
	 * Attempt to push buffered output to the stream
	 *
	 * @return	void
	 */
	public function flush()
	{
		$this->write( $this->buffer );
		$this->buffer = '';
	}

	/**
	 * Set the output stream
	 *
	 * @param	string	$protocol
	 *
	 * @param	string	$target
	 *
	 * @param	string	$mode
	 *
	 * @return	void
	 */
	public function setStream( string $protocol, string $target, string $mode='a' )
	{
		$this->streamURI = sprintf( '%s://%s', $protocol, $target );

		$supportedModes = ['w','a','x','c'];
		if( !in_array( $mode, $supportedModes ) )
		{
			throw new \InvalidArgumentException( "Unsupported mode '{$mode}'" );
		}

		$this->streamMode = $mode;
	}

	/**
	 * Write string to stream
	 *
	 * @param	string	$string
	 *
	 * @return	void
	 */
	public function write( string $string )
	{
		$handle = @fopen( $this->streamURI, $this->streamMode );

		if( !is_resource( $handle ) )
		{
			throw new \InvalidArgumentException( "Invalid or unwritable stream '{$this->streamURI}'" );
		}

		fwrite( $handle, $string );
		fclose( $handle );
	}
}

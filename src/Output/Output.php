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
	protected $stream='php://stdout';

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
	 * @return	void
	 */
	public function setStream( string $protocol, string $target )
	{
		$this->stream = sprintf( '%s://%s', $protocol, $target );
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
		$handle = fopen( $this->stream, 'a' );
		fwrite( $handle, $string );
		fclose( $handle );
	}
}

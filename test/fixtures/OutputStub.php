<?php

/*
 * This file is part of Cranberry\Shell
 */
namespace Cranberry\ShellTest;

use Cranberry\Shell\Output;

class OutputStub extends Output\Output
{
	/**
	 * Returns buffer contents
	 *
	 * @return	string
	 */
	public function getBuffer() : string
	{
		$buffer = $this->buffer;
		$this->buffer = '';

		return $buffer;
	}

	/**
	 * Redirect string writing to buffer
	 *
	 * @param	string	$string
	 *
	 * @return	void
	 */
	public function write( string $string )
	{
		$this->buffer( $string );
	}
}

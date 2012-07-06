<?php
class ExampleTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Example Test for module unittest
   * @group unittest
   */
	function testAlwaysTrue() {
		PHPUnit_Framework_Assert::assertEquals(1, 1);
	}
	
}
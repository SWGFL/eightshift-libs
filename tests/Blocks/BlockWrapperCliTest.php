<?php

namespace Tests\Unit\Block;

use EightshiftLibs\Blocks\BlockWrapperCli;

use function Tests\deleteCliOutput;
use function Tests\mock;

/**
 * Mock before tests.
 */
beforeEach(function () {
	$wpCliMock = mock('alias:WP_CLI');

$wpCliMock
	->shouldReceive('success')
	->andReturnArg(0);

$wpCliMock
	->shouldReceive('error')
	->andReturnArg(0);

$wpCliMock
	->shouldReceive('log')
	->andReturnArg(0);

$this->wrapper = new BlockWrapperCli('boilerplate');
});

/**
 * Cleanup after tests.
 */
afterEach(function () {
	deleteCliOutput();
});

 test('Wrapper CLI command will correctly copy the Wrapper class with defaults', function () {
	$wrapperMock = mock(BlockWrapperCli::class)
		->makePartial()
		->shouldReceive('getFrontendLibsBlockPath')
		->andReturn(\dirname(__FILE__, 2) . '/data');

	$mock = $wrapperMock->getMock();

	$mock([], [$this->wrapper->getDevelopArgs([])]);

	$outputPath = \dirname(__FILE__, 3) . '/cliOutput/wrapper.php';

	// Check the output dir if the generated method is correctly generated.
	$generatedWrapper = \file_get_contents($outputPath);

	$this->assertStringContainsString('<div>Wrapper!</div>', $generatedWrapper);
	$this->assertFileExists($outputPath);
 });

 test('Wrapper CLI command will run under custom command name', function () {
	$wrapper = $this->wrapper;
	$result = $wrapper->getCommandName();

	expect($result)->toContain('wrapper');
});

test('Wrapper CLI documentation is correct', function () {
	expect($this->wrapper->getDoc())->toBeArray();
});

test('Wrapper CLI command will fail if Wrapper doesn\'t exist', function () {
	$wrapperMock = mock(BlockWrapperCli::class)
		->makePartial()
		->shouldReceive('getFrontendLibsBlockPath')
		->andReturn(\dirname(__FILE__, 2) . '/data');

	$mock = $wrapperMock->getMock();

	$mock([], ['name' => 'testing']);

	$outputPath = \dirname(__FILE__, 3) . '/cliOutput/testing/testing.php';

	$this->assertFileDoesNotExist($outputPath);
});

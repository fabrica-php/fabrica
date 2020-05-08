<?php declare(strict_types=1);

namespace Noj\Fabrica\Adapter\Doctrine\PHPUnit;

use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeTestHook;

class RefreshDatabase implements BeforeTestHook, AfterTestHook
{
	use DatabaseFixtures;

	public function executeBeforeTest(string $test): void
	{
		$this->create();
	}

	public function executeAfterTest(string $test, float $time): void
	{
		$this->truncate();
	}
}
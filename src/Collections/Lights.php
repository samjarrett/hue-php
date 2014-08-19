<?php

namespace Hue\Collections;

use ArrayIterator;
use Hue\Bridge;
use Hue\Light;

class Lights extends \ArrayObject
{
	/**
	 * @var \Hue\Bridge
	 */
	private $bridge;

	/**
	 * @var bool
	 */
	private $initialised = false;

	/**
	 * @var array
	 */
	private $nameLookup = array();

	public function __construct(Bridge $bridge)
	{
		$this->bridge = $bridge;
	}

	public function isInitialised()
	{
		return $this->initialised;
	}

	private function initialise($force = false)
	{
		if ($this->isInitialised() && !$force)
		{
			return true;
		}

		$response = $this->bridge->getTransport()->get('lights')->send();
		foreach ($response->json() as $id => $item) {
			$light = new Light($this->bridge, $id, $item['name'], $item['type'], $item['modelid'], $item['swversion'], $item['state']);
			$this->nameLookup[$item['name']] = $id;
			$this[$id] = $light;
		}

		$this->initialised = true;

		return true;
	}

	public function offsetExists($index)
	{
		$this->initialise();
		if (array_key_exists($index, $this->nameLookup)) {
			$index = $this->nameLookup[$index];
		}

		return parent::offsetExists($index);
	}

	public function offsetGet($index)
	{
		$this->initialise();
		if (array_key_exists($index, $this->nameLookup)) {
			$index = $this->nameLookup[$index];
		}

		return parent::offsetGet($index);
	}

	public function count()
	{
		$this->initialise();
		return parent::count();
	}

	public function getIterator()
	{
		$this->initialise();
		return parent::getIterator();
	}
}
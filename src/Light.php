<?php

namespace Hue;

class Light {
	const EFFECT_NONE      = 'none';
	const EFFECT_COLORLOOP = 'colorloop';

	/**
	 * @var Bridge
	 */
	private $bridge;

	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $modelId;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var array Current (persisted) state of the light
	 */
	private $currentState;

	/**
	 * @var array New (proposed) state of the light
	 */
	private $newState;

	/**
	 * @param Bridge $bridge
	 * @param integer $id
	 * @param string  $name
	 * @param string  $type
	 * @param string  $modelId
	 * @param string  $version
	 * @param array   $state
	 */
	public function __construct(Bridge $bridge, $id, $name, $type, $modelId, $version, $state)
	{
		$this->bridge = $bridge;
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->modelId = $modelId;
		$this->version = $version;
		$this->currentState = $state;
		$this->newState = $state;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getModelId()
	{
		return $this->modelId;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Retrieve the new (proposed) state of the light
	 * @return array
	 */
	public function getState()
	{
		return $this->newState;
	}

	/**
	 * Reload light's state from the bridge
	 */
	public function reload()
	{
		$response = $this->bridge->getTransport()->get('lights/' . $this->id)->send();
		$data = $response->json();

		$this->currentState = $this->newState = $data['state'];
	}

	/**
	 * Retrieve the changed state fields
	 * @return array
	 */
	protected function getStateChanges()
	{
		return array_udiff_assoc(
			$this->newState,
			$this->currentState,
			function($a, $b) { return $a === $b ? 0 : 1; }
		);
	}

	/**
	 * Revert any pending changes to the light
	 */
	public function revert()
	{
		$this->newState = $this->currentState;
	}

	public function commit()
	{
		$changes = $this->getStateChanges();
		if (empty($changes)) {
			return;
		}

		$response = $this->bridge->getTransport()->put('lights/' . $this->id . '/state', null, json_encode($changes))->send();

		$success = true;
		foreach ($response->json() as $item) {
			if (array_key_exists('error', $item)) {
				$success = false;
			}
		}

		$this->reload();

		return $success;
	}

	/**
	 * Checks if the light is in range of the bridge
	 * @return bool
	 */
	public function isReachable()
	{
		return $this->currentState['reachable'];
	}

	/**
	 * @return bool
	 */
	public function isOn()
	{
		return $this->newState['on'];
	}

	/**
	 * @return bool
	 */
	public function isOff()
	{
		return !$this->newState['on'];
	}

	/**
	 * Turn on the light
	 */
	public function turnOn()
	{
		$this->newState['on'] = true;
	}

	/**
	 * Turn off the light
	 */
	public function turnOff()
	{
		$this->newState['on'] = false;
	}

	/**
	 * Retrieve the brightness of the light
	 * @return integer
	 */
	public function getBrightness()
	{
		return $this->newState['bri'];
	}

	/**
	 * Adjust the brightness of the light
	 * @param integer $value [0 - 255]
	 * @throws \InvalidArgumentException
	 */
	public function setBrightness($value)
	{
		if (!is_int($value) || $value < 0 || $value > 255) {
			throw new \InvalidArgumentException('Brightness must be between 0 and 255');
		}

		$this->newState['bri'] = $value;
	}

	/**
	 * Retrieve the hue of the light
	 * @return integer
	 */
	public function getHue()
	{
		return $this->newState['hue'];
	}

	/**
	 * Adjust the hue of the light
	 * @param integer $value
	 * @throws \InvalidArgumentException
	 */
	public function setHue($value)
	{
		if (!is_int($value) || $value < 0 || $value > 65535) {
			throw new \InvalidArgumentException('Hue must be between 0 and 65535');
		}

		$this->newState['hue'] = $value;
	}

	/**
	 * Retrieve the saturation of the light
	 * @return integer
	 */
	public function getSaturation()
	{
		return $this->newState['sat'];
	}

	/**
	 * Adjust the saturation of the light
	 * @param integer $value
	 * @throws \InvalidArgumentException
	 */
	public function setSaturation($value)
	{
		if (!is_int($value) || $value < 0 || $value > 255) {
			throw new \InvalidArgumentException('Saturation must be between 0 and 255');
		}

		$this->newState['sat'] = $value;
	}

	/**
	 * @return string [none|colorloop]
	 */
	public function getEffect()
	{
		return $this->newState['effect'];
	}

	/**
	 * @param string $value [none|colorloop]
	 */
	public function setEffect($value)
	{
		$this->newState['effect'] = $value;
	}
}
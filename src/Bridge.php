<?php

namespace Hue;

use Guzzle\Http\Client;
use Hue\Collections\Bridges;
use Hue\Collections\Lights;
use Hue\Exception\HueException;
use Hue\Exception\LinkButtonNotPressedException;

class Bridge
{
	/**
	 * @var string IP address of the device
	 */
	private $ipAddress;

	/**
	 * @var string ID of the device
	 */
	private $id;

	/**
	 * @var string MAC address of the device
	 */
	private $macAddress;

	/**
	 * @var string Name of the device
	 */
	private $name;

	/**
	 * @var string Username of your application
	 */
	private $username;

	public $lights;

	/**
	 * @param string $ipAddress   IP address of the device
	 * @param string $id          ID of the device
	 * @param string $macAddress  MAC address of the device
	 * @param string $name        Name of the device
	 */
	public function __construct($ipAddress, $id = null, $macAddress = null, $name = null)
	{
		$this->ipAddress = $ipAddress;
		$this->id = $id;
		$this->macAddress = $macAddress;
		$this->name = $name;
		$this->lights = new Lights($this);
	}

	public static function discover()
	{
		$client = new Client();
		$response = $client->get('http://www.meethue.com/api/nupnp')->send();

		$bridges = new Bridges();
		foreach ($response->json() as $bridge) {
			$bridges[] = new static($bridge['internalipaddress'], $bridge['id'], $bridge['macaddress'], $bridge['name']);
		}

		return $bridges;
	}

	/**
	 * Retrieve a configured Guzzle client
	 * @return Client
	 */
	public function getTransport($authenticated = true)
	{
		$url = 'http://' . $this->ipAddress . '/api';
		if ($this->isAuthenticated() && $authenticated) {
			$url .= $this->username . '/';
		}

		return new Client($url);
	}

	public function authenticate($deviceType, $username)
	{
		// Step 1: Try accessing a resource
		$this->username = $username;
		$response = $this->getTransport()->get(null)->send();
		$json = $response->json();
		if (empty($json[0]['error'])) {
			return true;
		}

		// Failing that, try authenticating
		$body = json_encode(array(
			'devicetype' => $deviceType,
			'username'   => $username
		));

		$response = $this->getTransport(false)->post(null, null, $body)->send();

		$body = $response->json();

		$content = $body[0];

		if (!empty($content['error'])) {
			switch ($content['error']['type']) {
				case '101':
					throw new LinkButtonNotPressedException($content['error']['description'], $content['error']['type']);

				default:
					throw new HueException($content['error']['description'], $content['error']['type']);
			}
		}

		$this->username = $username;

		return true;
	}

	public function isAuthenticated()
	{
		return !empty($this->username);
	}

	
}
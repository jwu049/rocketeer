<?php
/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Rocketeer\Services\Connections;

use Illuminate\Remote\Connection;
use InvalidArgumentException;
use Rocketeer\Traits\HasLocator;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Handle creationg and caching of connections
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 * @author Taylor Otwell
 */
class RemoteHandler
{
	use HasLocator;

	/**
	 * A storage of active connections
	 *
	 * @type Connection[]
	 */
	protected $active = [];

	/**
	 * Get the current Connection
	 */
	public function connection()
	{
		$name   = $this->connections->getConnection();
		$server = $this->connections->getServer();
		$handle = $name.'#'.$server;

		// Check the cache
		if (isset($this->active[$handle])) {
			return $this->active[$handle];
		}

		// Create connection
		$credentials = $this->connections->getServerCredentials();
		$connection  = $this->makeConnection($name, $credentials);

		// Save to cache
		$this->active[$handle] = $connection;

		return $connection;
	}

	/**
	 * @param string $name
	 * @param array  $credentials
	 *
	 * @return Connection
	 */
	protected function makeConnection($name, array $credentials)
	{
		$connection = new Connection(
			$name,
			$credentials['host'],
			$credentials['username'],
			$this->getAuth($credentials)
		);

		// Set output on connection
		$output = $this->hasCommand() ? $this->command->getOutput() : new NullOutput();
		$connection->setOutput($output);

		return $connection;
	}

	/**
	 * Format the appropriate authentication array payload.
	 *
	 * @param  array $config
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	protected function getAuth(array $config)
	{
		if (isset($config['agent']) && $config['agent'] === true) {
			return array('agent' => true);
		} elseif (isset($config['key']) && trim($config['key']) != '') {
			return array('key' => $config['key'], 'keyphrase' => $config['keyphrase']);
		} elseif (isset($config['keytext']) && trim($config['keytext']) != '') {
			return array('keytext' => $config['keytext']);
		} elseif (isset($config['password'])) {
			return array('password' => $config['password']);
		}

		throw new InvalidArgumentException('Password / key is required.');
	}

	/**
	 * Dynamically pass methods to the default connection.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 *
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array([$this->connection(), $method], $parameters);
	}
}

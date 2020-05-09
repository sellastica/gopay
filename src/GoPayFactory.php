<?php
namespace Sellastica\GoPay;

class GoPayFactory
{
	/**
	 * @var array
	 */
	private $config;


	/**
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * @return \GoPay\Payments
	 */
	public function create(): \GoPay\Payments
	{
		return \GoPay\Api::payments([
			'goid' => $this->config['goid'],
			'clientId' => $this->config['clientId'],
			'clientSecret' => $this->config['clientSecret'],
			'isProductionMode' => !$this->config['testMode'],
			'scope' => \GoPay\Definition\TokenScope::ALL,
			'language' => \GoPay\Definition\Language::CZECH,
			'timeout' => 30
		]);
	}
}
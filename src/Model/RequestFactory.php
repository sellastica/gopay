<?php
namespace Sellastica\GoPay;

class RequestFactory
{
	/**
	 * @var array
	 */
	private $config;
	/**
	 * @var \Nette\Localization\ITranslator
	 */
	private $translator;


	/**
	 * @param array $config
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		array $config,
		\Nette\Localization\ITranslator $translator
	)
	{
		$this->config = $config;
		$this->translator = $translator;
	}

	/**
	 * @param \Sellastica\Crm\Entity\Invoice\Entity\Invoice $invoice
	 * @param string $notificationUrl
	 * @param string $returnUrl
	 * @param string|null $defaultPaymentInstrument
	 * @param bool $recurringPayment
	 * @return array
	 */
	public function createPaymentRequest(
		\Sellastica\Crm\Entity\Invoice\Entity\Invoice $invoice,
		string $notificationUrl,
		string $returnUrl,
		string $defaultPaymentInstrument = null,
		bool $recurringPayment = false
	): array
	{
		//customer
		$billingAddress = $invoice->getProject()->getBillingAddress()
			? $invoice->getProject()->getBillingAddress()
			: null;
		$customer = [
			'email' => $invoice->getProject()->getEmail(),
			'phone_number' => $invoice->getProject()->getPhone(),
		];
		if ($billingAddress) {
			$customer += [
				'first_name' => $billingAddress->getFirstName(),
				'last_name' => $billingAddress->getLastName(),
				'street' => $billingAddress->getStreet(),
				'city' => $billingAddress->getCity(),
				'postal_code' => $billingAddress->getZip(),
				'country_code' => $billingAddress->getCountry()->getCode(),
			];
		}

		//items
		$items = [[
			'type' => \GoPay\Definition\Payment\PaymentItemType::ITEM,
			'name' => $this->translator->translate('apps.suppliers.invoices.invoice_number_no', [
				'code' => $invoice->getCode(),
			]),
			'amount' => $invoice->getPriceToPayLeft() * $invoice->getPrice()->getCurrency()->getSubUnit(),
			'count' => 1,
			'vat_rate' => \GoPay\Definition\Payment\VatRate::RATE_4,
		]];

		$request = [
			'payer' => [
				'default_payment_instrument' => $defaultPaymentInstrument,
				'contact' => $customer,
			],
			'target' => [
				'type' => 'ACCOUNT',
				'goid' => $this->config['goid'],
			],
			'amount' => $invoice->getPriceToPayLeft() * $invoice->getPrice()->getCurrency()->getSubUnit(),
			'currency' => $invoice->getPrice()->getCurrency()->getCode(),
			'order_number' => $invoice->getCode(),
			'order_description' => $this->translator->translate('apps.suppliers.invoices.invoice_number_no', [
				'code' => $invoice->getCode(),
			]),
			'items' => $items,
			'callback' => [
				'notification_url' => $notificationUrl,
				'return_url' => $returnUrl,
			],
		];

		//recurring payment
		if ($recurringPayment) {
			$request += [
				'recurrence' => [
					'recurrence_cycle' => \GoPay\Definition\Payment\Recurrence::ON_DEMAND,
					'recurrence_date_to' => '2099-12-31',
				]
			];
		}

		return $request;
	}

	/**
	 * @param \Sellastica\Crm\Entity\Invoice\Entity\Invoice $invoice
	 * @return array
	 */
	public function createRecurrenceRequest(\Sellastica\Crm\Entity\Invoice\Entity\Invoice $invoice): array
	{
		$items = [[
			'type' => \GoPay\Definition\Payment\PaymentItemType::ITEM,
			'name' => $this->translator->translate('apps.suppliers.invoices.invoice_number_no', [
				'code' => $invoice->getCode(),
			]),
			'amount' => $invoice->getPriceToPayLeft() * $invoice->getPrice()->getCurrency()->getSubUnit(),
			'count' => 1,
			'vat_rate' => \GoPay\Definition\Payment\VatRate::RATE_4,
		]];
		return [
			'amount' => $invoice->getPriceToPayLeft() * $invoice->getPrice()->getCurrency()->getSubUnit(),
			'currency' => $invoice->getPrice()->getCurrency()->getCode(),
			'order_number' => $invoice->getCode(),
			'order_description' => $this->translator->translate('apps.suppliers.invoices.invoice_number_no', [
				'code' => $invoice->getCode(),
			]),
			'items' => $items,
		];
	}
}
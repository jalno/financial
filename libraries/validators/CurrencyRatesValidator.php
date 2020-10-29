<?php
namespace packages\financial\validators;

use packages\base\{db\DuplicateRecord, Validator\IValidator, Validator\NumberValidator, InputValidationException};
use packages\financial\Currency;

class CurrencyRatesValidator implements IValidator {
	public function getTypes(): array {
		return [];
	}
	public function validate(string $input, array $rule, $data) {
		if (!is_array($data)) {
			throw new InputValidationException($input);
		}
		$cleanCurrencies = [];
		$clean = [];
		foreach ($data as $key => $rate) {
			if (!isset($rate["currency"]) or !isset($rate["price"])) {
				throw new InputValidationException("rates[{$key}]");
			}
			if (in_array($rate["currency"], $cleanCurrencies)) {
				throw new DuplicateRecord("rates[{$key}][currency]");
			}
			if ($rate["price"] <= 0) {
				throw new InputValidationException("rates[{$key}][price]");
			}
			$clean[] = array(
				'currency' => (new Currency)->validate("rates[{$key}][currency]", [], $rate["currency"]),
				'price' => (new NumberValidator)->validate("rates[{$key}][price]", ['type' => 'float'], $rate['price']),
			);
			$cleanCurrencies[] = $rate["currency"];
		}
		return $clean;
	}
}

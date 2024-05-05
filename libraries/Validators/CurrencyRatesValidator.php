<?php

namespace packages\financial\Validators;

use packages\base\DB\DuplicateRecord;
use packages\base\InputValidationException;
use packages\base\Validator\IValidator;
use packages\base\Validator\NumberValidator;
use packages\financial\Currency;

class CurrencyRatesValidator implements IValidator
{
    public function getTypes(): array
    {
        return [];
    }

    public function validate(string $input, array $rule, $data)
    {
        if (!is_array($data)) {
            throw new InputValidationException($input);
        }
        $cleanCurrencies = [];
        $clean = [];
        foreach ($data as $key => $rate) {
            if (!isset($rate['currency']) or !isset($rate['price'])) {
                throw new InputValidationException("rates[{$key}]");
            }
            if (in_array($rate['currency'], $cleanCurrencies)) {
                throw new DuplicateRecord("rates[{$key}][currency]");
            }
            if ($rate['price'] <= 0) {
                throw new InputValidationException("rates[{$key}][price]");
            }
            $clean[] = [
                'currency' => (new Currency())->validate("rates[{$key}][currency]", [], $rate['currency']),
                'price' => (new NumberValidator())->validate("rates[{$key}][price]", ['type' => 'float'], $rate['price']),
            ];
            $cleanCurrencies[] = $rate['currency'];
        }

        return $clean;
    }
}

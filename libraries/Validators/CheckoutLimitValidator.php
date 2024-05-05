<?php

namespace packages\financial\Validators;

use packages\base\InputValidationException;
use packages\base\Validator\IValidator;
use packages\financial\Currency;

class CheckoutLimitValidator implements IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['checkout-limit-validator'];
    }

    /**
     * Validate data to be a ipv4.
     *
     * @throws InputValidationException
     *
     * @param mixed $data
     *
     * @return mixed|null new value, if needed
     */
    public function validate(string $input, array $rule, $data)
    {
        if (!is_array($data)) {
            throw new InputValidationException($input);
        }

        foreach (['price', 'currency', 'period'] as $requireItem) {
            if (!isset($data[$requireItem]) or !is_numeric($data[$requireItem]) or $data[$requireItem] < 0) {
                throw new InputValidationException($input."[{$requireItem}]");
            }
        }

        $query = new Currency();
        $query->where('id', $data['currency']);
        if (!$query->has()) {
            throw new InputValidationException($input.'[currency]');
        }

        return [
            'price' => $data['price'],
            'currency' => $data['currency'],
            'period' => $data['period'] * 86400,
        ];
    }
}

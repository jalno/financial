<?php
namespace packages\financial\Validators;

use packages\base\{DB\DuplicateRecord, InputValidationException, Validator\IValidator, DB\Parenthesis};
use packages\financial\{Authentication, Authorization, Transaction};
use packages\userpanel\{User};

class IntervalValidator implements IValidator {
	public function getTypes(): array {
		return [];
	}

	/**
	 * validate a string to be a valid interval
	 *
	 * @param string $input
	 * @param array $rule that can be like this:
	 * 	array(
	 * 		[empty]: bool
	 * 		[default]: mixed
	 * 		[values]: the values that is acceptable
	 * 		[raw]: return raw value like: 1m, 2m, ...
	 * 	)
	 * @return int|string
	 */
	public function validate(string $input, array $rule, $data) {

		if (empty($data)) {

			if (!isset($rule['empty']) or !$rule['empty']) {
				throw new InputValidationException($input);
			}

			if (isset($rule['default'])) {
				if (!isset($rule["raw"]) or $rule["raw"]) {
					return $rule['default'];
				}
			} else {
				return;
			}
		}

		if (!is_string($data)) {
			throw new InputValidationException($input, "data-type");
		}

		if (!preg_match("/^([0-9]{1,2})([mHDWMY])$/", $data, $matches)) {
			throw new InputValidationException($input, "not-valid-format");
		}

		if (isset($rule["values"]) and $rule["values"] and !in_array($data, $rule["values"])) {
			throw new InputValidationException($input, "not-valid-value");
		}

		$interval = $matches[1];
		switch ($matches[2]) {
			case "m": $interval *= 60; break; // minute
			case "H": $interval *= 3600; break; // Hour
			case "D": $interval *= 86400; break; // Day
			case "W": $interval *= 604800; break; // Week
			case "M": $interval *= 2592000; break; // Month
			case "Y": $interval *= 31104000; break; // Year
		}
		
		if (isset($rule["raw"]) and $rule["raw"]) {
			return $data;
		}
		return $interval;
	}
}

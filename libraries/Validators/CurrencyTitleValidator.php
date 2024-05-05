<?php
namespace packages\financial\Validators;

use packages\base\{DB\DuplicateRecord, Validator\StringValidator};
use packages\financial\Currency;

class CurrencyTitleValidator extends StringValidator {
	public function validate(string $input, array $rule, $data) {
		$data = parent::validate($input, $rule, $data);
		$model = new Currency();
		if (isset($rule['self'])) {
			$model->where("id", $rule['self'], "!=");
		}
		$model->where("title", $data);
		if ($model->has()) {
			throw new DuplicateRecord($input);
		}
		return $data;
	}
}

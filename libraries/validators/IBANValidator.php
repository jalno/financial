<?php
namespace packages\financial\Validators;

use packages\base\{http, IO\file, IO\Directory, Exception, InputValidationException};

class IBANValidator implements IValidator {
	
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['iban'];
	}

	/**
	 * Validate data to be a IBAN code.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return packages\base\IO\file\local|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		$countries = array(
			'al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,
			'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,
			'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,
			'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,
			'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,
			'ae'=>23,'gb'=>22,'vg'=>24,
		);
		if (!is_string($data) or !$data) {
			throw new InputValidationException($input);
		}
		$countryCode = strtolower(substr($data, 0, 2));
		if (!in_array($countryCode, $countries)) {
			throw new InputValidationException($input);
		}
		if (strlen($data) != $countries[$countryCode]) {
			throw new InputValidationException($input);
		}
		if (!preg_match('/\w{2}\d+$/', $data)) {
			throw new InputValidationException($input);
		}
		$twoFirstChar = "";
		for ($i = 0, $a = ord("A"); $i < 2; $i++) {
			$twoFirstChar .= ord($countryCode[$i]) - $a + 10;
		}
		$twoFirstChar = substr($data, 4) . $twoFirstChar . substr($data, 2, 4);
		$result = bcmod($twoFirstChar, 97);
		if ($result != "1") {
			throw new InputValidationException($input);
		}
	}

	
}

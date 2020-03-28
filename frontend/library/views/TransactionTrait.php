<?php
namespace themes\clipone\views;

trait TransactionTrait {
	public function numberFormat($number): string {
		$number = explode(".", $number);
		if (!$number) {
			return "0";
		}
		$digit = number_format($number[0]);
		return ($digit ? $digit : 0) . (isset($number[1]) ? "." . $number[1] : "");
	}
}

<?php
namespace packages\financial;

use packages\userpanel\{User};
use packages\financial\{Authorization, Transaction, Currency, Transaction_pay as Pay};

class Stats {

	/**
	 * get the sum of user's spend or gain from system in the user's default currency
	 *
	 * @param User $user
	 * @param bool $paid it true, will calculate user's spend, else calculate user's gain
	 * @param int $from unix time stamp value that indicats start of period
	 * @param int $to unix time stamp value that indicates end of period
	 */
	public static function getStatsSumByUser(User $user, bool $paid, int $from, int $to) {
		$defaultCurrency = Currency::getDefault($user);
		$pays = self::getStatsByUserQuery($user, $paid, $from, $to);
		$pays->groupBy("financial_transactions_pays.currency");
		$pays->ArrayBuilder();
		$pays = $pays->get(null, array(
			"financial_transactions_pays.currency",
			"SUM(`financial_transactions_pays`.`price`) as `sum`"
		));
		// var_dump(\packages\base\db::getLastQuery());
		$sum = 0;
		foreach ($pays as $pay) {
			$currency =  (new Currency)->byId($pay["currency"]);
			$sum += $currency->changeTo(abs($pay["sum"]), $defaultCurrency);
		}
		return $sum;
	}

	/**
	 * get chart data for user's gain or spend in the user's default currency
	 *
	 * @param User $user
	 * @param bool $paid it true, will calculate user's spend, else calculate user's gain
	 * @param int $from unix time stamp value that indicats start of period
	 * @param int $to unix time stamp value that indicates end of period
	 * @param int $interval the interval in seconds
	 * @param int $limit the nunmber of items you need for chart
	 *
	 * @return array of arrays that each array is like this:
	 * 	array(
	 * 		[unix]: (int) that is unix timestamp
	 * 		[sum]: (double) the sum value of the period
	 * 	)
	 */
	public static function getStatsChartDataByUser(User $user, bool $paid, int $from, int $to, int $interval, int $limit) {
		$defaultCurrency = Currency::getDefault($user);
		$pays = self::getStatsByUserQuery($user, $paid, $from, $to);
		$pays->orderBy("time_unit", "DESC");
		$pays->groupBy("financial_transactions_pays.currency");
		$pays->groupBy("time_unit");
		$pays = $pays->arrayBuilder()->get($limit, array(
			"financial_transactions.paid_at DIV {$interval} as time_unit",
			"SUM(`financial_transactions_pays`.`price`) as `sum`",
			"financial_transactions_pays.currency",
		));

		foreach ($pays as &$pay) {
			$currency =  (new Currency)->byID($pay["currency"]);
			$pay["sum"] = $currency->changeTo(abs($pay["sum"]), $defaultCurrency);
			unset($pay["currency"]);
		}

		$last = intdiv($to, $interval);
		for ($x = 0; $x < $limit; $x++) {
			$timeUnit = $last - $x;
			if (
				!isset($pays[$x]) or
				(isset($pays[$x]) and $pays[$x]["time_unit"] != $timeUnit)
			) {
				$y = isset($pays[$x]) ? $x : $x - 1;
				array_splice($pays, $x, 0, array(
					array(
						"time_unit" => $timeUnit,
						"sum" => 0,
					),
				));
			}
			$pays[$x]["unix"] = $pays[$x]["time_unit"] * $interval;
			unset($pays[$x]["time_unit"]);
		}
		return $pays;
	}

	/**
	 * @param User $user
	 * @param bool $paid
	 * @param int $from
	 * @param int $to
	 *
	 * @return Pay
	 */
	protected static function getStatsByUserQuery(User $user, bool $paid = true, int $from = 0, int $to = 0): Pay {
		$pays = new Pay();
		$pays->join(Transaction::class, "transaction", "INNER");
		$pays->where("financial_transactions.user", $user->id);
		if ($paid) {
			$pays->where("financial_transactions_pays.method", [Pay::banktransfer, Pay::onlinepay], "IN");
			$pays->where("financial_transactions.price", 0, ">=");
		} else {
			$pays->where("financial_transactions.price", 0, "<");
		}
		if ($from) {
			$pays->where("financial_transactions.paid_at", $from, ">=");
		}
		if ($to) {
			$pays->where("financial_transactions.paid_at", $to, "<");
		}
		$pays->where("financial_transactions.status", Transaction::paid);
		return $pays;
	}
}
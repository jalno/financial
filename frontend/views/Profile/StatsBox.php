<?php
namespace themes\clipone\views\financial\Profile;

use packages\base\{json, Date};
use packages\userpanel\{User, Log};
use themes\clipone\views\Dashboard\Box;
use packages\financial\{logs, Authorization, Difficulty, Transaction, currency};

class StatsBox extends Box {

	/** @var User */
	protected $user;
	
	public function __construct(User $user, $name = "financial.StatsBox"){
		parent::__construct($name);
		$this->user = $user;
	}

	/**
	 * Get the value of user
	 * 
	 * @return User
	 */ 
	public function getUser(): User {
		return $this->user;
	}

	/**
	 * Set the value of user
	 *
	 * @param User $user
	 * @return void
	 */ 
	public function setUser(User $user): void {
		$this->user = $user;
	}


	public function getHTML() {
		$this->html = "";
		if (!$this->user->can("financial_transactions_list")) {
			return $this->html;
		}
		$defaultCurrency = Currency::getDefault($this->user);
		$queryBuilder = function (bool $paid = true, int $from = 0,int $to = 0) use ($defaultCurrency) {
			$transaction = new Transaction;
			$transaction->where("user", $this->user->id);
			if ($from > 0 and $to > 0) {
				$transaction->where("paid_at", $from, "<");
				$transaction->where("paid_at", $to, ">=");
			}
			if ($paid) {
				$transaction->where("price", 0, ">=");
			} else{
				$transaction->where("price", 0, "<");
			}
			$transaction->groupBy("currency");
			$transaction->ArrayBuilder();
			$transactions = $transaction->get(null, array("currency", "SUM(price) as `sum`"));

			$sum = 0;
			foreach ($transactions as $transaction) {
				$currency = new currency;
				$currency =  $currency->byId($transaction["currency"]);
				$sum += $currency->changeTo($transaction["sum"], $defaultCurrency);
			}
			return $sum;
		};
		$this->html .= '<div class="table-responsive table-responsive-posts-stats" data-user="' . $this->user->id . '">';
			$this->html .= '<table class="table table-bordered table-posts-stats">';
				$this->html .= '<thead>';
					$this->html .= '<tr>';
						$this->html .= '<th colspan="6" class="center">' . t("packages.financial.transaction") . '</th>';
					$this->html .= '</tr>';
					$this->html .= '<tr>';
						$this->html .= '<th class="center"></th>';
						$this->html .= '<th class="center">' . "1" . t("packages.financial.month") . "</th>";
						$this->html .= '<th class="center">' . "2" . t("packages.financial.month") . "</th>";
						$this->html .= '<th class="center">' . "3" . t("packages.financial.month") . "</th>";
						$this->html .= '<th class="center">' . "1" . t("packages.financial.year") . "</th>";
						$this->html .= '<th class="center">' . t("packages.financial.total") . "</th>";
					$this->html .= '</tr>';
				$this->html .= '</thead>';
				$this->html .= '<tbody>';
					$this->html .= "<tr>";
						$this->html .= '<td class="center">' . t("packages.financial.paid") .'</td>';
						$this->html .= '<td class="center">' . number_format($queryBuilder(true, Date::time(), Date::time() - 2592000)) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format($queryBuilder(true, Date::time(), Date::time() - 5184000)) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format($queryBuilder(true, Date::time(), Date::time() - 7776000)) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format($queryBuilder(true, Date::time(), Date::time() - 31104000)) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format($queryBuilder(true)) . " " . $defaultCurrency->title . '</td>';
					$this->html .= "</tr>";
					$this->html .= "<tr>";
						$this->html .= '<td class="center">' . t("packages.financial.recive") .'</td>';
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false, Date::time(), Date::time() - 2592000))) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false, Date::time(), Date::time() - 5184000))) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false, Date::time(), Date::time() - 7776000))) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false, Date::time(), Date::time() - 31104000))) . " " . $defaultCurrency->title . '</td>';
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false))) . " " . $defaultCurrency->title . '</td>';
					$this->html .= "</tr>";
				$this->html .= '</tbody>';
			$this->html .= '</table>';
		$this->html .= '</div>';
		return $this->html;
	}
}
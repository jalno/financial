<?php
namespace themes\clipone\views\financial\Profile;

use themes\clipone\views\Dashboard\Box;
use packages\base\{json, Date, db, Options};
use packages\userpanel\{User, Log, Authentication};
use packages\financial\{logs, Authorization, Difficulty, Transaction, currency, Transaction_pay as Pay};

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
			$pay = new Pay();
			$pay->join(Transaction::class, "transaction", "INNER");
			$pay->where("financial_transactions.user", $this->user->id);
			if ($paid) {
				$pay->where("financial_transactions_pays.method", [Pay::banktransfer, Pay::onlinepay], "IN");
				$pay->where("financial_transactions.price", 0, ">=");
			} else {
				$pay->where("financial_transactions.price", 0, "<");
			}
			if ($from > 0 and $to > 0) {
				$pay->where("financial_transactions.paid_at", $from, "<");
				$pay->where("financial_transactions.paid_at", $to, ">=");
			}
			$pay->where("financial_transactions.status", Transaction::paid);
			$pay->groupBy("financial_transactions_pays.currency");
			$pay->ArrayBuilder();
			$pays = $pay->get(null, array("financial_transactions_pays.currency", "SUM(`financial_transactions_pays`.`price`) as `sum`"));
			$sum = 0;
			foreach ($pays as $pay) {
				$currency =  (new Currency)->byId($pay["currency"]);
				$sum += $currency->changeTo(abs($pay["sum"]), $defaultCurrency);
			}
			return $sum;
		};
		$periods = Options::get("packages.financial.user_pay_stats_period");
		if (!$periods) {
			$periods = array();
		}
		$isme = $this->user->id == Authentication::getID();
		$this->html .= '<div class="table-responsive table-responsive-transactions">';
			$this->html .= '<table class="table table-bordered table-transactions">';
				$this->html .= '<thead>';
					$this->html .= '<tr>';
						$this->html .= '<th colspan="' . ($periods ? count($periods) + 2 : 2) . '" class="center"><i class="fa fa-line-chart"></i>' . t("packages.financial.transaction") . '<span class="text-danger">' . t("packages.financial.stats_curreny", ["currency" => $defaultCurrency->title]) . '</span></th>';
					$this->html .= '</tr>';
					$this->html .= '<tr>';
						$this->html .= '<th class="center"></th>';
					foreach ($periods as $period) {
						$days = $period / 86400;
						if ($days >= 365) {
							$this->html .= '<th class="center">' . t("packages.financial.last_year", ["year" => $days / 365]) . "</th>";
						} else if ($days >= 30) {
							$this->html .= '<th class="center">' . t("packages.financial.last_month", ["month" => $days / 30]) . "</th>";
						} else {
							$this->html .= '<th class="center">' . t("packages.financial.last_day", array("day" => $days)) . "</th>";
						}
					}
						$this->html .= '<th class="center">' . t("packages.financial.total") . "</th>";
					$this->html .= '</tr>';
				$this->html .= '</thead>';
				$this->html .= '<tbody>';
					$this->html .= "<tr>";
						$this->html .= '<td class="center"><i class="fa fa-' . ($isme ? "upload text-info" : "download text-success") . '"></i> ' . ($isme ? t("packages.financial.paid") : t("packages.financial.user_paids")) .'</td>';
					foreach ($periods as $period) {
						$this->html .= '<td class="center">' . number_format($queryBuilder(true, Date::time(), Date::time() - 2592000)) . '</td>';
					}
						$this->html .= '<td class="center">' . number_format($queryBuilder()) . '</td>';
					$this->html .= "</tr>";
				if (Authorization::is_accessed("transactions_refund_add")) {
					$this->html .= "<tr>";
						$this->html .= '<td class="center"><i class="fa fa-' . ($isme ? "download text-info" : "upload text-danger") . '"></i> ' . ($isme ? t("packages.financial.receive") : t("packages.financial.paid_touser")) .'</td>';
					foreach ($periods as $period) {
						$this->html .= '<td class="center">' . number_format($queryBuilder(false, Date::time(), Date::time() - 2592000)) . '</td>';
					}
						$this->html .= '<td class="center">' . number_format(abs($queryBuilder(false))) . '</td>';
					$this->html .= "</tr>";
				}
				$this->html .= '</tbody>';
			$this->html .= '</table>';
		$this->html .= '</div>';
		return $this->html;
	}
}
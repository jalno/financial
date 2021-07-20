<?php
namespace themes\clipone\views\financial\Profile;

use themes\clipone\views\Dashboard\Box;
use packages\base\{json, Date, db, Options};
use packages\userpanel\{User, Log, Authentication};
use packages\financial\{logs, Authorization, Difficulty, Transaction, currency, Transaction_pay as Pay, Stats};

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
		$periods = Options::get("packages.financial.user_pay_stats_period");
		$periods = [];
		for ($x = 1; $x <= 8; $x++) {
			$periods[] = 86400 * $x;
		}
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
							$this->html .= '<th class="center">' . t("packages.financial.last_year", ["year" => round($days / 365, 1)]) . "</th>";
						} else if ($days >= 30) {
							$this->html .= '<th class="center">' . t("packages.financial.last_month", ["month" => round($days / 30, 1)]) . "</th>";
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
						$this->html .= '<td class="center">' . number_format(Stats::getStatsSumByUser($this->user, true, Date::time() - $period, Date::time())) . '</td>';
					}
						$this->html .= '<td class="center">' . number_format(Stats::getStatsSumByUser($this->user, true, 0, 0)) . '</td>';
					$this->html .= "</tr>";
				if (Authorization::is_accessed("transactions_refund_add")) {
					$this->html .= "<tr>";
						$this->html .= '<td class="center"><i class="fa fa-' . ($isme ? "download text-info" : "upload text-danger") . '"></i> ' . ($isme ? t("packages.financial.receive") : t("packages.financial.paid_touser")) .'</td>';
					foreach ($periods as $period) {
						$this->html .= '<td class="center">' . number_format(Stats::getStatsSumByUser($this->user, false, Date::time() - $period, Date::time())) . '</td>';
					}
						$this->html .= '<td class="center">' . number_format(abs(Stats::getStatsSumByUser($this->user, false, 0, 0))) . '</td>';
					$this->html .= "</tr>";
				}
				$this->html .= '</tbody>';
			$this->html .= '</table>';
		$this->html .= '</div>';
		return $this->html;
	}
}
import Add from "./Transaction/Add";
import Addingcredit from "./Transaction/Addingcredit";
import Edit from "./Transaction/Edit";
import List from "./Transaction/List";
import payByCredit from "./Transaction/Pay/byCredit";
import OnlinePay from "./Transaction/Pay/OnlinePay";
import Redirect from "./Transaction/Pay/OnlinePay/Redirect";
import Gateway from "./Transaction/Settings/GateWays";
import View from "./Transaction/View";

export default class Transaction {
	public static initIfNeeded() {
		List.initIfNeeded();
		Add.initIfNeeded();
		Edit.initIfNeeded();
		Redirect.initIfNeeded();
		Addingcredit.initIfNeeded();
		Gateway.initIfNeeded();
		payByCredit.initIfNeeded();
		OnlinePay.initIfNeeded();
		View.initIfNeeded();
	}
}

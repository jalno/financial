import List from "./Transaction/List";
import Add from "./Transaction/Add";
import Edit from "./Transaction/Edit";
import Redirect from "./Transaction/Pay/OnlinePay/Redirect";
import OnlinePay from "./Transaction/Pay/OnlinePay";
import payByCredit from "./Transaction/Pay/byCredit";
import Gateway from "./Transaction/Settings/GateWays";
import Addingcredit from "./Transaction/Addingcredit";

export default class Transaction{
	public static initIfNeeded(){
		List.initIfNeeded();
		Add.initIfNeeded();
		Edit.initIfNeeded();
		Redirect.initIfNeeded();
		Addingcredit.initIfNeeded();
		Gateway.initIfNeeded();
		payByCredit.initIfNeeded();
		OnlinePay.initIfNeeded();
	}
	
}

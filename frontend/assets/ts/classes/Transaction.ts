import Add from "./Transaction/Add";
import Addingcredit from "./Transaction/Addingcredit";
import Edit from "./Transaction/Edit";
import List from "./Transaction/List";
import Pay from "./Transaction/Pay";
import Gateway from "./Transaction/Settings/GateWays";
import View from "./Transaction/View";

export default class Transaction {
	public static initIfNeeded() {
		List.initIfNeeded();
		Add.initIfNeeded();
		Edit.initIfNeeded();
		Addingcredit.initIfNeeded();
		Gateway.initIfNeeded();
		View.initIfNeeded();
		Pay.initIfNeeded();
	}
}

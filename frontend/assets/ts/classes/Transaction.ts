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
	public static formatNumber(number: number) {
		return number.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
	}
	public static deFormatNumber(str: string) {
		return str.replace(/\,/g, "");
	}
	public static formatFlotNumber(float: number) {
		const split = float.toString().split(".");
		const number = Transaction.formatNumber(parseInt(float.toString(), 10));
		const decimal = split.length > 1 ? parseInt(split[1], 10) : 0;
		return number + (decimal > 0 ? "." + decimal : "");
	}
}

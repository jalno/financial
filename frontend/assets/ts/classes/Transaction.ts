import List from "./Transaction/List";
import Add from "./Transaction/Add";
import Edit from "./Transaction/Edit";
export default class Transaction{
	public static initIfNeeded(){
		List.initIfNeeded();
		Add.initIfNeeded();
		Edit.initIfNeeded();
	}
	
}
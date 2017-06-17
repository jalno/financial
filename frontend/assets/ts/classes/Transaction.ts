import List from "./Transaction/List";
import Add from "./Transaction/Add";
export default class Transaction{
	public static initIfNeeded(){
		List.initIfNeeded();
		Add.initIfNeeded();
	}
	
}
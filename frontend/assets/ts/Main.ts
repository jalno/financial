import * as $ from "jquery";
import Transaction from "./classes/Transaction";
import Settings from "./classes/Settings";
$(function(){
	Transaction.initIfNeeded();
	Settings.initIfNeeded();
});
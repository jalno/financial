import * as $ from "jquery";
import Settings from "./classes/Settings";
import Transaction from "./classes/Transaction";
$(() => {
	Transaction.initIfNeeded();
	Settings.initIfNeeded();
});

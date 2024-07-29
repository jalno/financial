import Settings from "./classes/Settings";
import Transaction from "./classes/Transaction";
import UserpanelSettings from "./classes/Userpanel/Settings";
$(() => {
	Transaction.initIfNeeded();
	Settings.initIfNeeded();
	UserpanelSettings.initIfNeeded();
});

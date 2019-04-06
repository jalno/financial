import Banks from "./Settings/Banks";
import Currencies from "./Settings/Currencies";

export default class Settings {
	public static initIfNeeded() {
		Currencies.initIfNeeded();
		Banks.initIfNeeded();
	}
}

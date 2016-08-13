<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class tasker extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function event() {
		$cmd = taskerCmd::byId(init('id'));
		if (!is_object($cmd) || $cmd->getEqType() != 'tasker') {
			throw new Exception(__('Commande ID tasker inconnu, ou la commande n\'est pas de type tasker : ', __FILE__) . init('id'));
		}
		$cmd->event(init('value'));
	}

	public static function sceneParameters($_scene = '') {
		$return = array();
		foreach (ls(dirname(__FILE__) . '/../config/scene', '*') as $dir) {
			$path = dirname(__FILE__) . '/../config/scene/' . $dir;
			if (!is_dir($path)) {
				continue;
			}
			$files = ls($path, '*.json', false, array('files', 'quiet'));
			foreach ($files as $file) {
				try {
					$content = file_get_contents($path . '/' . $file);
					if (is_json($content)) {
						$return += json_decode($content, true);
					}
				} catch (Exception $e) {

				}
			}
		}
		if (isset($_scene) && $_scene != '') {
			if (isset($return[$_scene])) {
				return $return[$_scene];
			}
			return array();
		}
		return $return;
	}

	/*     * *********************Méthodes d'instance************************* */

	public function generateXml($_scene) {
		$config = self::sceneParameters($_scene);
		if (count($config) == 0) {
			throw new Exception(__('Impossible de trouver le fichier de config : ', __FILE__) . $_scene);
		}
		$replace = array(
			'#name#' => $this->getName(),
			'#eqLogic_id#' => $this->getId(),
			'#apikey#' => config::byKey('api'),
			'#network::external#' => network::getNetworkAccess('external'),
		);
		foreach ($config['commands'] as &$command) {
			$cmd = $this->getCmd(null, $command['logicalId']);
			if (!is_object($cmd)) {
				$cmd = new taskerCmd();
				$cmd->setEqLogic_id($this->getId());
			} else {
				$command['name'] = $cmd->getName();
				if (isset($command['display'])) {
					unset($command['display']);
				}
			}
			utils::a2o($cmd, $command);
			$cmd->save();
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		foreach ($config['configuration'] as $key => $parameter) {
			$default = '';
			if (isset($parameter['default'])) {
				$default = $parameter['default'];
			}
			$replace['#' . $key . '#'] = $this->getConfiguration('tasker::' . $key, $default);
		}

		$dir = dirname(__FILE__) . '/../../../../tmp/tasker';
		if (file_exists($dir)) {
			rrmdir($dir);
		}
		mkdir($dir);
		foreach ($config['profil'] as $profil) {
			$xml = str_replace(array_keys($replace), $replace, file_get_contents(dirname(__FILE__) . '/../config/scene/' . $profil));
			file_put_contents($dir . '/' . basename(dirname(__FILE__) . '/../config/scene/' . $profil), $xml);
		}

		return str_replace(array_keys($replace), $replace, $xml);
	}

	/*     * **********************Getteur Setteur*************************** */
}

class taskerCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = array()) {

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
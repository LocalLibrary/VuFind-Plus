<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'services/MyResearch/MyResearch.php';

class EContentHolds extends MyResearch {
	function launch(){
		global $configArray;
		global $interface;
		global $user;
		global $timer;

		// Get My Transactions
		if ($this->catalog->status) {
			if ($user->cat_username) {
				$patron = $this->catalog->patronLogin($user->cat_username, $user->cat_password);
				$timer->logTime("Logged in patron to get checked out items.");
				if (PEAR::isError($patron))
				PEAR::raiseError($patron);

				$patronResult = $this->catalog->getMyProfile($patron);
				if (!PEAR::isError($patronResult)) {
					$interface->assign('profile', $patronResult);
				}
				$timer->logTime("Got patron profile to get checked out items.");

				// Define sorting options
				$sortOptions = array('title'   => 'Title',
                             'author'  => 'Author',
                             'dueDate' => 'Due Date',
				                     'format'  => 'Format',
				                    );
				$interface->assign('sortOptions', $sortOptions);
				$selectedSortOption = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'dueDate';
				$interface->assign('defaultSortOption', $selectedSortOption);

				require_once 'Drivers/EContentDriver.php';
				$driver = new EContentDriver();

				if (isset($_REQUEST['multiAction']) && $_REQUEST['multiAction'] == 'suspendSelected'){
					$ids = array();
					foreach ($_REQUEST['unavailableHold'] as $id => $selected){
						$ids[] = $id;
					}

					$suspendDate = $_REQUEST['suspendDate'];
					$dateToReactivate = strtotime($suspendDate);
					$suspendResult = $driver->suspendHolds($ids, $dateToReactivate);

					//Redirect back to the MyEContent page
					header("Location: " . $configArray['Site']['path'] . "/MyResearch/MyEContent");
				}
				$result = $driver->getMyHolds($user);
				$interface->assign('holds', $result['holds']);
				$timer->logTime("Loaded econtent from catalog.");

			}
		}

		$hasSeparateTemplates = $interface->template_exists('MyResearch/eContentAvailableHolds.tpl');
		if ($hasSeparateTemplates){
			$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : 'available';
			if ($section == 'available'){
				$interface->setPageTitle('Available eContent');
				$interface->setTemplate('eContentAvailableHolds.tpl');
			}else{
				$interface->setPageTitle('eContent On Hold');
				$interface->setTemplate('eContentUnavailableHolds.tpl');
			}
		}else{
			$interface->setTemplate('eContentHolds.tpl');
			$interface->setPageTitle('On Hold eContent');
		}

		$interface->display('layout.tpl');
	}

}
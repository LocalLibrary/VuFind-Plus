<?php
require_once 'sys/analytics/Analytics_Session.php';
require_once 'sys/analytics/Analytics_Event.php';
require_once 'sys/analytics/Analytics_Search.php';
require_once 'sys/analytics/Analytics_PageView.php';
require_once 'sys/BotChecker.php';

class Analytics
{
	private $session;
	private $pageView;
	private $events = array();
	private $search;
	private $finished = false;
	private $trackingDisabled = false;

	function __construct($ipAddress, $startTime){
		global $configArray;
		global $logger;
		if (!isset($configArray)){
			die("You must load configuration before creating a tracker");
		}
		global $interface;
		global $user;
		if (!isset($interface)){
			die("You must setup the interface before creating a tracker");
		}

		//Make sure that we don't track visits from bots
		if (BotChecker::isRequestFromBot() == true){
			//$logger->log("Disabling logging because the request is from a bot", PEAR_LOG_DEBUG);
			$this->trackingDisabled = true;
			$this->finished = true;
		}

		//disable error handler since the tables may not be installed yet.
		disableErrorHandler();
		$sessionId = session_id();
		$session = new Analytics_Session();
		$session->session_id = $sessionId;
		if ($session->find(true)){
			$this->session = $session;
			if ($this->session->ip != $ipAddress){
				$this->session->ip = $ipAddress;
				$this->doGeoIP();
			}
		}else{
			$this->session = $session;
			$this->session->sessionStartTime = $startTime;
			$this->session->lastRequestTime = $startTime;
			$this->session->ip = $ipAddress;

			$this->doGeoIP();

			$this->session->insert();
		}

		$this->pageView = new Analytics_PageView();
		$this->pageView->sessionId = $this->session->id;
		$this->pageView->pageStartTime = $startTime;
		$this->pageView->fullUrl = $_SERVER['REQUEST_URI'];
		enableErrorHandler();
	}

	function disableTracking(){
		$this->trackingDisabled = true;
	}

	function setModule($module){
		$this->pageView->module = $module;
	}

	function setAction($action){
		$this->pageView->action = $action;
	}

	function setObjectId($objectId){
		$this->pageView->objectId = $objectId;
	}

	function setMethod($method){
		$this->pageView->method = $method;
	}

	function setLanguage($language){
		$this->pageView->language = $language;
	}

	function setTheme($language){
		$this->session->theme = $language;
	}

	function setMobile($mobile){
		$this->session->mobile = $mobile;
	}

	function setDevice($device){
		$this->session->device = $device;
	}

	function setPhysicalLocation($physicalLocation){
		$this->session->physicalLocation = $physicalLocation;
	}

	function setPatronType($patronType){
		$this->session->patronType = $patronType;
	}

	function setHomeLocationId($homeLocationId){
		$this->session->homeLocationId = $homeLocationId;
	}

	function doGeoIP(){
		global $configArray;
		//Load GeoIP data
		require_once 'sys/MaxMindGeoIP/geoip.inc';
		require_once 'sys/MaxMindGeoIP/geoipcity.inc';
		$geoIP = geoip_open($configArray['Site']['local'] . '/../../sites/default/GeoIPCity.dat', GEOIP_MEMORY_CACHE);
		$geoRecord = GeoIP_record_by_addr($geoIP, $this->session->ip);
		if ($geoRecord){
			$this->session->country = $geoRecord->country_code;
			$this->session->states = $geoRecord->region;
			$this->session->city = $geoRecord->city;
			$this->session->latitude = $geoRecord->latitude;
			$this->session->longitude = $geoRecord->longitude;
		}
		geoip_close($geoIP);
	}

	function addEvent($category, $action, $data1 = '', $data2 = '', $data3 = ''){
		$event = new Analytics_Event();
		$event->sessionId = $this->session->id;
		$event->category = $category;
		$event->action = $action;
		$event->data = substr($data1, 0, 256);
		$event->data2 = substr($data2, 0, 256);
		$event->data3 = substr($data3, 0, 256);
		$event->eventTime = time();
		$this->events[] = $event;
	}

	function addSearch($scope, $lookfor, $isAdvanced, $searchType, $facetsApplied, $numResults){
		$this->search = new Analytics_Search();
		$this->search->sessionId = $this->session->id;
		$this->search->scope = $scope;
		$this->search->lookfor = $lookfor;
		$this->search->isAdvanced = $isAdvanced;
		$this->search->searchType = $searchType;
		$this->search->facetsApplied = $facetsApplied;
		$this->search->searchTime = time();
		$this->search->numResults = $numResults;
	}

	function __destruct(){
		$this->finish();
	}

	function finish(){
		if ($this->finished){
			global $logger;
			//$logger->log("Not logging analytics because tracking is already finished", PEAR_LOG_DEBUG);
			return;
		}
		$this->finished = true;
		global $configArray;
		if (!isset($configArray['System']['enableAnalytics']) || $configArray['System']['enableAnalytics'] == false){
			return;
		}

		//disableErrorHandler();
		if (!$this->trackingDisabled){
			//Save or update the session
			$this->session->lastRequestTime = time();
			$this->session->update();
			//Save the page view
			$this->pageView->pageEndTime = time();
			$this->pageView->insert();
			//Save searches
			if ($this->search){
				$this->search->insert();
			}
		}
		//Save events
		foreach ($this->events as $event){
			$event->insert();
		}

		//enableErrorHandler();
	}

	function getSessionFilters(){
		$session = null;
		if (isset($_REQUEST['filter'])){
			$filterFields = $_REQUEST['filter'];
			$filterValues = $_REQUEST['filterValue'];
			foreach($filterFields as $index => $fieldName){
				if (isset($filterValues[$index])){
					$value = $filterValues[$index];
					if (in_array($fieldName, array('country', 'city', 'state', 'theme', 'mobile', 'device', 'physicalLocation', 'patronType', 'homeLocationId'))){
						if ($session == null){
							$session = new Analytics_Session();
						}

						$session->$fieldName = $value;
					}
				}
			}
		}
		return $session;
	}

	function getSessionFilterString(){
		$filterParams = "";
		if (isset($_REQUEST['filter'])){
			foreach ($_REQUEST['filter'] as $index => $filterName){
				if (isset($_REQUEST['filterValue'][$index])){
					if (strlen($filterParams) > 0){
						$filterParams .= "&";
					}
					$filterVal = $_REQUEST['filterValue'][$index];
					$filterParams .= "filter[$index]={$filterName}";
					$filterParams .= "&filterValue[$index]={$filterVal}";
				}
			}
		}
		return $filterParams;
	}

	function getSessionFilterSQL(){
		$sessionFilterSQL = null;
		if (isset($_REQUEST['filter'])){
			$filterFields = $_REQUEST['filter'];
			$filterValues = $_REQUEST['filterValue'];
			foreach($filterFields as $index => $fieldName){
				if (isset($filterValues[$index])){
					$value = $filterValues[$index];
					if (in_array($fieldName, array('country', 'city', 'state', 'theme', 'mobile', 'device', 'physicalLocation', 'patronType', 'homeLocationId'))){
						if ($sessionFilterSQL != null){
							$sessionFilterSQL .= " AND ";
						}
						$sessionFilterSQL .= "$fieldName = '" . mysql_escape_string($value) . "'";
					}
				}
			}
		}
		return $sessionFilterSQL;
	}

	function getReportData($source, $forGraph = false){
		$data = array();
		if ($source == 'searchesByType'){
			$data['name'] = "Searches By Type";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Search Type', 'Times Used', 'Percent Usage');
			$data['data'] = $this->getSearchesByType($forGraph);
		}elseif ($source == 'searchesByScope'){
			$data['name'] = "Searches By Scope";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Scope', 'Number of Searches', 'Percent Usage');
			$data['data'] = $this->getSearchesByScope($forGraph);
		}elseif ($source == 'searchesWithFacets'){
			$data['name'] = "Searches with Facets";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Searches', 'Percent Usage');
			$data['data'] = $this->getSearchesWithFacets($forGraph);
		}elseif ($source == 'facetUsageByType'){
			$data['name'] = "Facet Usage";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Facet', 'Number of Searches', 'Percent Usage');
			$data['data'] = $this->getFacetUsageByType($forGraph);
		}elseif ($source == 'topSearches'){
			$data['name'] = "Top Searches";
			$data['parentLink'] = '/Report/Searches';
			$data['parentName'] = 'Searches';
			$data['columns'] = array('Search Term', 'Number of Searches');
			$data['paginate'] = true;
			$data['data'] = $this->getTopSearches($forGraph);

		}elseif ($source == 'pageViewsByModule'){
			$data['name'] = "Page Views By Module";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Module', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByModule($forGraph);
		}elseif ($source == 'pageViewsByModuleAction'){
			$data['name'] = "Page Views By Module and Action";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Module', 'Action', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByModuleAction($forGraph);
		}elseif ($source == 'pageViewsByTheme'){
			$data['name'] = "Page Views By Theme";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Theme', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByTheme($forGraph);
		}elseif ($source == 'pageViewsByDevice'){
			$data['name'] = "Page Views By Device";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Device', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByDevice($forGraph);
		}elseif ($source == 'pageViewsByHomeLocation'){
			$data['name'] = "Page Views By Home Location";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Patron Home Library', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByHomeLocation($forGraph);
		}elseif ($source == 'pageViewsByPhysicalLocation'){
			$data['name'] = "Page Views By Home Location";
			$data['parentLink'] = '/Report/PageViews';
			$data['parentName'] = 'Page Views';
			$data['columns'] = array('Physical Location', 'Number of Page Views');
			$data['data'] = $this->getPageViewsByPhysicalLocation($forGraph);

		}elseif ($source == 'holdsByResult'){
			$data['name'] = "Holds By Result";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('Hold Result', 'Times', '% Result');
			$data['data'] = $this->getHoldsByResult($forGraph);
		}elseif ($source == 'holdsPerSession'){
			$data['name'] = "Holds Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Placed', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsPerSession($forGraph);
		}elseif ($source == 'holdsCancelledPerSession'){
			$data['name'] = "Holds Cancelled Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Cancelled', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}elseif ($source == 'holdsUpdatedPerSession'){
			$data['name'] = "Holds Updated Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Updated', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}elseif ($source == 'holdsFailedPerSession'){
			$data['name'] = "Holds Failed Per Session";
			$data['parentLink'] = '/Report/ILSIntegration';
			$data['parentName'] = 'ILS Integration';
			$data['columns'] = array('# Holds Failed', 'Number of Sessions', '% Result');
			$data['data'] = $this->getHoldsCancelledPerSession($forGraph);
		}
		return $data;
	}

	function getSearchesByType($forGraph){
		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$searches->selectAdd('searchType');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->groupBy('searchType');
		$searches->orderBy('numSearches  DESC');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->searchType] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesByType = array();
		$numSearchesReported = 0;
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$numSearchesReported += $searchCount;
			if ($forGraph){
				$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}else{
				$searchesInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}
			if ($forGraph && count($searchesInfo) >= 5){
				break;
			}
		}
		if ($forGraph){
			$searchesInfo[] = array('Other', (float)sprintf('%01.2f', (($totalSearches - $numSearchesReported) / $totalSearches) * 100));
		}
		return $searchesInfo;
	}

	function getSearchesByScope($forGraph){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$searches->selectAdd('scope');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->groupBy('scope');
		$searches->orderBy('numSearches  DESC');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->scope] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesInfo = array();
		$numSearchesReported = 0;
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$numSearchesReported += $searchCount;
			if ($forGraph){
				$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}else{
				$searchesInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
			}
			if ($forGraph && count($searchesInfo) >= 5){
				break;
			}
		}
		if ($forGraph){
			$searchesInfo[] = array('Other', (float)sprintf('%01.2f', (($totalSearches - $numSearchesReported) / $totalSearches) * 100));
		}

		return $searchesInfo;
	}

	function getSearchesWithFacets($forGraph){
		//load searches by type
		$searches = new Analytics_Search();
		$searches->selectAdd('count(analytics_search.id) as numSearches');
		$session = $this->getSessionFilters();
		if ($session != null){
			$searches->joinAdd($session);
		}
		$searches->groupBy('facetsApplied');
		$searches->find();
		$totalSearches = 0;
		$searchByTypeRaw = array();
		while ($searches->fetch()){
			$searchByTypeRaw[$searches->facetsApplied == 0 ? 'No Facets' : 'Facets Applied'] = $searches->numSearches;
			$totalSearches += $searches->numSearches;
		}
		$searchesInfo = array();
		foreach ($searchByTypeRaw as $searchName => $searchCount){
			$searchesInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalSearches) * 100));
		}

		return $searchesInfo;
	}

	function getFacetUsageByType($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'Apply Facet';
		$events->selectAdd('action');
		$session = $this->getSessionFilters();
		if ($session != null){
			$events->joinAdd($session);
		}
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsByFacetTypeRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsByFacetTypeRaw[$events->action] = (int)$events->numEvents;
			$totalEvents += $events->numEvents;
		}
		$numReported = 0;
		foreach ($eventsByFacetTypeRaw as $searchName => $searchCount){
			if ($forGraph && (float)($searchCount / $totalEvents) < .02){
				break;
			}
			$numReported += $searchCount;
			if ($forGraph){
				$eventInfo[] = array($searchName, (float)sprintf('%01.2f', ($searchCount / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($searchName, $searchCount, (float)sprintf('%01.2f', ($searchCount / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getTopSearches($forGraph){
		$search = new Analytics_Search();
		$search->selectAdd();
		$search->selectAdd("count(analytics_search.id) as timesSearched");
		$search->selectAdd("lookfor");
		$session = $this->getSessionFilters();
		if ($session != null){
			$search->joinAdd($session);
		}
		$search->whereAdd("numResults > 0");
		$search->groupBy('lookfor');
		$search->orderBy('timesSearched DESC');
		if ($forGraph){
			$search->limit(0, 20);
		}else{

			$search->limit(0, 50);
		}
		$search->find();
		$topSearches = array();
		while ($search->fetch()){
			$searchTerm = "";
			if (!is_null($search->lookfor) && strlen(trim($search->lookfor)) > 0){
				$searchTerm = $search->lookfor;
			}else{
				$searchTerm = "&lt;blank&gt;";
			}
			if ($forGraph){
				$topSearches[] = "$searchTerm ({$search->timesSearched})";
			}else{
				$topSearches[] = array($searchTerm, $search->timesSearched);
			}
		}
		return $topSearches;
	}

	function getPageViewsByDevice($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('device');
		$pageViews->groupBy('device');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->device, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByHomeLocation($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$location = new Location();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$session->joinAdd($location);
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('displayName');
		$pageViews->groupBy('displayName');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->displayName, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByPhysicalLocation($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('physicalLocation');
		$pageViews->groupBy('physicalLocation');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 5);
		}
		$pageViews->find();
		$pageViewsByDeviceRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByDeviceRaw[] = array ($pageViews->physicalLocation, (int)$pageViews->numViews);
		}

		return $pageViewsByDeviceRaw;
	}

	function getPageViewsByTheme($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();

		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$session = $this->getSessionFilters();
		if ($session == null){
			$session = new Analytics_Session();
		}
		$pageViews->joinAdd($session);
		$pageViews->selectAdd('theme');
		$pageViews->groupBy('theme');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByThemeRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByThemeRaw[] = array ($pageViews->theme, (int)$pageViews->numViews);
		}

		return $pageViewsByThemeRaw;
	}

	function getPageViewsByModule($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->selectAdd('module');
		$session = $this->getSessionFilters();
		if ($session != null){
			$pageViews->joinAdd($session);
		}
		$pageViews->groupBy('module');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByModuleRaw = array();
		while ($pageViews->fetch()){
			$pageViewsByModuleRaw[] = array ($pageViews->module, (int)$pageViews->numViews);
		}

		return $pageViewsByModuleRaw;
	}

	function getPageViewsByModuleAction($forGraph){
		//load searches by type
		$pageViews = new Analytics_PageView();
		$pageViews->selectAdd('count(analytics_page_view.id) as numViews');
		$pageViews->selectAdd('module');
		$pageViews->selectAdd('action');
		$session = $this->getSessionFilters();
		if ($session != null){
			$pageViews->joinAdd($session);
		}
		$pageViews->groupBy('module');
		$pageViews->groupBy('action');
		$pageViews->orderBy('numViews DESC');
		if ($forGraph){
			$pageViews->limit(0, 10);
		}
		$pageViews->find();
		$pageViewsByModuleRaw = array();
		while ($pageViews->fetch()){
			if ($forGraph){
				$pageViewsByModuleRaw[] = array ($pageViews->module . ' - ' . $pageViews->action, (int)$pageViews->numViews);
			}else{$pageViewsByModuleRaw[] = array ($pageViews->module, $pageViews->action, (int)$pageViews->numViews);

			}
		}

		return $pageViewsByModuleRaw;
	}

	function getHoldsByResult($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		$events->selectAdd('data');
		$events->selectAdd('count(analytics_event.id) as numEvents');
		$events->category = 'ILS Integration';
		$events->whereAdd("action in ('Failed Hold', 'Successful Hold')");
		$session = $this->getSessionFilters();
		if ($session != null){
			$events->joinAdd($session);
		}
		$events->groupBy('action');
		$events->orderBy('numEvents DESC');
		$events->find();
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->action] = (int)$events->numEvents;
			$totalEvents += $events->numEvents;
		}
		$numReported = 0;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $totalEvents) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($totalEvents - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getHoldsPerSession($forGraph){
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		//load searches by type
		$events = new Analytics_Event();

		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(id) as numHolds, sessionId FROM analytics_event WHERE action ='Successful Hold' GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;

		$totalEvents = $numTotalSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $totalEvents) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $totalEvents) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($totalEvents - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($totalEvents - $numReported) / $totalEvents) * 100));
		}

		return $eventInfo;
	}

	function getHoldsCancelledPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(id) as numHolds, sessionId FROM analytics_event WHERE action ='Hold Cancelled' GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}

	function getHoldsUpdatedPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(id) as numHolds, sessionId FROM analytics_event WHERE action ='Hold Updated' GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}

	function getHoldsFailedPerSession($forGraph){
		//load searches by type
		$events = new Analytics_Event();

		//Get total sessions from the databse
		$totalSessions = new Analytics_Session();
		$numTotalSessions = $totalSessions->count('id');

		$events->query("SELECT numHolds, count(sessionId) as numSessions from (SELECT count(id) as numHolds, sessionId FROM analytics_event WHERE action ='Failed Hold' GROUP BY sessionId) as holdData GROUP BY numHolds ORDER BY numHolds");
		$eventsInfoRaw = array();
		$totalEvents = 0;
		while ($events->fetch()){
			$eventsInfoRaw[$events->numHolds . ' Holds'] = (int)$events->numSessions;
			$totalEvents += $events->numSessions;
		}
		$numZeroSessions = $numTotalSessions - $totalEvents;
		if ($forGraph){
			$eventInfo[] = array('0 Holds', (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}else{
			$eventInfo[] = array('0 Holds', $numZeroSessions, (float)sprintf('%01.2f', ($numZeroSessions / $numTotalSessions) * 100));
		}
		$numReported = $numZeroSessions;
		foreach ($eventsInfoRaw as $name => $count){
			if ($forGraph && (float)($count / $numTotalSessions) < .02){
				break;
			}
			$numReported += $count;
			if ($forGraph){
				$eventInfo[] = array($name, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}else{
				$eventInfo[] = array($name, $count, (float)sprintf('%01.2f', ($count / $numTotalSessions) * 100));
			}
			if ($forGraph && count($eventInfo) >= 10){
				break;
			}
		}
		if ($forGraph && ($numTotalSessions - $numReported > 0)){
			$eventInfo[] = array('Other', (float)sprintf('%01.2f', (($numTotalSessions - $numReported) / $numTotalSessions) * 100));
		}

		return $eventInfo;
	}
}
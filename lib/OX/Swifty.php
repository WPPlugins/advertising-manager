<?php
require_once(OX_LIB . '/Ad.php');
require_once(OX_LIB . '/Dal.php');
require_once(OX_LIB . '/Html.php');

class OX_Swifty
{
	var $dal;
	var $ad_networks;
	var $counter;
	var $actions;
	
	function OX_Swifty($dalClass = null)
	{
		// Functions here are initialisation only - plugins have not been loaded (so we cannot initialise data)
		$this->counter = array();
		$this->ad_networks = array();
		$this->actions = array();
		
		// Load all Swifty plugins
		OX_Tools::load_plugins(OX_LIB . '/Plugins', $this);
		
		// Load the data access layer
		$this->dal = is_null($dalClass) ? new OX_Dal() : new $dalClass();
	}
	
	function addAction($key, $value)
	{
		$actions = !empty($this->actions[$key]) ? $this->actions[$key] : array();
		$actions[] = $value;
		
		$this->actions[$key] = $actions;
	}
	
	function getAction($key)
	{
		return $this->actions[$key];
	}
	
	function factory($class)
	{
		return $this->dal->factory($class);
	}
	
	function getSetting($key)
	{
		return $this->dal->select_setting($key);
	}
	
	function setSetting($key, $value)
	{
		return $this->dal->update_setting($key, $value);
	}
	
	function setStats($stats)
	{
		return $this->dal->update_stats($stats);
	}

    /*
     * The goal of this method is to give back stats for a date range:
     * - by entity
     * - # imps
     * - # clicks
     * - # views
     * - # vs. prev. imps
     * - # vs. prev. clicks
     * - # vs. prev. views
     * returns an array of this structure:
     * [ad_id][i] = number of impressions
     * [ad_id][i-] = number of impressions from the immediately previous segment
     */
	function getStats($date_range = null, $entity_breakdown = 'ad', $date_breakdown = 'hour', $previous = false)
    {
        // If no date range specified, then get it for today
        if (!$date_range) {
            $today = date('Y-m-d');
            $date_range = array(
                'begin' => $today,
                'end' => $today
            );
        }

        // For now, entity breakdown is always 'ads'
        $entity_breakdown = 'ads';

        // For now, date breakdown is always by day
        // $date_breakdown = 'd';

        // If previous timeperiod, find the range of days immediately preceding the date range
        if ($previous) {

        }
        // Get the begin and end dates.  These dates are passed in as 'Y-m-d'.
        $begin = strtotime($date_range['begin']);
        $end = strtotime($date_range['end']) + 86400;  // begin/end are inclusive, so add a day to the end

        // If you want the previous time period, then some math is needed to get the start and end dates of the previous timeperiod
        if ($previous) {
            $diff = ($end - $begin);
            $begin -= $diff;
            $end -= $diff;
        }

        // Get the raw statistics
        $stats = $this->dal->select_stats();

        // Sum up stats for this date range
        $sum_stats = array();
        foreach ($stats['d'] as $dtstr => $stat) {
            $dt = strtotime($dtstr);
            if ($dt >= $begin && $dt < $end) {
                // Get the entity breakdown
                $s = $stat[$entity_breakdown];
                // Loop through each of the entities and sum up
                foreach ($s as $id => $data) {
                    if (!isset($sum_stats[$id])) {
                        $sum_stats[$id] = $data;
                    } else {
                        foreach($data as $v => $n) {
                            // Either create a new node or increment the node
                            $sum_stats[$id][$v] = (!isset($sum_stats[$id][$v])) ? $n : $sum_stats[$id][$v] + $n;
                        }
                        $sum_stats[$id]['i'] += $data['i'];
                    }
                    if ($entity_breakdown == 'ads') {
                        $ad = $this->getAd($id);
                        if ($ad) {
                            $sum_stats[$id]['name'] = $ad->name;
                        }
                    }
                }
            }
        }

        return $sum_stats;
    }

	function incrementStats($ad, $type = 'i')
	{
		$date = date("Y-m-d");
        $hour = date('H');
		$adId = $ad->id;

		if ($this->getSetting('stats')) {

            $stats = $this->dal->select_stats();

            // Increment date stats
			if (empty($stats['d'][$date]['ads'][$adId][$type])) {
                $stats['d'][$date]['ads'][$adId][$type] = 0;
			}
            $stats['d'][$date]['ads'][$adId][$type]++;

            // Increment hour stats
            if (empty($stats['h'][$date][$hour]['ads'][$adId][$type])) {
                $stats['h'][$date][$hour]['ads'][$adId][$type] = 0;
            }
            $stats['h'][$date][$hour]['ads'][$adId][$type]++;

			$this->setStats($stats);
		}
	}
	
	function insertAd(&$ad)
	{
		$ad->add_revision();
		return $this->dal->insert_ad($ad);
	}
	
	function deleteAd($id)
	{
		return $this->dal->delete_ad($id);
	}
	
	function getAds()
	{
		return $this->dal->select_ads();
	}
	
	function getAd($id)
	{
		return $this->dal->select_ad($id);
	}
	
	function setAd(&$ad)
	{
		$ad->add_revision();
		return $this->dal->update_ad($ad);
	}
	
	function copyAd($id)
	{
		$ad = $this->dal->select_ad($id);
		if ($ad) {
            // Not sure why, but we will manually clone an object here
            $new = unserialize(serialize($ad));
            $new->add_revision();
			return $this->dal->insert_ad($new);
		}
		
		return false;
	}
	
	function setAdNetwork(&$ad)
	{
		$ad->add_revision(true);
		return $this->dal->update_ad_network($ad);
	}
	
	function importAdTag($tag)
	{
		global $advman_engine;
		
		$imported = false;
		if (!empty($tag)) {
			$networks = $this->getAction('ad_network');
			foreach ($networks as $network) {
				if (call_user_func(array($network, 'import_detect_network'), $tag)) {
					$ad = $advman_engine->factory($network);
					if ($ad) {
						$ad->import_settings($tag);
						$imported = true;
						break; //leave the foreach loop
					}
				}
			}
		}
		
		// Not a pre-defined network - we will make it HTML code...
		if (!$imported) {
			$ad = $advman_engine->factory('OX_Ad_Html');
			$ad->import_settings($tag);
		}
		
		$ad = $this->insertAd($ad);
		// Add the ad network defaults if they are not set yet
		if (empty($ad->np)) {
			$this->setAdNetwork($ad);
		}
		
		return $ad;
	}
	
	function setAdActive($id, $active)
	{
		$ad = $this->dal->select_ad($id);
		if ($active != $ad->active) {
			$ad->active = $active;
			return $this->setAd($ad);
		}
		
		return false;
	}
		
	function selectAd($name = null)
	{
		global $advman_engine;
		
		if (empty($name)) {
			$name = $this->getSetting('default-ad');
		}
		if (!empty($name)) {
			// Find the ads which match the name
			$ads = $advman_engine->getAds();

			$totalWeight = 0;
			$validAds = array();
			foreach ($ads as $id => $ad) {
				if ( ($ad->name == $name) && ($ad->is_available()) ) {
					$weight = $ad->get('weight');
					if ($weight > 0) {
						$validAds[] = $ad;
						$totalWeight += $weight;
					}
				}
			}

			// Pick the ad
			// Generate a number between 0 and 1
			$rnd = (mt_rand(0, PHP_INT_MAX) / PHP_INT_MAX);
			// Loop through ads until the selected one is chosen
			$wt = 0;
			foreach ($validAds as $ad) {
				$wt += $ad->get('weight');
				if ( ($wt / $totalWeight) > $rnd) {
					// Update the counters for this ad
					$this->update_counters($ad);
					// Display the ad
					return $ad;
				}
			}
		}
	}
	
	function update_counters($ad)
	{
		if (!empty($ad)) {
			if (empty($this->counter['id'][$ad->id])) {
				$this->counter['id'][$ad->id] = 1;
			} else {
				$this->counter['id'][$ad->id]++;
			}
			
			if (empty($this->counter['network'][strtolower(get_class($ad))])) {
				$this->counter['network'][strtolower(get_class($ad))] = 1;
			} else {
				$this->counter['network'][strtolower(get_class($ad))]++;
			}
		}
	}
}
?>
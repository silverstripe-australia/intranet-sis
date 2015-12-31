<?php

/**
 * 	An extension on viewing dashlet to allow display of page based announcements and RSS feeds.
 * 	@author Nathan Glasl <nathan@silverstripe.com.au>
 */
class AnnouncementDashlet extends Dashlet
{

    public static $title = "Announcements";
    public static $cmsTitle = "Announcements";
    public static $description = "View of Announcements and RSS Feeds";
    private $default_feeds = array();
    private static $db = array(
        'ShowAnnouncements' => 'Boolean',
        'RssHeader' => 'Varchar(255)',
        'RSSFeeds' => 'MultiValueField',
    );
    private static $defaults = array(
        'ShowAnnouncements' => 1,
        'RssHeader' => 'From around the place...',
        'Width' => 6
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->push(CheckboxField::create('ShowAnnouncements', 'Show general announcements'));
        $fields->push(MultiValueTextField::create('RSSFeeds', 'RSS Feeds'));
        return $fields;
    }

    public function getDashletFields()
    {
        $fields = parent::getDashletFields();
        $fields->push(CheckboxField::create('ShowAnnouncements', 'Show general announcements'));
        $fields->push(MultiValueTextField::create('RSSFeeds', 'RSS Feeds'));
        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $defaults = $this->config()->default_feeds;
        if (!count($this->RSSFeeds->getValues())) {
            $this->RSSFeeds = $defaults;
        }
    }
    
    public function canCreate($member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }
        return $member->ID > 0;
    }
}

class AnnouncementDashlet_Controller extends Dashlet_Controller
{

    private static $allowed_actions = array(
        'rssfeed'
    );
    
    public function init()
    {
        parent::init();
        Requirements::javascript(BA_SIS_COMMUNITY_PATH . '/javascript/dashlet-announcements.js');
    }
    
    public function _getAnnouncement($render = true)
    {
        if (ClassInfo::hasTable('MediaHolder')) {
            $media = MediaHolder::get()->filter(array('MediaType.Title' => 'News'));
            $possible = $media->first();

            // what about an announcement title'd page
            $page = $media->filter(array('Title' => 'Announcements'))->first();

            if (!$page) {
                $page = $possible;
            }

            if ($page) {
                $announcement = MediaPage::get()->filter('ParentID', $page->ID)->sort('Date DESC')->first();
                if ($announcement) {
                    if (!$render) {
                        return $announcement;
                    } else {
                        return ModelAsController::controller_for($announcement)->index();
                    }
                }
            }
        }
    }
    
    public function rssfeed()
    {
        return $this->renderWith('AnnouncementRss');
    }

    public function Announcement($render = false)
    {
        $render = (bool)$render;
        return $this->_getAnnouncement($render);
    }
    
    public function _getRSS()
    {
        $allItems = ArrayList::create();
        $feeds = $this->widget->RSSFeeds->getValues();
        if ($feeds && count($feeds)) {
            foreach ($feeds as $feedUrl) {
                $feed = new RestfulService($feedUrl, 1800);
                $request = $feed->request();

                // Make sure the request ended up being a success.

                if (substr($request->getStatusCode(), 0, 1) == '2') {
                    $XML = $request->simpleXML($request->getBody());
                    $objects = $this->recursiveXML($XML);

                    $output = null;
                    // Make sure the XML is valid RSS.
                    if (isset($objects['channel']['item'])) {
                        $output = $objects['channel']['item'];
                    } elseif (isset($objects['entry'])) {
                        $output = $objects['entry'];
                    }

                    if ($output) {

                        // Transform the XML into a structure that templating can parse.

                        $output = ArrayList::create($output);
                        foreach ($output as $child) {
                            // the foreach triggers the conversion to ArrayData - we're going to grab them 
                            // into the main array list now

                            // and add a Time field for sorting later
                            $date = $child->pubDate ? $child->pubDate : $child->updated;
                            $child->pubTime = strtotime($date);
                            $child->ItemDate = SS_Datetime::create_field('SS_Datetime', $child->pubTime);
                            
                            $link = $child->link;
                            if ($link instanceof ArrayData) {
                                $link = $link->toMap();
                                $child->link = $link['@attributes']['href'];
                            }
                            $allItems->push($child);
                        }
                    }
                }
            }
        }
        
        return $allItems->sort('pubTime', 'DESC');
    }

    /**
     * 	Recursively construct an object array from the given XML.
     * 	@author Nathan Glasl <nathan@silverstripe.com.au>
     */
    private function recursiveXML($XML, $objects = array())
    {
        foreach ((array) $XML as $attribute => $value) {
            $objects[$attribute] = (is_object($value) || is_array($value)) ? $this->recursiveXML($value) : $value;
        }
        return $objects;
    }
}

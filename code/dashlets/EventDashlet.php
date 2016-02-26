<?php

/**
 *	A dashlet to list upcoming and past events from multiple calendars.
 *	@author Nathan Glasl <nathan@silverstripe.com.au>
 */

class EventDashlet extends Dashlet
{

    private static $db = array(
        'OnlyUpcoming' => 'Boolean'
    );

    private static $defaults = array(
        'OnlyUpcoming' => 1
    );

    private static $many_many = array(
        'Calendars' => 'Calendar'
    );

    public static $title = 'Events';

    public static $cmsTitle = 'Events';

    public static $description = 'View Upcoming Events';
    
    private $addCalendar = false;
    
    public function onBeforeWrite()
    {
        if (!$this->ID) {
            $this->addCalendar = true;
        }
        parent::onBeforeWrite();
    }
    
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        
        if ($this->addCalendar) {
            $this->addCalendar = false;
            $calendars = Calendar::get()->restrict();

            foreach ($calendars as $cal) {
                $this->Calendars()->add($cal);
            }
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->push(MultiSelect2Field::create(
            'Calendars',
            'Calendars'
        )->setSource(Calendar::get()->map()->toArray())->setMultiple(true));
        $fields->push(CheckboxField::create('OnlyUpcoming'));
        return $fields;
    }

    public function getDashletFields()
    {
        $fields = parent::getDashletFields();
        $fields->push(MultiSelect2Field::create(
            'Calendars',
            'Calendars'
        )->setSource(Calendar::get()->map()->toArray())->setMultiple(true));
        $fields->push(CheckboxField::create('OnlyUpcoming'));
        return $fields;
    }
}

class EventDashlet_Controller extends Dashlet_Controller
{

    public function getEvents()
    {

        // Retrieve and merge events for each calendar selection, taking the only upcoming flag into account.

        $events = ArrayList::create();
        foreach ($this->data()->Calendars() as $calendar) {
            $events->merge($this->data()->OnlyUpcoming ? $calendar->UpcomingEvents() : $calendar->getEventList(null, null));
        }

        // Make sure the events are sorted correctly after merging.

        return $events->sort(array(
            'StartDate' => 'ASC',
            'StartTime' => 'ASC'
        ));
    }
}

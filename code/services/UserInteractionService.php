<?php

class UserInteractionService
{

    public function trackInteraction($interactionType, DataObject $item = null, Member $user = null)
    {
        if ($user == null) {
            $user = Member::currentUserID();
        }

        // Create a new user interaction object to track the page count.
        $link = '#';
        if ($item->hasMethod('RelativeLink')) {
            $link = $item->RelativeLink();
        }

        $interaction = UserInteraction::create(array('Title' => $item->Title, 'Type' => $interactionType, 'ItemClass' => get_class($item), 'ItemID' => $item->ID, 'URL' => $link, 'MemberID' => $user));
        $interaction->write();
    }

    public function getPopularInteractions($interactionType, $itemClass, $days, $number = 10)
    {
        $since = date('Y-m-d H:i:s', strtotime("-$days days"));
        // Execute an SQL query so we can group by and count.
        $interactions = UserInteraction::get()->filter(array(
            'Type' => $interactionType,
            'ItemClass' => $itemClass,
            'Created:GreaterThan' => $since
        ));

        $interactionType = Convert::raw2sql($interactionType);
        $itemClass = Convert::raw2sql($itemClass);
        
        $subs = ClassInfo::subclassesFor($itemClass);
        
        $subs[] = $itemClass;
        
        if ($i = array_search('ErrorPage', $subs)) {
            unset($subs[$i]);
        }
        $in = "'" . implode("','", $subs) ."'";

        $query = new SQLQuery('*', 'UserInteraction', "Type = '$interactionType' AND ItemClass IN ($in) AND DATEDIFF(NOW(), Created) <= $days", 'Views DESC, Title ASC', 'Title', '', $number);
        $query->selectField('COUNT(Title)', 'Views');
        $results = $query->execute();
        $container = ArrayList::create();

        // The array list will need to be populated with objects so the template accepts it.
        for ($i = 0; $i < $results->numRecords(); $i++) {
            $object = UserInteraction::create($results->record());
            if ($object->canView()) {
                $container->add($object);
            }
        }

        return $container;
    }
}

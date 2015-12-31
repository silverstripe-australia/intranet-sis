<?php

/**
 * User interaction listing
 *
 * @author Nathan Glasl <nathan@silverstripe.com.au>
 */
class InteractionDashlet extends Dashlet
{

    public static $title = "User Interaction";
    public static $cmsTitle = "User Interaction";
    public static $description = "List of User Interaction";
    private static $db = array(
        'Days' => 'Varchar'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $input = new TextField('Days', 'Past Number of Days');
        $input->setAttribute('Placeholder', 'Days');
        $fields->push($input);
        return $fields;
    }

    public function getDashletFields()
    {
        $fields = parent::getDashletFields();
        $input = new TextField('Days', 'Past Number of Days');
        $input->setAttribute('Placeholder', 'Days');
        $fields->push($input);
        return $fields;
    }
    
    public function canCreate($member = null)
    {
        return Permission::check('ADMIN');
    }
}

class InteractionDashlet_Controller extends Dashlet_Controller
{

    private static $allowed_actions = array(
        'items',
    );
    private static $dependencies = array(
        'interactions' => '%$UserInteractionService'
    );
    public $interactions;

    public function items()
    {
        if ($this->Days == null) {
            $this->Days = 30;
        }

        // Use the pageID to check if this call is the result of some javascript.

        $pageFlag = $this->getRequest()->getVar('pageFlag');

        if (!isset($pageFlag)) {
            return $this->interactions->getPopularInteractions('page-view', 'Page', $this->Days);
        } else {

            // We want to add a content type so the application knows it will be dealing with json.
            $this->response->addHeader('Content-Type', 'application/json');

            // We require an associative array at the top level, so we'll create this and insert our data array.

            $data = array('list' => $this->interactions->getPopularInteractions('page-view', 'Page', $this->Days)->toNestedArray());

            // Convert this array to json so we can interate through using javascript.

            return Convert::array2json($data);
        }
    }
}

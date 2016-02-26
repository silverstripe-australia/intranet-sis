<?php

/**
 * Page viewing dashlet.
 *
 * @author Nathan Glasl <nathan@silverstripe.com.au>
 */
class ViewingDashlet extends Dashlet
{

    public static $title = "Selected Page";
    public static $cmsTitle = "Selected Page";
    public static $description = "View of Selected Page";
    private static $db = array(
        'PageName' => 'Varchar',
        'DisplayLinks' => 'Boolean'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $input = new TextField('PageName', 'Page');
        $input->setAttribute('Placeholder', 'Page Name');
        $fields->push($input);
        $static = new CheckboxField('DisplayLinks', 'Display Selected Links?');
        $fields->push($static);
        return $fields;
    }

    public function getDashletFields()
    {
        $fields = parent::getDashletFields();
        $input = new TextField('PageName', 'Page');
        $input->setAttribute('Placeholder', 'Page Name');
        $fields->push($input);
        $static = new CheckboxField('DisplayLinks', 'Display Selected Links?');
        $fields->push($static);
        return $fields;
    }
}

class ViewingDashlet_Controller extends Dashlet_Controller
{

    private static $allowed_actions = array('display');
    private static $dependencies = array('interactions' => '%$UserInteractionService');
    public $interactions;

    public function init()
    {
        parent::init();
        Requirements::javascript(BA_SIS_COMMUNITY_PATH . '/javascript/dashlet-viewingdashlet.js');
    }

    public function display()
    {

        // We don't want the dashboard to display in the dashboard. This might create problems.
        $excluded = array("SiteDashboardPage");

        // If this call is the result of some javascript, grab the pageID or pageURL.

        $pageID = $this->getRequest()->getVar('pageID');
        $pageURL = $this->getRequest()->getVar('pageURL');

        // Use this pageID, else the pageURL, else fall back to the previously set page name field.

        if (isset($pageID)) {
            $page = Page::get()->byID($pageID);
        } elseif (isset($pageURL)) {
            $segments = explode('?', $pageURL);
            $page = Site::get_by_link($segments[0]);

            // If a certain page is excluded, we don't want it to display in the viewing dashlet.
        } else {

            // If a certain page is excluded, we don't want it to display in the viewing dashlet.

            $page = Page::get()->filter(array('Title' => $this->PageName))->first();
        }
        
        foreach ($excluded as $exclude) {
            if ($page instanceof $exclude) {
                $page = null;
                break;
            }
        }

        // Display the matching page object and render it using the template, falling back on a basic custom template.

        if ($page) {

            // We need to create a controller for the given model.

            $controller = ModelAsController::controller_for($page);

            // Make sure any query parameters carry across.

            if (isset($segments) && isset($segments[1])) {
                $URL = $segments[0];
                $parameters = null;
                parse_str($segments[1], $parameters);
                foreach ($parameters as $parameter => $value) {
                    $URL = HTTP::setGetVar($parameter, $value, $URL);
                }
                $controller->setRequest(new SS_HTTPRequest('GET', $URL, $parameters));
            }

            // If the page is not the launch page, we want to track the interaction.

            if ($page->URLSegment != 'home') {
                $this->interactions->trackInteraction('page-view', $controller->data());
            }

            // Make sure the correct template is used for a media type.

            if ($page->MediaType) {
                $templates[] = "Layout/{$page->ClassName}_{$page->MediaType}";
            }
            $templates[] = "Layout/{$page->ClassName}";
            $templates[] = 'Layout/Page';

            // We want to remove the page wrapper, so we only use the layout directory.

            return $controller->renderWith($templates);
        } else {

            // If we are clicking an invalid page link from our viewing dashlet, we don't want to refresh the dashlet.
            if (isset($pageURL)) {
                return "invalid_page";
            } else {
                return "Please select a valid page.";
            }
        }
    }
}

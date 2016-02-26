<?php

/**
 * Search listing.
 *
 * @author Nathan Glasl <nathan@silverstripe.com.au>
 */

class SearchDashletStatic extends SearchDashlet
{
    public static $title = "Static Search Results";
    public static $cmsTitle = "Static Search Results";
    public static $description = "List of Static Search Results";

    private static $db = array(
        'SearchTerm' => 'Varchar'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $input = new TextField('SearchTerm', 'Search');
        $input->setAttribute('Placeholder', 'Search Term');
        $fields->push($input);
        return $fields;
    }

    public function getDashletFields()
    {
        $fields = parent::getDashletFields();
        $input = new TextField('SearchTerm', 'Search');
        $input->setAttribute('Placeholder', 'Search Term');
        $fields->push($input);
        return $fields;
    }
}

class SearchDashletStatic_Controller extends SearchDashlet_Controller
{

    private static $allowed_actions = array('SearchForm');

    public function SearchForm()
    {
        $form = parent::SearchForm();
        if ($form) {

            // Populate the search with the static search term that has been defined.

            $term = $this->data()->SearchTerm;
            $form->Fields()->dataFieldByName('Search')->setValue($term);
            $form->addExtraClass('static-search-dashlet-form');
        }
        return $form;
    }
}

<?php

/**
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class PageListExtension extends SiteTreeExtension
{
    
    /**
     * Return an array that represents how this page should be stored for favourites
     */
    public function updateMapForList(&$d)
    {
        if (!isset($d['Title'])) {
            $d['Title']        = $this->owner->Title;
        }
        if (!isset($d['ID'])) {
            $d['ID']        = $this->owner->ID;
        }
        
        if (!isset($d['Content'])) {
            $d['Content']        = $this->owner->obj('Content')->forTemplate();
        }
        
        if (!isset($d['Link'])) {
            $d['Link']        = $this->owner->AbsoluteLink();
        }
    }
}

<?php

/**
 * 
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class UserListService
{

    public function webEnabledMethods()
    {
        return array(
            'dataForList' => 'GET',
            'saveLists' => 'POST',
            'saveSerialisedList' => 'POST',
            'getSerialisedList' => 'GET'
        );
    }
    
    /**
     * Returns a map of data representing the information
     * that should be stored in a user's list 
     * 
     * Note that for a data item to be included in a list, it MUST 
     * implement the mapForList method, to ensure that _just_ the properties
     * needed are returned. 
     * 
     * 
     * @param string $typeId
     */
    public function dataForList($typeId)
    {
        $bits = explode('-', $typeId);
        if (count($bits) !== 2) {
            throw new Exception("Invalid item being retrieved");
        }

        $data = DataList::create($bits[0])->restrictedByID($bits[1]);
        if ($data) {
            if (method_exists($data, 'mapForList')) {
                $d = $data->mapForList();
            } else {
                $d = array(
                    'Title'        => $data->Title,
                    'ID'        => $data->ID
                );
            }
            
            $data->extend('updateMapForList', $d);
            if ($data->LastEdited) {
                $d['edited_time'] = strtotime($data->LastEdited);
                $d['created_time'] = strtotime($data->Created);
            }

            return count($d) ? $d : null;
        }
    }

    /**
     *	Write the serialised information list, such that it is stored server side.
     *	@param string
     */

    public function saveSerialisedList($list)
    {

        // Retrieve the current member and save the serialised list.

        $member = Member::currentUser();
        if ($member) {
            $member->SerialisedMaterialList = $list;
            $member->write();
        }
    }

    /**
     *	Retrieve the serialised information list that has been stored server side.
     *	@return string
     */

    public function getSerialisedList()
    {

        // Retrieve the current member and the related serialised list.

        $member = Member::currentUser();
        if ($member && $member->SerialisedMaterialList) {
            return $member->SerialisedMaterialList;
        } else {
            return '';
        }
    }
}

<?php

    /**
     *	Write a serialised list of material against a member for retrieval when nothing is found locally.
     */

class MemberListExtension extends DataExtension
{

    private static $db = array(
        'SerialisedMaterialList' => 'Text'
    );

    public function updateCMSFields(FieldList $fields)
    {

        // Remove the serialised list view from the CMS.

        $fields->removeByName('SerialisedMaterialList');
    }
}

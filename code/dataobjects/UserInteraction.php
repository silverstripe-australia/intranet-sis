<?php

class UserInteraction extends DataObject
{
    
    private static $db = array(
        'Title' => 'Varchar',
        'Type' => 'Varchar',
        'ItemClass' => 'Varchar',
        'ItemID' => 'Varchar',
        'URL' => 'Varchar'
    );
    
    private static $has_one = array(
        'Member' => 'Member'
    );
}

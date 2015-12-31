<?php

class FileListingPage extends Page
{

    private static $singular_name = "File Listing Page";

    private static $plural_name = "File Listing Pages";
    
    private static $sort_dirs_map = array(
        'ASC'    => 'Ascending',
        'DESC'    => 'Descending'
    );

    /**
     * @var Array Folder names to exclude from listing (globally for this page type),
     * e.g. for "_versions" folder
     */
    public static $exclude_folder_names = array();
    
    private static $db = array(
        'ItemsPerPage'    => 'Int(3)',
        'FileSortBy'    => 'Varchar',
        'FileSortDir'    => "Enum('ASC,DESC')"
    );

    private static $has_one = array(
        'SourceFolder' => 'Folder'
    );

    private static $defaults = array(
        'ItemsPerPage'    => 10,
        'FileSortBy'    => 'Title',
        'FileSortDir'    => 'ASC'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.Main', TreeDropdownField::create('SourceFolderID', 'Source folder', 'Folder'), 'Content');
        $fields->addFieldToTab('Root.Main', NumericField::create('ItemsPerPage', 'Items per page'), 'Content');
        $fileFields = singleton('File')->inheritedDatabaseFields();
        $fileFields = array_merge($fileFields, array(
            'Created' => null,
            'LastEdited' => null
        ));
        $fileFields = ArrayLib::valuekey(array_keys($fileFields));
        $fields->addFieldToTab('Root.Main', DropdownField::create('FileSortBy', 'Sort files by', $fileFields), 'Content');
        $fields->addFieldToTab('Root.Main', DropdownField::create('FileSortDir', 'Sort direction', self::$sort_dirs_map), 'Content');
        return $fields;
    }
    
    public function getSortString()
    {
        $sort = $this->FileSortBy ? $this->FileSortBy : 'Title';
        $sort .= ' ' . ($this->FileSortDir ? $this->FileSortDir : 'ASC');
        return $sort;
    }
}

class FileListingPage_Controller extends Page_Controller
{
    
    protected $viewingFolder;
    
    private static $allowed_actions = array(
        'FileSearchForm'
    );

    public function init()
    {
        parent::init();
    }

    /**
     * Main method to get the folders and/or files to list
     *
     * @param string $restrictClass Restrict the listing to a particular File subclass: 'File' or 'Folder'
     * @return PaginatedList The list of file records
     */
    public function getFiles($restrictClass = null)
    {
        $files = $folders = array();
        $sourceFolderID = $this->getViewingFolder()->ID;

        if ($restrictClass != 'File') {
            // Retrieve the folders

            $folders = Folder::get();

            $excludeFolders = Config::inst()->get('FileListingPage', 'exclude_folder_names');
            if (is_array($excludeFolders) && count($excludeFolders)) {
                $folders = $folders->exclude('Name', array_values($excludeFolders));
            }

            if (($term = $this->request->getVar('term')) && ($term = Convert::raw2sql($term))) {
                $folders = $folders
                    ->leftJoin('File_Terms', 'File.ID = File_Terms.FileID')
                    ->leftJoin('TaxonomyTerm', 'File_Terms.TaxonomyTermID = TaxonomyTerm.ID')
                    ->where("(File.Title LIKE '%{$term}%') OR (File.Description LIKE '%{$term}%') OR (TaxonomyTerm.Name LIKE '%{$term}%')");
            } else {
                $folders = $folders->filter('ParentID', $sourceFolderID);
            }
            $folders = $folders
                ->sort($this->getSortString())
                ->toArray();
        }
        
        if ($restrictClass != 'Folder') {
            // Retrieve the files.
            $files = File::get()->filter('ClassName:not', 'Folder');
            
            if ($term) {
                $files = $files
                    ->leftJoin('File_Terms', 'File.ID = File_Terms.FileID')
                    ->leftJoin('TaxonomyTerm', 'File_Terms.TaxonomyTermID = TaxonomyTerm.ID')
                    ->where("(File.Title LIKE '%{$term}%') OR (File.Description LIKE '%{$term}%') OR (TaxonomyTerm.Name LIKE '%{$term}%')");
            } else {
                $files = $files->filter('ParentID', $sourceFolderID);
            }
            $files = $files
                ->sort($this->getSortString())
                ->toArray();
        }

        // Merge the folders and files, so the folders are displayed first.
        if ($files && $folders) {
            $files = array_merge($folders, $files);
        } elseif ($folders) {
            $files = $folders;
        }

        foreach ($files as $key => $file) {

            // Make sure the search results are contained under the current category folder.
            $parent = $file;
            $found = false;
            if ($parent->ParentID) {
                while ($parentID = $parent->ParentID) {
                    if ($parentID == $sourceFolderID) {
                        $found = true;
                    }
                    $parent = File::get()->byID($parentID);
                }
                if (!$found) {
                    unset($files[$key]);
                    continue;
                }
            }

            // Make sure any folders link back to the file listing page.
            $file->Location = ($file->ClassName === 'Folder') ?
                $this::join_links($this->Link(), "?cat={$file->ID}") :
                $file->Location = $file->Filename;
        }
        $itemsPerPage = $this->data()->ItemsPerPage ? $this->data()->ItemsPerPage : 10;
        return PaginatedList::create(ArrayList::create($files), $this->getRequest())->setPageLength($itemsPerPage);
    }

    public function FileSearchForm()
    {
        // Make sure multiple files exist before allowing search.
        $sourceFolderID = $this->getViewingFolder()->ID;
        $files = File::get()->where("ParentID = {$sourceFolderID}");
        if (!$files->count()) {
            return;
        }

        $fields = FieldList::create(
            TextField::create('term', 'Search', $this->request->getVar('term'))
            //HiddenField::create('cat', 'Category', $cat)
        );
        $actions = FieldList::create(
            FormAction::create('search', 'Go')
        );
        return Form::create($this, 'FileSearchForm', $fields, $actions);
    }

    public function search($data)
    {
        $link = $this->Link();
        if (isset($data['cat']) && ($cat = $data['cat'])) {
            $link = $this::join_links($link, "?cat={$cat}");
        }
        if ($term = $data['term']) {
            $link = $this::join_links($link, "?term={$term}");
        }
        return $this->redirect($link);
    }

    // Recursively check that the current category parameter is under the source folder.
    public function hasUnderSource($category)
    {
        $children = Folder::get()->filter('ParentID', $this->data()->SourceFolderID);
        return ($children->exists() && $this->recursiveUnderSource($category, $children)) ?
                true :
                false;
    }

    public function recursiveUnderSource($category, $children)
    {
        foreach ($children as $child) {
            $deeper = $child->ChildFolders();
            if (($child->ID === $category) || ($deeper->exists() && $this->recursiveUnderSource($category, $deeper))) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * A breadcrumb method for the folders
     *
     * @return ArrayList The folder records
     */
    public function FolderLineage()
    {
        $list = ArrayList::create();
        $folder = $this->getViewingFolder();
        while ($folder && ($folder->ID != $this->data()->SourceFolderID)) {
            $list->unshift($folder);
            $folder = $folder->ParentID ? $folder->Parent() : null;
        }
        $list->unshift($this->data()->SourceFolder());
        return $list;
    }
    
    public function getViewingFolder()
    {
        if (!isset($this->viewingFolder)) {
            if (($cat = (int)$this->request->getVar('cat')) && $this->hasUnderSource($cat)) {
                $this->viewingFolder = Folder::get()->byID($cat);
            } else {
                $this->viewingFolder = $this->data()->SourceFolder();
            }
        }
        return $this->viewingFolder;
    }
    
    public function setViewingFolder(Folder $folder)
    {
        $this->viewingFolder = $folder;
    }
}

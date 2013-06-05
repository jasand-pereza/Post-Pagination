Post-Pagination
===============

Previous and next links gives a Wordpress user the ability to navigate within the post view of the admin ui.

#### Install

* Put the "Post-Pagination" folder in your plugins directory.
* Activate in the Wordpress admin.


#### Wordpress Requirements
This version has only been tested with Wordpress versions 3.5 and up. 
Testing still needs to be done to make sure this is suitable for older versions. Use at your own risk.


#### Plugins Options
The plugin's pagination has a select menu that allows the user to order by certain fields in the database.
These fields are omitted from the list.

```PHP
<?php
/**
   * Set array of filters to be excluded
   * @return array
  */
public function exclude_meta_filters() {
  // exclude anything that contains this array of words
  return array(
    'image',
    'video',
    'id',
    'url',
    'position',
    'body',
    'media',
    'lock',
    'edit',
    'thumb'
    );
}
?> 
```

To allow these other fields just remove it from the array.

#### In the Near Future
The plan is to get Post Pagination in the Wordpress SVN repo.

<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;
use TSJIPPY\ADMIN;

if (! defined('ABSPATH')) {
    exit;
}

class PostOutOfDateEmail extends ADMIN\MailSetting
{

    public \WP_User $user;
    public string $postTitle;
    public string $pageAge;
    public string $url;

    /**
     * PostOutOfDateEmail constructor.
     *
     * @param \WP_User $user The user object
     * @param string $postTitle The title of the post
     * @param string $pageAge The age of the page in days
     * @param string $url The URL of the post
     */
    public function __construct(\WP_User $user, string $postTitle = '', string $pageAge = '', string $url = '')
    {
        // call parent constructor
        parent::__construct('page_age', PLUGINSLUG);

        $this->addUser($user);

        $this->replaceArray['%post_title%']     = $postTitle;
        $this->replaceArray['%page_age%']       = $pageAge;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Please update the contents of '%post_title%'";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage   .= "It has been %page_age% days since the page with title '%post_title%' on %site_url% has been updated.<br>";
        $this->defaultMessage   .= "Please follow <a href='%url%'>this link</a> to update it. ";
    }
}

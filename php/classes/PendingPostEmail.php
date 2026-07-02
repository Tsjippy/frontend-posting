<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;
use TSJIPPY\ADMIN;

if (! defined('ABSPATH')) {
    exit;
}

class PendingPostEmail extends ADMIN\MailSetting
{

    public \WP_User $user;
    public string $authorName;
    public string $actionText;
    public string $postType;
    public string $url;

    /**
     * PendingPostEmail constructor.
     *
     * @param \WP_User $user The user object
     * @param string $authorName The name of the author
     * @param string $actionText The action text (e.g., "submitted", "updated")
     * @param string $postType The type of the post
     * @param string $url The URL of the pending post
     */
    public function __construct(\WP_User $user, string $authorName = '', string $actionText = '', string $postType = '', string $url = '')
    {
        // call parent constructor
        parent::__construct('pending_post', PLUGINSLUG);

        $this->addUser($user);

        $this->replaceArray['%author_name%']    = $authorName;
        $this->replaceArray['%action_text%']    = $actionText;
        $this->replaceArray['%post-type%']      = $postType;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Please review a %post-type%";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
        $this->defaultMessage   .= "%author_name% just %action_text% a %post-type%. Please review it <a href='%url%'>here</a>";
    }
}

<?php

namespace TSJIPPY\FRONTENDPOSTING;

use TSJIPPY;
use TSJIPPY\ADMIN;

if (! defined('ABSPATH')) {
    exit;
}

class ApprovedPostMail extends ADMIN\MailSetting
{

    public $authorName;
    public $postType;
    public $url;

    public function __construct($authorName = '', $postType = '', $url = '')
    {
        // call parent constructor
        parent::__construct('approved_post', PLUGINSLUG);

        $this->replaceArray['%author_name%']    = $authorName;
        $this->replaceArray['%post-type%']      = $postType;
        $this->replaceArray['%url%']            = $url;

        $this->defaultSubject    = "Your %post-type% is approved and published";

        $this->defaultMessage    = 'Hi %author_name%,<br><br>';
        $this->defaultMessage   .= "Your %post-type% is approved and published. View it <a href='%url%'>here</a>";
    }
}

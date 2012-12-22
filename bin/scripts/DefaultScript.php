<?php

namespace Carcass\Tools;

use Carcass\Application as Application;

class DefaultScript extends Application\Controller {

    public function actionDefault() {
        (new Help)->displayTo($this->Response);
        return 0;
    }

}

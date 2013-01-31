<?php

namespace Carcass\Application;

use Carcass\Corelib;

class Web_Renderer_Jsonp extends Web_Renderer_Json {

    const
        DEFAULT_JSONP_CALLBACK = 'callback',
        CALLBACK_KEY_IN_RENDER_DATA_ARRAY = 'jsonp_callback';

    protected function doRender(array $render_data) {
        if (isset($render_data[self::CALLBACK_KEY_IN_RENDER_DATA_ARRAY])) {
            $callback_name = $this->validateCallbackName($render_data[self::CALLBACK_KEY_IN_RENDER_DATA_ARRAY])
                ? $render_data[self::CALLBACK_KEY_IN_RENDER_DATA_ARRAY] : self::DEFAULT_JSONP_CALLBACK;
            unset($render_data[self::CALLBACK_KEY_IN_RENDER_DATA_ARRAY]);
        } else {
            $callback_name = self::DEFAULT_JSONP_CALLBACK;
        }
        $json_string = parent::doRender($render_data);
        return "${callback_name}(${json_string});";
    }

    protected function validateCallbackName($name) {
        return is_string($name) && preg_match('/^[$A-Z_][0-9A-Z_$]*$/i', $name);
    }

}

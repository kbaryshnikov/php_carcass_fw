<?php

namespace Carcass\Filter;

class Html implements FilterInterface {

    protected
        $args = [];

    protected static function getDefaults() {
        return [
            'use_tidy' => true,
            'tidy_args' => [],
            'use_strip_tags' => false,
            'safe_tags' => [],
            'strip_event_attributes' => true,
        ];
    }

    public function __construct(array $args = []) {
        $this->setArgs($args + static::getDefaults());
    }

    public static function construct(array $args = []) {
        return new static($args);
    }

    public static function constructStrip(array $safe_tags, $strip_event_attributes = true) {
        return new static([
            'use_strip_tags' => true,
            'safe_tags' => $safe_tags,
            'strip_event_attributes' => (bool)$strip_event_attributes,
        ]);
    }

    public function setArgs(array $args) {
        $defaults = static::getDefaults();
        foreach ($args as $k => $v) {
            if (array_key_exists($k, $defaults)) {
                settype($v, gettype($defaults[$k]));
                $this->args[$k] = $v;
            }
        }
    }

    public function filter(&$value) {
        if (!empty($this->args['use_tidy'])) {
            $value = tidy_repair_string($value, $this->getTidyConfig(), 'utf8');
        }
        if (!empty($this->args['use_strip_tags'])) {
            $value = $this->stripCdata($value);
            $value = strip_tags($value, $this->getSafeTagsString());
            if (!empty($this->args['strip_event_attributes'])) {
                $value = $this->stripEventAttributes($value);
            }
        }
    }

    protected function stripCdata($value) {
        return preg_replace('~\s*(?://)?<!\[CDATA\[.*?\]\]>\s*~is', '', $value);
    }

    protected function getTidyConfig() {
        return $this->args['tidy_args'] + [
            'show-body-only'    => true,
            'hide-comments'     => true,
            'output-xhtml'      => true,
            'word-2000'         => true,
        ];
    }

    protected function getSafeTagsString() {
        if (empty($this->args['safe_tags'])) {
            return null;
        }
        return join('', array_map(function($tag) { return '<' . $tag . '>'; }, $this->args['safe_tags']));
    }

    // http://stackoverflow.com/questions/9462104/remove-on-js-event-attributes-from-html-tags
    protected static $redefs = '(?(DEFINE)
        (?<tagname> [a-z][^\s>/]*+    )
        (?<attname> [^\s>/][^\s=>/]*+    )  # first char can be pretty much anything, including =
        (?<attval>  (?>
                        "[^"]*+" |
                        \'[^\']*+\' |
                        [^\s>]*+            # unquoted values can contain quotes, = and /
                    )
        )
        (?<attrib>  (?&attname)
                    (?: \s*+
                        = \s*+
                        (?&attval)
                    )?+
        )
        (?<crap>    [^\s>]    )             # most crap inside tag is ignored, will eat the last / in self closing tags
        (?<tag>     <(?&tagname)
                    (?: \s*+                # spaces between attributes not required: <b/foo=">"style=color:red>bold red text</b>
                        (?>
                            (?&attrib) |    # order matters
                            (?&crap)        # if not an attribute, eat the crap
                        )
                    )*+
                    \s*+ /?+
                    \s*+ >
        )
    )';

    protected function stripEventAttributes($html) {
        $redefs = self::$redefs;
        $re = '(?&tag)' . $redefs;
        return preg_replace_callback(
            "~$re~xi",
            function($match) use ($redefs) {
                $tag = $match[0];
                $re = '( ^ <(?&tagname) ) | \G \s*+ (?> ((?&attrib)) | ((?&crap)) )' . $redefs;
                return preg_replace_callback(
                    "~$re~xi",
                    function($_) {
                        return (!empty($_[1]) && !empty($_[3]))
                            ? $_[0]
                            : (
                                (isset($_[2]) && preg_match("/^on/i", $_[2]))
                                    ? ""
                                    : $_[0]
                            );
                    },
                    $tag
                );
            },
            $html);
    }

}

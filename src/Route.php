<?php
namespace Aoloe;
// use function Aoloe\debug as debug;

class Route {
    private $language = null;
    private $language_available = array('en');
    /** the default language is used if a language is detected but not provided for the specific page */
    private $language_default = 'en';
    public function set_language_default($language) {$this->language_default = $language;}
    public function set_language_available($available) {$this->language_available = $available;}
    // public function set_language($language) {$this->language = $language;}
    public function get_language() {return $this->language;} // TODO: really useful?

    private $url_base = null;
    private $url_request = null;
    private $url = null;
    private $url_segment = null;

    public function set_url_base($url) {$this->url_base = trim($url, '/').'/';}
    public function get_url_base() {return $this->url_base;}
    public function set_url_request($url) {
        $this->url_request = $url;
        $url = trim($url, '/');
        $this->url = isset($this->url_base) ? trim(substr($url, strlen($this->url_base)), '/') : $url;
        $this->url_segment = explode('/', $this->url);
    }
    public function read_url_request() {
        $this->set_url_request(ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
    }
    public function is_url_request($url = null) { // TODO really useful?
        return is_null($url) ? isset($this->url_request) : ($this->url_request == $url);
    }
    public function get_url() {return $this->url;}

    private $structure = null;
    public function set_structure($structure) {$this->structure = $structure;}

    private $page = null;
    private $page_url = null;
    private $page_query = null;
    private $not_found = false;
    private $page_aliased_url = null;

    public function get_page() {return $this->page;}
    public function get_page_url() {return $this->page_url;}
    public function get_page_query() {return $this->page_query;}
    public function is_not_found() {return $this->not_found;}
    public function get_page_aliased_url() {return $this->page_aliased_url;}
    public function is_page_aliased_url() {return isset($this->page_aliased_url);}

    /**
     * if the first chunk of the url_segment corresponds to one of the available language, use it as
     * the current language and remove it from url_segment.
     * if you read the language and a language is found, it will be set and used when reading the page.
     */
    public function read_language() {
        $language = $this->url_segment[0];
        if (in_array($language, $this->language_available)) {
            $this->language = $language;
            $this->url_segment = array_slice($this->url_segment, 1);
        }
    }

    public function read_current_page($page_url = null) {
        if (isset($page_url)) {
            // TODO: what if the $page url must be translated?
            $url_segment = explode('/', $page_url);
        } else {
            $url_segment = $this->url_segment;
        }
        list($this->page, $this->page_url, $this->page_query)  = $this->get_current_page($url_segment, $this->structure);

        list($alias_url, $alias_follow) = $this->get_aliased_page($this->page);
        if (isset($alias_url)) {
            $this->page_aliased_url = null;
            if (!$alias_follow) {
                $this->page_aliased_url = $this->page_url;
            }
            $url_segment = explode('/', $alias_url);
            list($this->page, $this->page_url, $this->page_query)  = $this->get_current_page($url_segment, $this->structure);
        }
        // debug('page', $this->page);
        // debug('page_url', $this->page_url);
        // debug('page_query', $this->page_query);
    }

    /**
     * TODO: should be renamed from get_current_page to get_matching_page() ?
     * match the first segment in $url_segment with structure.
     * if it matches and there are further segments
     * - check if any children match
     * - if not, check if 'query' is set and use the rest of the $url_segment as the query
     * - if not no page has been found
     */
    private function get_current_page($url_segment, $structure) {
        $page = null;
        $page_query = null;
        $url = reset($url_segment);
        $url_child = '';
        $child_is_active = false;

        $key_current = $this->get_key_matching_url_in_structure($url, $structure);
        // debug('key_current', $key_current);
        if (isset($key_current)) {
            // debug('url_segment', $url_segment);
            if (!is_array($structure[$key_current])) {
                $structure[$key_current] = array();
            }
            $url = $key_current;
            if (count($url_segment) > 1) {
                if (array_key_exists('children', $structure[$key_current])) {
                    list($page, $url_child, $page_query) = $this->get_current_page(array_slice($url_segment, 1), $structure[$key_current]['children']);
                    if (isset($page)) {
                        $child_is_active = true;
                        $url = implode('/', array($url, $url_child));
                    }
                }
                if (is_null($page) && array_key_exists('query', $structure[$key_current])) {
                    $page = $structure[$key_current];
                    $page_query = implode('/', array_slice($url_segment, 1));
                }
                if (is_null($page)) {
                    $url = null;
                }
            } else {
                $page = $structure[$key_current];
            }
        } else {
            $this->not_found = true;
            $url = null;
        }
        if (is_string($page)) {
            $page = array();
        }
        // debug('url', $url);
        // debug('page_query', $page_query);
        // debug('page', $page);
        return array($page, $url, $page_query);
    }

    /**
     * an url matches an item if it matches:
     * - the key in structure
     * - the url in the item's navigation (if a language is defined, it's taken into consideration)
     */
    private function get_key_matching_url_in_structure($url, $structure) {
        $result = null;
        if (array_key_exists($url, $structure)) {
            $result = $url;
        } else {
            foreach ($structure as $key => $value) {
                if (isset($this->language)) {
                    $value = $this->get_structure_translated($value);
                }
                if (is_array($value) && array_key_exists('navigation', $value) && is_array($value['navigation']) && array_key_exists('url', $value['navigation']) && ($value['navigation']['url'] == $url)) {
                    $result = $key;
                    break;
                }
            }
        }
        return $result;
    }

    private function get_aliased_page($page) {
        $alias_url = null;
        $alias_follow = false;
        if (isset($page) && array_key_exists('alias', $page)) {
            if (is_array($page['alias'])) {
                $alias_url = $page['alias']['url'];
                if (array_key_exists('follow', $page['alias']) && $page['alias']['follow']) {
                    $alias_follow = true;
                }
            } else {
                $alias_url = $this->page['alias'];
            }
        }
        return array($alias_url, $alias_follow);
    }


    private function get_structure_translated($structure) {
        $result = $structure;
        if (array_key_exists('navigation', $structure)) {
            $key = null;
            if (array_key_exists($this->language, $structure['navigation'])) {
                $key = $this->language;
            } elseif (array_key_exists($this->language_default, $structure['navigation'])) {
                $key = $this->language_default;
            } else {
                $key = current(array_keys($structure['navigation'])); // the first translation
            }
            $result['navigation'] = $structure['navigation'][$key];
            $result['language'] = $key;
        }
        return $result;
    }
}

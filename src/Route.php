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
        if (isset($this->page) && is_array($this->page) && array_key_exists('alias', $this->page)) {
            if (is_array($this->page['alias'])) {
                $url = $this->page['alias']['url'];
                if (!array_key_exists('follow', $this->page['alias']) || !$this->page['alias']['follow']) {
                    $this->page_aliased_url = $this->page_url;
                }
            } else {
                $url = $this->page['alias'];
                $this->page_aliased_url = $this->page_url;
            }
            list($this->page, $this->page_url, $this->page_query)  = $this->get_current_page(explode('/', $url), $this->structure);
        }
        if (is_string($this->page)) {
            $this->page = array();
        }
        // debug('page', $this->page);
        // debug('page_url', $this->page_url);
        // debug('page_query', $this->page_query);
    }

    private function get_current_page($url_segment, $structure) {
        $page = null;
        $page_query = null;
        $url = reset($url_segment);
        $url_child = '';
        $child_is_active = false;

        $key_current = null;
        if (array_key_exists($url, $structure)) {
            $key_current = $url;
        } else {
            foreach ($structure as $key => $value) {
                if (isset($this->language)) {
                    $value = $this->get_structure_translated($value);
                }
                if (is_array($value) && array_key_exists('navigation', $value) && is_array($value['navigation']) && array_key_exists('url', $value['navigation']) && ($value['navigation']['url'] == $url)) {
                    $key_current = $key;
                    break;
                }
            }
        }
        // debug('key_current', $key_current);
        if (isset($key_current)) {
            // debug('url_segment', $url_segment);
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
        // debug('url', $url);
        // debug('page_query', $page_query);
        // debug('page', $page);
        return array($page, $url, $page_query);
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

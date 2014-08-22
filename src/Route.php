<?php

new Aoloe\Debug();
use function Aoloe\debug as debug;

class Route {
    private $url_request = null;
    private $url_segment = null;
    public function set_url_request($url) {
        $this->url_request = $url;
        $this->url_segment = array_slice(explode('/', $url), 1);
    }
    public function get_url_segment() {return $this->url_segment;}
    public function read_url_request() {
        $this->set_url_request(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }
    public function is_url_request($url = null) {
        return is_null($url) ? isset($this->url_request) : ($this->url_request == $url);
    }

    private $structure = null;
    public function set_structure($structure) {$this->structure = $structure;}

    private $page = null;
    private $page_query = null;
    public function get_page() {return $this->page;}
    public function get_query() {return $this->page_query;}

    public function read_current_page() {
        list($this->page, $this->page_query) = $this->get_current_page($this->url_segment, $this->structure);
        // if no page found, set the url as the parameter
        if (is_null($this->page)) {
            $this->page_query = $this->url_request;
        }
        // debug('page', $this->page);
        // debug('page_query', $this->page_query);
    }

    private function get_current_page($url_segment = null, $structure = null) {
        if (!isset($url_segment)) {
            $url_segment = $this->url_segment;
            $structure = $this->structure;
        }
        $page = null;
        $page_query = null;
        $url = reset($url_segment);
        // debug('url', $url);
        $child_is_active = false;
        if (array_key_exists($url, $structure)) {
            $page = array();
            // debug('url_segment', $url_segment);
            if (count($url_segment) > 1) {
                // TODO: only do the query=url if no children are matching!
                if (array_key_exists('query', $structure[$url]) && ($structure[$url]['query'] == 'url')) {
                    $page = $structure[$url];
                    $page_query = implode('/', array_slice($url_segment, 1));
                } elseif (array_key_exists('children', $structure[$url])) {
                    list($page, $page_query) = $this->get_current_page(array_slice($url_segment, 1), $structure[$url]['children']);
                    $child_is_active = isset($page);
                }
            } else {
                $page = $structure[$url];
            }
            if (!$child_is_active && isset($page) && array_key_exists('alias', $page)) {
                // should it set $this->page instead of $page?
                list($page, $alias_query) = $this->get_current_page(explode('/', $page['alias']), $this->structure);
                // debug('page_query', $page_query);
                // debug('page', $page);
            }
        }
        return array($page, $page_query);
    }
}

<?php

/**
 * TODO:
 * - implement the language detection
 */

namespace Aoloe;

use function Aoloe\debug as debug;

class Route {
    private $url_base = null;
    private $url_request = null;
    private $url = null;
    private $url_segment = null;
    private $not_found = false;
    public function set_url_base($url) {$this->url_base = trim($url, '/');}
    public function set_url_request($url) {
        $this->url_request = $url;
        $url = trim($url, '/');
        $this->url = isset($this->url_base) ? trim(substr($url, strlen($this->url_base)), '/') : $url;
        $this->url_segment = explode('/', $url);
    }
    public function get_url_segment() {return $this->url_segment;}
    public function is_not_found() {return $this->not_found;}
    public function read_url_request() {
        $this->set_url_request(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    }
    public function is_url_request($url = null) {
        return is_null($url) ? isset($this->url_request) : ($this->url_request == $url);
    }
    public function get_url() {return $this->url;}

    private $structure = null;
    public function set_structure($structure) {$this->structure = $structure;}

    private $page = null;
    private $page_url = null;
    private $page_query = null;
    public function get_query() {return $this->page_query;}

    public function get_page() {return $this->page;}
    public function get_page_url() {return $this->page_url;}
    public function get_page_query() {return $this->page_query;}

    public function read_current_page() {
        list($this->page, $this->page_url, $this->page_query)  = $this->get_current_page($this->url_segment, $this->structure);
        if (isset($this->page) && is_array($this->page) && array_key_exists('alias', $this->page)) {
            // debug('alias', $page['alias']);
            list($this->page, $this->page_url, $this->page_query)  = $this->get_current_page(explode('/', $this->page['alias']), $this->structure);
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
        if (array_key_exists($url, $structure)) {
            // debug('url_segment', $url_segment);
            if (count($url_segment) > 1) {
                if (array_key_exists('children', $structure[$url])) {
                    list($page, $url_child, $page_query) = $this->get_current_page(array_slice($url_segment, 1), $structure[$url]['children']);
                    if (isset($page)) {
                        $child_is_active = true;
                        $url = implode('/', array($url, $url_child));
                    }
                }
                if (is_null($page) && array_key_exists('query', $structure[$url])) {
                    $page = $structure[$url];
                    $page_query = implode('/', array_slice($url_segment, 1));
                }
                if (is_null($page)) {
                    debug('it should never get in here!');
                    $url = null;
                }
            } else {
                $page = $structure[$url];
            }
        } else {
            $url = null;
            $this->not_found = true;
        }
        // debug('url', $url);
        // debug('page_query', $page_query);
        // debug('page', $page);
        return array($page, $url, $page_query);
    }
}

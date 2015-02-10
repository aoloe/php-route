<?php

/**
 * being tested on ww.xox.ch/route/ [laptop]
 */

include('../vendor/autoload.php');
include('../src/Route.php');
include('../vendor/aoloe/php-debug/src/Debug.php');

$test = new Aoloe\Test();

$test->start("Import the GitHub deploy source");
$test->assert_identical('Route class loaded', class_exists('Aoloe\Route'), true);
$route = new Aoloe\Route();
$test->assert_identical('Route object created', is_a($route, 'Aoloe\Route'), true);
$test->stop();
unset($route);


$test->start("read url request");
$route = new Aoloe\Route();
$route->read_url_request();
$test->assert_identical("read url request", $test->access_property($route, 'url'), trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')); // 'route/test'
$route = new Aoloe\Route();
$route->set_url_request('/route/test/');
$test->assert_identical("set url request /test/", $test->access_property($route, 'url_request'), '/route/test/');
$test->assert_identical("set url request /test/", $test->access_property($route, 'url'), 'route/test');
$test->assert_identical("url segement", $route->get_url_segment(), array('route', 'test')); // '/route/test/'
unset($route);
$test->stop();

$test->start("read current path");
$route = new Aoloe\Route();
$route->set_url_request('/route/test/the-page/');
$route->set_url_base('');
$test->assert_identical("don't remove empty base path", $route->get_url(), 'route/test/the-page');
$route->set_url_base('/route/test/');
$route->set_url_request('/route/test/the-page/');
$test->assert_identical("respect base path for page", $route->get_url(), 'the-page');
$route->set_url_request('/route/test/the-directory/the-page');
$test->assert_identical("respect base path for directory/page", $route->get_url(), 'the-directory/the-page');
// $route = new Aoloe\Route();
unset($route);
$test->stop();

$test->start("set the structure");
$structure = array ('home' => true);
$route = new Aoloe\Route();
$route->set_structure($structure);
$test->assert_identical("structure is set", $test->access_property($route, 'structure'), $structure);
unset($route);
$test->stop();

$test->start("read current page");
$route = new Aoloe\Route();
$route->set_structure(array ('home' => ""));
$route->set_url_request('/home/');
$route->read_current_page();
$test->assert_identical("get page from simplest matching structure", $route->get_page(), '');
$test->assert_identical("get url from simplest matching structure", $route->get_page_url(), 'home');
$test->assert_identical("get query from simplest matching structure", $route->get_page_query(), null);

$structure = array (
    'home' => "",
    'about' => array (
        'children' => array (
            'contact' => ''
        )
    )
);
$route->set_structure($structure);
$route->set_url_request('/about/contact/');
$route->read_current_page();
$test->assert_identical("get page about/contact from home+about/contact structure", $route->get_page(), '');
$test->assert_identical("get url about/contact from home+about/contact structure", $route->get_page_url(), 'about/contact');
$test->assert_identical("get query about/contact from home+about/contact structure", $route->get_page_query(), null);
$test->assert_identical("existing page is found", $route->is_not_found(), false);
$route->set_url_request('/test/');
$route->read_current_page();
$test->assert_identical("get non existing page test from home+about/contact structure", $route->get_page(), null);
$test->assert_identical("non existing page is not found", $route->is_not_found(), true);
$test->assert_identical("get non existing url test from home+about/contact structure", $route->get_page_url(), null);
unset($route);
$test->stop();

$test->start("read query");
$route = new Aoloe\Route();
$structure = array (
    'home' => array (
        'query' => true,
    ),
    'about' => array (
        'query' => true,
        'children' => array (
            'contact' => array (
                'query' => true,
            )
        )
    )
);
$route->set_structure($structure);
$route->set_url_request('/home/');
$route->read_current_page();
$test->assert_identical("url matches home", $route->get_page_url(), 'home');
$route->set_url_request('/home/abcd');
$route->read_current_page();
$test->assert_identical("home parameter read", $route->get_page_query(), 'abcd');
$route->set_url_request('/about/contact/');
$route->read_current_page();
$test->assert_identical("about parameter not read if matches a child", $route->get_page_query(), null);
$route->set_url_request('/about/abcd/');
$route->read_current_page();
$test->assert_identical("about parameter read even if there are non matching children", $route->get_page_query(), 'abcd');
unset($route);
$test->stop();

$test->start("use aliases");
$route = new Aoloe\Route();
$structure = array (
    'home' => array (
        'alias' => 'about/contact',
    ),
    'about' => array (
        'query' => true,
        'children' => array (
            'contact' => array (
                'query' => true,
            )
        )
    )
);
$route->set_structure($structure);
$route->set_url_request('/home/');
$route->read_current_page();
$test->assert_identical("url home matches alias about/contact", $route->get_page_url(), 'about/contact');
$structure = array (
    'home' => array (
    ),
    'about' => array (
        'children' => array (
            'contact' => array (
                'alias' => 'home',
            )
        )
    )
);
$route->set_structure($structure);
$route->set_url_request('/about/contact/');
$route->read_current_page();
$test->assert_identical("url about/contact matches alias home", $route->get_page_url(), 'home');
unset($route);
$test->stop();

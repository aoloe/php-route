# Route

Extract the url from `$_REQUEST` and validate it against a site structure

## Features

- Uses list of pages to define the the route
- Multilingual support
- exceeding characters are returned as query
- support for a default page and and page not found detection

## Todo

- should the multilinguagl url and custom url be in navigation?
- warn if `read_url_request()` (and `set_url_request`) or `read_current_page` have not been run before `get_url` or `get_page_url()`.

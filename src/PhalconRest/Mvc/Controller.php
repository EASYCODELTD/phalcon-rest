<?php

namespace PhalconRest\Mvc;

/**
 * @property \PhalconRest\Http\Request $request
 * @property \PhalconRest\Http\Response $response
 * @property \Phalcon\Acl\AdapterInterface $acl
 * @property \PhalconRest\Auth\Manager $authManager
 * @property \PhalconRest\Auth\TokenParserInterface $tokenParser
 * @property \PhalconRest\Data\Query $query
 * @property \PhalconRest\Data\Query\QueryParsers\PhqlQueryParser $phqlQueryParser
 * @property \PhalconRest\Data\Query\QueryParsers\UrlQueryParser $urlQueryParser
 */

class Controller extends \Phalcon\Mvc\Controller implements ApiInjectableInterface
{
    /** @var \PhalconRest\Api */
    protected $api;

    public function setApi(\PhalconRest\Api $api)
    {
        $this->api = $api;
    }
}
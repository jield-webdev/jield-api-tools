<?php

declare(strict_types=1);

namespace Jield\ApiTools\Versioning;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Override;
use function array_reverse;
use function array_shift;
use function explode;
use function is_array;
use function is_int;
use function is_numeric;
use function preg_match;
use function trim;

class ContentTypeListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * Header to examine.
     *
     * @var string
     */
    protected string $headerName = 'content-type';

    // @codingStandardsIgnoreStart
    /**
     * @var array
     */
    protected array $regexes
        = [
            '#^application/vnd\.(?P<laminas_ver_vendor>[^.]+)\.v(?P<laminas_ver_version>\d+)(?:\.(?P<laminas_ver_resource>[a-zA-Z0-9_-]+))?(?:\+[a-z]+)?$#',
        ];

    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $this->onRoute(...), priority: -40);
    }

    /**
     * Add a regular expression to the stack
     *
     * @param string $regex
     * @return self
     */
    public function addRegexp(string $regex): static
    {
        $this->regexes[] = $regex;
        return $this;
    }

    /**
     * Match against the Content-Type header and inject into the route matches
     *
     */
    public function onRoute(MvcEvent $e): void
    {
        $routeMatches = $e->getRouteMatch();
        if (!($routeMatches instanceof RouteMatch)) {
            return;
        }

        $request = $e->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $headers = $request->getHeaders();
        if (!$headers->has(name: $this->headerName)) {
            return;
        }

        $header = $headers->get(name: $this->headerName);

        $matches = $this->parseHeaderForMatches(value: $header->getFieldValue());
        if (is_array(value: $matches)) {
            $this->injectRouteMatches(routeMatches: $routeMatches, matches: $matches);
        }
    }

    /**
     * Parse the header for matches against registered regexes
     *
     * @param string $value
     * @return false|array
     */
    protected function parseHeaderForMatches(string $value): false|array
    {
        $parts       = explode(separator: ';', string: $value);
        $contentType = array_shift(array: $parts);
        $contentType = trim(string: $contentType);

        foreach (array_reverse(array: $this->regexes) as $regex) {
            if (!preg_match(pattern: $regex, subject: $contentType, matches: $matches)) {
                continue;
            }

            return $matches;
        }

        return false;
    }

    /**
     * Inject regex matches into the route matches
     *
     * @param RouteMatch $routeMatches
     * @param array $matches
     */
    protected function injectRouteMatches(RouteMatch $routeMatches, array $matches): void
    {
        foreach ($matches as $key => $value) {
            if (is_numeric(value: $key) || is_int(value: $key) || $value === '') {
                continue;
            }

            $routeMatches->setParam(name: $key, value: $value);
        }
    }
}

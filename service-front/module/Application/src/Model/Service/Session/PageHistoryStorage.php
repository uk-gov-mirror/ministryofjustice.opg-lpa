<?php
namespace Application\Model\Service\Session;

use Application\Model\Service\Session\PageHistory;
use Laminas\Session\Container;
use Laminas\Uri\Http as HttpUri;

/**
 * Store user's page visit history in their session.
 */
class PageHistoryStorage
{
    /**
     * @var Laminas\Session|Container
     */
    private $container;

    /**
     * @var Application\View\Helper\PageHistory
     */
    private $pageHistory;

    /**
     * The path of the page we're currently on.
     * @var string
     */
    private $currentPath = null;

    /**
     * Constructor
     *
     * @param Container $container Session container; if not supplied, one
     * named 'pageHistory' is created
     * @param PageHistory $pageHistory If not supplied, one is created; it
     * is instantiated with the history from the session if available
     */
    public function __construct(Container $container = null, $pageHistory = null)
    {
        $this->container = $container ?? new Container('pageHistory');
        $this->pageHistory = $pageHistory ?? new PageHistory();

        // populate page history from the session container
        $this->pageHistory->init($this->container->offsetGet('history') ?? []);
    }

    /**
     * Save the current path into the session history.
     *
     * @param HttpUri $uri URI with page path and query string
     */
    public function saveCurrentPath(HttpUri $uri)
    {
        $query = $uri->getQuery();
        if ($query !== '') {
            $query = '?' . $query;
        }
        $pagePath = $uri->getPath() . $query;

        // store current path (used by page history to determine previous path)
        $this->currentPath = $pagePath;

        // add current page path to history
        $this->pageHistory->visit($pagePath);

        // store history in session
        $this->container->offsetSet('history', $this->pageHistory->asArray());
    }

    /**
     * Return the path previously visited. See PageHistory for the logic.
     *
     * @return string
     */
    public function getPreviousPath(): ?string
    {
        return $this->pageHistory->getPreviousPath($this->currentPath);
    }
}

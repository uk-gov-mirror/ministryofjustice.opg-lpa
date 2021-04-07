<?php
namespace Application\View\Helper;

/**
 * Class for storing page history, but only enough to enable us to return
 * to the previous page.
 */
class PageHistory
{
    private $history;

    /**
     * Constructor
     *
     * @param array $history History of pages visited; typically this
     * class is instantiated from session data which will populate the
     * history so far. The array will be trimmed to a maximum of two elements,
     * and should be sorted by recency, most-recently visited first.
     */
    public function __construct(array $history = [])
    {
        $this->history = array_slice($history, 0, 2);
    }

    /**
     * Record a visit to $path.
     *
     * We store two paths in an instance at all times. When we visit a
     * page, we check that page's path against the (up to) two paths in history:
     *
     * - If the first path in the history array matches $path, we discard it: this
     * will usually happen when we refresh the page so the URL doesn't change.
     * In this case, we keep the other path in the history array.
     * - If the first path in the history array doesn't match $path, we keep it
     * and discard the second.
     *
     * In either case, we then make $path the first element in the array.
     */
    public function visit(string $path) : void
    {
        $history = [$path];

        // does $path match the first element in the list?
        // if so, remove the first element and keep the second
        if (isset($this->history[1]) && $this->history[0] === $path) {
            $history[] = $this->history[1];
        }
        else if (isset($this->history[0])) {
            // either $path does not match first element in the stack, so it's
            // a valid previous path, and we keep it and remove the last element;
            // or we just append the existing single element from history
            $history[] = $this->history[0];
        }

        $this->history = $history;
    }

    /**
     * Return the path of the previous page in the stack.
     *
     * @param string $currentPath Get the previous page relative to
     * this path
     * @return ?string Path of the previous page in the history stack
     */
    public function getPreviousPath(string $currentPath) : ?string
    {
        foreach ($this->history as $path) {
            if ($path !== $currentPath) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Return page history as an array.
     *
     * @return array
     */
    public function asArray() : array
    {
        return $this->history;
    }
}

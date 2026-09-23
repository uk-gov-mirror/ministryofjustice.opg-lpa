<?php

declare(strict_types=1);

namespace App\Handler;

use App\Form\UserSearch;
use App\RequestAttributes;
use App\Service\SharedSpaceService;
use App\Service\UserService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress UnusedClass
 */
class UserSearchHandler extends AbstractHandler
{
    public static int $LIMIT = 20;

    public function __construct(
        private readonly UserService $userService,
        private readonly SharedSpaceService $sharedSpaceService
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $form = new UserSearch([
            'csrf' => $request->getAttribute(RequestAttributes::CSRF_TOKEN),
        ]);

        $limit = self::$LIMIT;

        // to be set from GET
        $searchTerm = null;
        $searchType = null;

        // default offset
        $offset = 0;

        // next/previous params
        $nextOffset = null;
        $previousOffset = null;

        $results = null;

        if ($request->getMethod() == RequestMethodInterface::METHOD_GET) {
            $params = $request->getQueryParams();

            if (array_key_exists('searchTerm', $params)) {
                $form->setData($params);

                if ($form->isValid()) {
                    $inputFilter = $form->getInputFilter();
                    $searchTerm = $inputFilter->getValue('searchTerm');
                    $searchType = $inputFilter->getValue('searchType');
                    $offset = $inputFilter->getValue('offset');
                }
            } else {
                // reset this to empty string for display as form element value
                $params['searchTerm'] = '';

                $params['offset'] = $offset;

                $form->setData($params);
            }
        }

        if (!is_null($searchTerm)) {
            $input = trim($searchTerm);

            // userId/aReference lookups return a single record (or false if
            // not found); the other search types return a paginated list
            // along with the total number of matching records
            $paginated = !in_array($searchType, ['userId', 'aReference'], true);

            $result = match ($searchType) {
                'userId'          => $this->userService->searchById($input),
                'aReference'      => $this->userService->searchByAReference($input),
                'sharedSpaceName' => $this->sharedSpaceService->matchSharedSpaces(
                    $input,
                    ['offset' => $offset, 'limit' => $limit]
                ),
                default           => $this->userService->match(['query' => $input, 'offset' => $offset, 'limit' => $limit]),
            };

            if ($result === false) {
                $formMessages = $form->getMessages();

                $notFoundMessage = match ($searchType) {
                    'userId'          => 'No user found for user ID',
                    'aReference'      => 'No user found for A Reference',
                    'sharedSpaceName' => 'No shared space found for shared space name',
                    default           => 'No user found for email address',
                };

                // Set error message
                $messages = array_merge($formMessages, [
                    'searchTerm' => [
                        $notFoundMessage
                    ]
                ]);

                $form->setMessages($messages);
            } else {
                if ($paginated) {
                    $results = $result['results'];
                    $total = $result['total'];

                    // there are more records to come after these...
                    if ($offset + $limit < $total) {
                        $nextOffset = $offset + $limit;
                    }

                    // we are on page 2+
                    if ($offset > 0) {
                        $previousOffset = max(0, $offset - $limit);
                    }
                } else {
                    // wrap the single record so the template can treat all
                    // search types as a list of results
                    $results = [$result];
                }

                $this->auditLog(
                    $request->getAttribute(RequestAttributes::USER_EMAIL),
                    'admin.user.search',
                    'Admin viewed user data',
                    ['searched_for' => $input],
                );
            }
        }

        return new HtmlResponse($this->getTemplateRenderer()->render('app::user-search', [
            'form'  => $form,
            'results'  => $results,
            'searchTerm' => $form->get('searchTerm')->getValue(),
            'searchType' => $form->get('searchType')->getValue(),
            'secret' => $form->get('secret')->getValue(),
            'nextOffset' => $nextOffset,
            'previousOffset' => $previousOffset,
        ]));
    }
}

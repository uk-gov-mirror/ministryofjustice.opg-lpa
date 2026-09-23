<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\UserSearch;
use App\Handler\UserSearchHandler;
use App\RequestAttributes;
use App\Service\SharedSpaceService;
use App\Service\UserService;
use AppTest\Common;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserSearchHandlerTest extends TestCase
{
    private TemplateRendererInterface|MockObject $mockTemplateRenderer;
    private UserService|MockObject $mockUserService;
    private SharedSpaceService|MockObject $mockSharedSpaceService;
    private LoggerInterface|MockObject $mockLogger;
    private UserSearchHandler $handler;

    protected function setUp(): void
    {
        $this->mockUserService = $this->createMock(UserService::class);
        $this->mockSharedSpaceService = $this->createMock(SharedSpaceService::class);
        $this->mockTemplateRenderer = $this->createMock(TemplateRendererInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        $this->handler = new UserSearchHandler($this->mockUserService, $this->mockSharedSpaceService);
        $this->handler->setTemplateRenderer($this->mockTemplateRenderer);
        $this->handler->setLogger($this->mockLogger);
    }

    private function makeRequest(array $queryParams = [], string $adminEmail = null): ServerRequest
    {
        $request = (new ServerRequest())
            ->withMethod(RequestMethodInterface::METHOD_GET)
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttributes::CSRF_TOKEN, Common::TEST_CSRF_TOKEN);

        if ($adminEmail !== null) {
            $request = $request->withAttribute(RequestAttributes::USER_EMAIL, $adminEmail);
        }

        return $request;
    }

    public function testRendersForm()
    {
        $this->mockTemplateRenderer->expects($this->once())->method('render')
            ->with(
                'app::user-search',
                $this->callback(fn ($args) => $args['form'] instanceof UserSearch)
            )->willReturn('response');

        $this->handler->handle($this->makeRequest());
    }

    public function testSubmitsSearchByEmail()
    {
        $user = new User(['name' => new Name(['first' => 'David'])]);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with(['query' => 'user@example.com', 'offset' => '0', 'limit' => 20])
            ->willReturn(['results' => [$user], 'total' => 1]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['searchTerm'] === 'user@example.com'
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchByUserId()
    {
        $user = ['userId' => 'abc123', 'isActive' => true];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'abc123',
            'searchType' => 'userId',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchByAReference()
    {
        $user = ['userId' => 'abc123def456', 'isActive' => true];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn($user);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$user])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'A-99998888882',
            'searchType' => 'aReference',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testSubmitsSearchBySharedSpaceName()
    {
        $sharedSpace = ['sharedSpaceId' => 'ss1', 'sharedSpaceName' => 'Test Space'];
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', ['offset' => '0', 'limit' => 20])
            ->willReturn(['results' => [$sharedSpace], 'total' => 1]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['results'] === [$sharedSpace])
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testPaginatesMatchResultsAndSetsNextOffset()
    {
        $users = array_fill(0, 20, ['userId' => 'x', 'username' => 'x@example.com']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with(['query' => 'user', 'offset' => '0', 'limit' => 20])
            ->willReturn(['results' => $users, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                count($args['results']) === 20
                && $args['nextOffset'] === 20
                && $args['previousOffset'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user',
            'searchType' => 'email',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testPaginatesSharedSpaceResultsAndSetsPreviousOffset()
    {
        $sharedSpaces = array_fill(0, 5, ['sharedSpaceId' => 'ss', 'sharedSpaceName' => 'Test Space']);
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', ['offset' => '20', 'limit' => 20])
            ->willReturn(['results' => $sharedSpaces, 'total' => 25]);

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                count($args['results']) === 5
                && $args['nextOffset'] === null
                && $args['previousOffset'] === 0)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'offset' => '20',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testAuditLogsSuccessfulSearch()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('match')
            ->with(['query' => 'user@example.com', 'offset' => '0', 'limit' => 20])
            ->willReturn(['results' => [new User(['name' => new Name(['first' => 'David'])])], 'total' => 1]);

        $this->mockTemplateRenderer->method('render')->willReturn('response');

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with(
                'Admin viewed user data',
                $this->callback(fn ($context) =>
                    $context['event'] === 'admin.user.search'
                    && $context['admin_email'] === 'admin@example.com'
                    && !array_key_exists('admin_id', $context)
                    && $context['searched_for'] === 'user@example.com')
            );

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'offset' => '0',
            'secret' => $secret,
        ], 'admin@example.com'));
    }

    public function testRendersErrorWhenUserNotFoundByUserId()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchById')
            ->with('abc123')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No user found for user ID']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'abc123',
            'searchType' => 'userId',
            'offset' => '0',
            'secret' => $secret,
        ]));
    }

    public function testRendersErrorWhenUserNotFoundByAReference()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockUserService->expects($this->once())
            ->method('searchByAReference')
            ->with('A-99998888882')
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No user found for A Reference']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'A-99998888882',
            'searchType' => 'aReference',
            'offset' => '0',
            'secret' => $secret,
        ]));
    }

    public function testRendersErrorWhenSharedSpaceNotFound()
    {
        $secret = hash('sha512', Common::TEST_CSRF_TOKEN . UserSearch::class);

        $this->mockSharedSpaceService->expects($this->once())
            ->method('matchSharedSpaces')
            ->with('Test', ['offset' => '0', 'limit' => 20])
            ->willReturn(false);

        $this->mockLogger->expects($this->never())->method('info');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('searchTerm') === ['No shared space found for shared space name']
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'Test',
            'searchType' => 'sharedSpaceName',
            'offset' => '0',
            'secret' => $secret,
        ]));
    }

    public function testRequiresCsrf()
    {
        $this->mockUserService->expects($this->never())->method('match');

        $this->mockTemplateRenderer->expects($this->once())->method('render')->with(
            'app::user-search',
            $this->callback(fn ($args) =>
                $args['form'] instanceof UserSearch
                && $args['form']->getMessages('secret') === [
                    'notSame' => 'The form submitted did not originate from the expected site'
                ]
                && $args['results'] === null)
        )->willReturn('response');

        $this->handler->handle($this->makeRequest([
            'searchTerm' => 'user@example.com',
            'searchType' => 'email',
            'offset' => '0',
            'secret' => 'not_the_real_hash', // pragma: allowlist secret
        ]));
    }
}

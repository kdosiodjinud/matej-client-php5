<?php

namespace Lmc\Matej\RequestBuilder;

use Fig\Http\Message\RequestMethodInterface;
use Lmc\Matej\Exception\LogicException;
use Lmc\Matej\Http\RequestManager;
use Lmc\Matej\Model\Command\Interaction;
use Lmc\Matej\Model\Command\Sorting;
use Lmc\Matej\Model\Command\UserMerge;
use Lmc\Matej\Model\Request;
use Lmc\Matej\Model\Response;
use Lmc\Matej\Model\Response\SortingResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Lmc\Matej\RequestBuilder\SortingRequestBuilder
 * @covers \Lmc\Matej\RequestBuilder\AbstractRequestBuilder
 * @covers \Lmc\Matej\Exception\LogicException
 */
class SortingRequestBuilderTest extends TestCase
{
    /** @test */
    public function shouldBuildRequestWithCommands()
    {
        $sortingCommand = Sorting::create('userId1', ['itemId1', 'itemId2']);
        $builder = new SortingRequestBuilder($sortingCommand);
        $interactionCommand = Interaction::detailView('sourceId1', 'itemId1');
        $builder->setInteraction($interactionCommand);
        $userMergeCommand = UserMerge::mergeFromSourceToTargetUser('sourceId1', 'userId1');
        $builder->setUserMerge($userMergeCommand);
        $builder->setRequestId('custom-request-id-foo');
        $request = $builder->build();
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame(RequestMethodInterface::METHOD_POST, $request->getMethod());
        $this->assertSame('/sorting', $request->getPath());
        $requestData = $request->getData();
        $this->assertCount(3, $requestData);
        $this->assertSame($interactionCommand, $requestData[0]);
        $this->assertSame($userMergeCommand, $requestData[1]);
        $this->assertSame($sortingCommand, $requestData[2]);
        $this->assertSame('custom-request-id-foo', $request->getRequestId());
        $this->assertSame(SortingResponse::class, $request->getResponseClass());
    }

    /** @test */
    public function shouldThrowExceptionWhenSendingCommandsWithoutRequestManager()
    {
        $builder = new SortingRequestBuilder(Sorting::create('userId1', ['itemId1', 'itemId2']));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Instance of RequestManager must be set to request builder');
        $builder->send();
    }

    /** @test */
    public function shouldSendRequestViaRequestManager()
    {
        $requestManagerMock = $this->createMock(RequestManager::class);
        $requestManagerMock->expects($this->once())->method('sendRequest')->with($this->isInstanceOf(Request::class))->willReturn(new Response(0, 0, 0, 0));
        $builder = new SortingRequestBuilder(Sorting::create('userId1', ['itemId1', 'itemId2']));
        $builder->setRequestManager($requestManagerMock);
        $builder->send();
    }

    /** @test */
    public function shouldThrowExceptionWhenUserOfInteractionDiffersFromSorting()
    {
        $builder = new SortingRequestBuilder(Sorting::create('userId1', ['itemId1', 'itemId2']));
        $builder->setInteraction(Interaction::purchase('different-user', 'itemId1'));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('User in Interaction command ("different-user") must be the same as user in Sorting command ("userId1")');
        $builder->build();
    }

    /** @test */
    public function shouldThrowExceptionWhenUserOfUserMergeDiffersFromSorting()
    {
        $builder = new SortingRequestBuilder(Sorting::create('userId1', ['itemId1', 'itemId2']));
        $builder->setUserMerge(UserMerge::mergeInto('different-user', 'userId1'));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('User in UserMerge command ("different-user") must be the same as user in Sorting command ("userId1")');
        $builder->build();
    }

    /**
     * ([interaction], [user merge], [sorting]): (A, A -> B, B)
     * @test
     */
    public function shouldPassOnCorrectSequenceOfUsersWhenMerging()
    {
        $interactionCommand = Interaction::purchase('test-user-a', 'test-item-id');
        $userMergeCommand = UserMerge::mergeFromSourceToTargetUser('test-user-a', 'test-user-b');
        $sortingCommand = Sorting::create('test-user-b', ['itemId1', 'itemId2']);
        $builder = new SortingRequestBuilder($sortingCommand);
        $builder->setUserMerge($userMergeCommand);
        $builder->setInteraction($interactionCommand);
        $this->assertInstanceOf(Request::class, $builder->build());
    }

    /**
     * ([interaction], [user merge], [sorting]): (A, B -> A, A)
     * @test
     */
    public function shouldFailOnIncorrectSequenceOfUsersWhenMerging()
    {
        $interactionCommand = Interaction::purchase('test-user-a', 'test-item-id');
        $userMergeCommand = UserMerge::mergeFromSourceToTargetUser('test-user-b', 'test-user-a');
        $sortingCommand = Sorting::create('test-user-a', ['itemId1', 'itemId2']);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Source user in UserMerge command ("test-user-b") must be the same as user in Interaction command ("test-user-a")');
        $builder = new SortingRequestBuilder($sortingCommand);
        $builder->setUserMerge($userMergeCommand);
        $builder->setInteraction($interactionCommand);
        $builder->build();
    }
}

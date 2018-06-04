<?php

namespace Lmc\Matej\IntegrationTests\RequestBuilder;

use Lmc\Matej\Exception\LogicException;
use Lmc\Matej\IntegrationTests\IntegrationTestCase;
use Lmc\Matej\Model\Command\Interaction;
use Lmc\Matej\Model\Command\ItemProperty;
use Lmc\Matej\Model\Command\ItemPropertySetup;
use Lmc\Matej\Model\Command\UserMerge;
use Lmc\Matej\RequestBuilder\ItemPropertiesSetupRequestBuilder;

/**
 * @covers \Lmc\Matej\RequestBuilder\EventsRequestBuilder
 */
class EventsRequestBuilderTest extends IntegrationTestCase
{
    public static function setUpBeforeClass()
    {
        $request = self::createMatejInstance()->request()->setupItemProperties();
        self::addPropertiesToPropertySetupRequest($request);
        $request->send();
    }

    public static function tearDownAfterClass()
    {
        $request = self::createMatejInstance()->request()->deleteItemProperties();
        self::addPropertiesToPropertySetupRequest($request);
        $request->send();
    }

    /** @test */
    public function shouldThrowExceptionWhenSendingBlankRequest()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('At least one command must be added to the builder before sending the request');
        static::createMatejInstance()->request()->events()->send();
    }

    /** @test */
    public function shouldExecuteInteractionAndUserMergeAndItemPropertyCommands()
    {
        $response = static::createMatejInstance()->request()->events()->addInteraction(Interaction::bookmark('user-a', 'item-a'))->addInteractions([Interaction::detailView('user-b', 'item-a'), Interaction::rating('user-c', 'item-a'), Interaction::purchase('user-d', 'item-a')])->addUserMerge(UserMerge::mergeInto('user-a', 'user-b'))->addUserMerges([UserMerge::mergeInto('user-a', 'user-c'), UserMerge::mergeInto('user-a', 'user-d')])->addItemProperty(ItemProperty::create('item-a', ['test_property_a' => 'test-value-a']))->addItemProperties([ItemProperty::create('item-a', ['test_property_b' => 'test-value-b']), ItemProperty::create('item-a', ['test_property_c' => 'test-value-c'])])->send();
        $this->assertResponseCommandStatuses($response, ...$this->generateOkStatuses(10));
    }

    private static function addPropertiesToPropertySetupRequest(ItemPropertiesSetupRequestBuilder $builder)
    {
        $builder->addProperties([ItemPropertySetup::string('test_property_a'), ItemPropertySetup::string('test_property_b'), ItemPropertySetup::string('test_property_c')]);
    }
}
